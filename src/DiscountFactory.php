<?php

namespace SilverCommerce\Discounts;

use DateTime;
use SilverStripe\ORM\DB;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverCommerce\Discounts\Model\Discount;

/**
 * Simple factroy to handle getting discounts (either by code or valid)
 */
class DiscountFactory
{

    use Injectable;

    use Configurable;

    /**
     * Find a discount by the prodvided code.
     *
     * @param string  $code       A discount code to find
     * @param boolean $only_valid Only find valid codes
     */
    public function getByIdent($ident, $only_valid = true)
    {
            $siteconfig = SiteConfig::current_site_config();
            
            $discount = Discount::get()->filter(
                [
                    "Code" => $ident,
                    'SiteID' => $siteconfig->ID
                ]
            )->first();

        // Check if this discount is valid
        if ($discount && $only_valid) {
            // Set the current date to now using DBDateTime
            // for unit testing support
            $now = new DateTime(
                DBDatetime::now()->format(DBDatetime::ISO_DATETIME)
            );

            $starts = new DateTime($discount->Starts);
            $expires = new DateTime($discount->Expires);

            // If in the future, invalid
            if ($now > $expires) {
                $discount = null;
            }

            // If in the past, invalid
            if ($now < $starts) {
                $discount = null;
            }
        }

        return $discount;
    }

    /**
     * Get a list of discounts that are valid (not expired and have passed their
     * start date).
     *
     * @return SSList
     */
    public static function getValid()
    {
        $config = SiteConfig::current_site_config();
        $list = $config->Discounts();
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
        $list = $list->where(
            [
                $start_field . ' <= ?' => $now,
                $end_field . ' >= ?' => $now
            ]
        );

        return $list;
    }

    public function generateAppliedDiscount($code, $estimate)
    {
        $discount = $this->getByIdent($code);
        
        if (!$discount) {
        }
        
        $discount->applyDiscount($estimate, $code);
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
}
