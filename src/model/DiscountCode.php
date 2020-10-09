<?php

namespace SilverCommerce\Discounts\Model;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverCommerce\Discounts\DiscountFactory;
use SilverCommerce\OrdersAdmin\Tasks\MigrateDiscountCodesTask;

/**
 * Represents a single code (either single of multi use) that is assigned to a discount
 *
 * @property string $Code
 * @property bool   $LimitUse
 * @property int    $AllowedUses
 * @property int    $Uses
 * @property string $Title
 * @property int    $ReachedAllowed
 *
 * @method Discount The assigned discount
 */
class DiscountCode extends DataObject
{
    private static $db = [
        'Code' => 'Varchar',
        'LimitUse' => 'Boolean',
        'AllowedUses' => 'Int'
    ];

    private static $has_one = [
        'Discount' => Discount::class
    ];

    private static $casting = [
        'Title' => 'Varchar',
        'Uses' => 'Int',
        'ReachedAllowed' => 'Boolean'
    ];

    private static $summary_fields = [
        'Code',
        'LimitUse',
        'AllowedUses',
        'Uses'
    ];

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $run_migration = MigrateDiscountCodesTask::config()->run_during_dev_build;

        if ($run_migration) {
            $request = Injector::inst()->get(HTTPRequest::class);
            MigrateDiscountCodesTask::create()->run($request);
        }
    }

    /**
     * Return a list of codes that are currently valid (currently active and not exceeded usage limit)
     *
     * @return \SilverStripe\ORM\DataList
     */
    public static function getValidCodes()
    {
        $discounts = DiscountFactory::getValid();

        if (!$discounts->exists()) {
            return ArrayList::create();
        }

        // compile a list of valid codes
        return self::get()
            ->filter('ID', $discounts->column('ID'))
            ->filterByCallback(function($item, $list) {
                return !($item->ReachedAllowed);
            });
    }

    public function getTitle() {
        return $this->Discount()->Title;
    }

    /**
     * Find the number of times this code has been used
     *
     * @return int 
     */
    public function getUses()
    {
        return AppliedDiscount::get()->filter('Code', $this->Code)->count();
    }

    /**
     * Has this code reached or exceeded it's allowed usage?
     *
     * @return boolean
     */
    public function getReachedAllowed()
    {
        if ($this->LimitUse && $this->Uses >= $this->AllowedUses) {
            return true;
        }

        return false;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (empty($this->Code)) {
            $this->Code = self::generateRandomString();
            
            while (!$this->validCode()) {
                $this->Code = self::generateRandomString();
            }
        }

        // Ensure that the code is URL safe
        $this->Code = Convert::raw2url($this->Code);
    }

    /**
     * Check if the current code is valid (exists on another object)
     *
     * @return boolean
     */
    protected function validCode()
    {
        $existing = self::get()
            ->filter("Code", $this->Code)
            ->first();
        
        return !($existing);
    }

    /**
     * Generate a random string that we can use for the code by default
     *
     * @return string
     */
    protected static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $string;
    }
}
