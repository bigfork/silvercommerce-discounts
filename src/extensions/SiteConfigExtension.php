<?php

namespace SilverCommerce\Discounts\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\GridField\GridField;
use SilverCommerce\Discounts\Model\Discount;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

/**
 * Add additional settings to the default siteconfig
 */
class SiteConfigExtension extends DataExtension
{
    private static $has_many = [
        'Discounts' => Discount::class
    ];
    
    public function updateCMSFields(FieldList $fields)
    {
        // Add config sets
        $fields->addFieldToTab(
            'Root.Shop',
            ToggleCompositeField::create(
                'DiscountSettings',
                _t("Discounts.DiscountSettings", "Discount Settings"),
                [
                    LiteralField::create("DiscountPadding", "<br/>"),
                    GridField::create(
                        'Discounts',
                        '',
                        $this->owner->Discounts()
                    )->setConfig(GridFieldConfig_RecordEditor::create())
                ]
            )
        );
    }
}
