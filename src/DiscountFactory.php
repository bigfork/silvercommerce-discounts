<?php

namespace SilverCommerce\Discounts;

use DateTime;
use LogicException;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Config\Config;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\Discounts\Model\DiscountCode;
use SilverCommerce\Discounts\Model\AppliedDiscount;

/**
 * Simple factroy to handle getting discounts (either by code or valid)
 */
class DiscountFactory
{
    use Injectable;

    use Configurable;

    /**
     * Allow the number of discounts  applied to an estimate to be limited
     *
     * Defaults to 1, 0 = unlimited
     *
     * @var int
     */
    private static $discount_limit = 1;

    /**
     * Allow discount factory to replace first discount with the
     * new one.
     * 
     * If set to false, this factory throws an error.
     *
     * @var bool
     */
    private static $allow_replacement = true;

    /**
     * Discount code that we are working with
     *
     * @var string
     */
    protected $code;

    /**
     * The estimate/invoice to apply a discount to
     *
     * @var Estimate
     */
    protected $estimate;

    public function __construct($code, Estimate $estimate = null)
    {
        $this->setCode($code);

        if (!empty($estimate)) {
            $this->setEstimate($estimate);
        }
    }

    /**
     * Find a discount by the prodvided code.
     *
     * @param boolean $only_valid Only find valid codes
     *
     * @throws LogicException
     *
     * @return Discount|null
     */
    public function getDiscount($only_valid = true)
    {
        $siteconfig = SiteConfig::current_site_config();
        $code = $this->getCode();

        if (empty($code)) {
            throw new LogicException('You must set a code on DiscountFactory');
        }

        if ($only_valid) {
            $discounts = self::getValid();
        } else {
            $discounts = Discount::get();
        }

        $discount = $discounts->filter([
            "Codes.Code" => $code,
            'SiteID' => $siteconfig->ID
        ])->first();

        // If there is a discount, but it is single use and reached its limit, return nothing
        if (!empty($discount) && $only_valid) {
            /** @var DiscountCode */
            $code = $discount->Codes()->find('Code', $code);
            if ($code->getReachedAllowed()) {
                return;
            }
        }

        return $discount;
    }

    /**
     * Get a list of discounts that are valid (not expired and have passed their
     * start date).
     *
     * @return \SilverStripe\ORM\DataList
     */
    public static function getValid()
    {
        $config = SiteConfig::current_site_config();
        $where = self::getDateFilter();

        return $config->Discounts()->where($where);
    }

    /**
     * Return a list of codes that are currently valid (currently active
     * and not exceeded usage limit)
     *
     * @return ArrayList
     */
    public static function getValidCodes()
    {
        $discounts = DiscountFactory::getValid();

        if (!$discounts->exists()) {
            return ArrayList::create();
        }

        $ids = $discounts->column('ID');

        // compile a list of valid codes
        return DiscountCode::get()
            ->filter('Discount.ID', $ids)
            ->filterByCallback(function ($item, $list) {
                return !($item->ReachedAllowed);
            });
    }

    /**
     * Get a list of valid discounts as an array
     *
     * @return array
     */
    public static function getValidArray()
    {
        return self::getValid()->toArray();
    }

    /**
     * Get a date filter to be used by data queries
     *
     * @return array
     */
    protected static function getDateFilter()
    {
        $db = DB::get_conn();
        // Set the current date to now using DBDateTime
        // for unit testing support
        $start = new DateTime(
            DBDatetime::now()->format(DBDatetime::ISO_DATETIME)
        );
        $format = "%Y-%m-%d";

        $start_field = $db->formattedDatetimeClause(
            '"Discount"."Starts"',
            $format
        );
        $end_field = $db->formattedDatetimeClause(
            '"Discount"."Expires"',
            $format
        );

        $now = $start->format("Y-m-d");

        return [
            $start_field . ' <= ?' => $now,
            $end_field . ' >= ?' => $now
        ];
    }

    /**
     * Apply the selected discount to the provided estimate (performing various checks, such
     * as discount limit, duplicate checks, etc)
     *
     * @param bool $only_valid Only apply discount if valid
     *
     * @throws LogicException
     *
     * @return self
     */
    public function applyDiscountToEstimate(bool $only_valid = true)
    {
        $limit = Config::inst()->get(self::class, 'discount_limit');
        $allow_replacement = Config::inst()->get(self::class, 'allow_replacement');
        $code = $this->getCode();
        $discount = $this->getDiscount($only_valid);
        $estimate = $this->getEstimate();

        if (empty($discount)) {
            throw new LogicException(_t(__CLASS__ . '.InvalidDiscount', 'Invalid discount code'));
        }

        if (empty($estimate)) {
            throw new LogicException(_t(__CLASS__ . '.InvalidEstimate', 'No estimate, or invalid estimate provided'));
        }

        if ($estimate->findDiscount($code)) {
            throw new LogicException(_t(__CLASS__ . '.DiscountAlreadySet', 'Cannot apply discount more than once'));
        }

        /** @var DataList */
        $discounts = $estimate->Discounts();

        if ($allow_replacement === false && $limit > 0 && $discounts->count() >= $limit) {
            throw new LogicException(_t(
                __CLASS__ . '.DiscountLimitReached',
                'Only {limit} discounts can be applied at this time',
                ['limit' => $limit]
            ));
        }

        $applied = AppliedDiscount::create();
        $applied->Code = $code;
        $applied->Title = $discount->Title;
        $applied->Value = $discount->calculateAmount($estimate);
        $applied->write();

        if ($allow_replacement === true && $discounts->count() >= $limit) {
            $discounts->first()->delete();
        }

        $estimate->Discounts()->add($applied);
        $estimate->recalculateDiscounts();

        return $this;
    }

    /**
     * Get discount code that we are working with
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Find the number of times the selected code has been used
     *
     * @return int
     */
    public function getCodeUses()
    {
        return AppliedDiscount::get()
            ->filter('Code', $this->getCode())
            ->count();
    }

    /**
     * Set discount code that we are working with
     *
     * @param string $code
     *
     * @return self
     */
    public function setCode(string $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the estimate/invoice to apply a discount to
     *
     * @return  Estimate
     */
    public function getEstimate()
    {
        return $this->estimate;
    }

    /**
     * Set the estimate/invoice to apply a discount to
     *
     * @param Estimate $estimate
     *
     * @return self
     */
    public function setEstimate(Estimate $estimate)
    {
        $this->estimate = $estimate;

        return $this;
    }
}
