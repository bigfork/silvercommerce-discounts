<?php

namespace SilverCommerce\Discounts\Model;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;

/**
 * Represents a single code (either single of multi use) that is assigned to a discount
 *
 * @property string $Code
 * @property bool   $SingleUser
 * @property int    $Uses
 *
 * @method Discount The assigned discount
 */
class DiscountCode extends DataObject
{
    private static $db = [
        'Code' => 'Varchar',
        'SingleUse' => 'Boolean'
    ];

    private static $has_one = [
        'Discount' => Discount::class
    ];

    private static $casting = [
        'Uses' => 'Int'
    ];

    private static $summary_fields = [
        'Code',
        'SingleUse',
        'Uses'
    ];

    /**
     * Find the number of times this code has been used
     *
     * @return int 
     */
    public function getUses()
    {
        return AppliedDiscount::get()->filter('Code', $this->Code)->count();
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (empty($this->Code)) {
            $this->Code = self::generateRandomString();
        }

        // Ensure that the code is URL safe
        $this->Code = Convert::raw2url($this->Code);
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
