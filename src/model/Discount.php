<?php

namespace SilverCommerce\Discounts\Model;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Security\PermissionProvider;
use SilverCommerce\OrdersAdmin\Control\ShoppingCart;

class Discount extends DataObject implements PermissionProvider
{
    private static $table_name = 'Discount';

    private static $db = [
        "Title"     => "Varchar",
        "Type"      => "Enum('Fixed,Percentage,Free Shipping','Percentage')",
        "Code"      => "Varchar(99)",
        "Amount"    => "Decimal",
        "MinOrder"  => "Decimal",
        "Starts"    => "Date",
        "Expires"   => "Date"
    ];

    private static $has_one = [
        "Site"      => SiteConfig::class
    ];

    private static $many_many = [
        "Groups"    => Group::class
    ];

    private static $summary_fields = [
        "Title",
        "Code",
        "Starts",
        "Expires"
    ];

    /**
     * Generate a random string that we can use for the code by default
     *
     * @return string
     */
    private static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $string;
    }

    /**
     * Set more complex default data
     */
    public function populateDefaults()
    {
        $this->setField('Code', self::generateRandomString());
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Ensure that the code is URL safe
        $this->Code = Convert::raw2url($this->Code);
    }

    public function canView($member = null, $context = [])
    {
        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }
    
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan('canCreate', $member);
        if ($extended !== null) {
            return $extended;
        }

        $permissions = ["ADMIN", "DISCOUNTS_CREATE"];

        if (!$member) {
            $member = Member::currentUser();
        }
        
        if ($member && Permission::checkMember($member->ID, $permissions)) {
            return true;
        }

        return false;
    }

    public function canEdit($member = null, $context = [])
    {
        $extended = $this->extendedCan('canEdit', $member);
        if ($extended !== null) {
            return $extended;
        }

        $permissions = ["ADMIN", "DISCOUNTS_EDIT"];

        if (!$member) {
            $member = Member::currentUser();
        }
        
        if ($member && Permission::checkMember($member->ID, $permissions)) {
            return true;
        }

        return false;
    }

    public function canDelete($member = null, $context = [])
    {
        $extended = $this->extendedCan('canDelete', $member);
        if ($extended !== null) {
            return $extended;
        }

        $permissions = ["ADMIN", "DISCOUNTS_DELETE"];

        if (!$member) {
            $member = Member::currentUser();
        }
        
        if ($member && Permission::checkMember($member->ID, $permissions)) {
            return true;
        }

        return false;
    }



    public function providePermissions()
    {
        return [
            "DISCOUNTS_CREATE" => [
                'name' => 'Create Discounts',
                'help' => 'Allow user to create discounts',
                'category' => 'Discounts',
                'sort' => 88
            ],
            "DISCOUNTS_EDIT" => [
                'name' => 'Edit Discounts',
                'help' => 'Allow user to edit discounts',
                'category' => 'Discounts',
                'sort' => 87
            ],
            "DISCOUNTS_DELETE" => [
                'name' => 'Delete Discounts',
                'help' => 'Allow user to delete discounts',
                'category' => 'Discounts',
                'sort' => 86
            ]
        ];
    }
}
