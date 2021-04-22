<?php

namespace SilverCommerce\Discounts\Extensions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\GridField\GridField;
use SilverCommerce\Discounts\Model\Discount;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
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
        // Setup add new button
        $add_button = new GridFieldAddNewMultiClass("buttons-before-left");
        $add_button->setClasses($this->get_subclasses(Discount::class));

        $config = GridFieldConfig_RecordEditor::create();
        $config->removeComponentsByType(GridFieldAddNewButton::class);
        $config->addComponent($add_button);
        
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
                    )->setConfig($config)
                ]
            )
        );
    }

    /**
     * Get a list of subclasses for the chosen type (either CatalogueProduct
     * or CatalogueCategory).
     *
     * @param  string $classname Classname of object we will get list for
     * @return array
     */
    protected function get_subclasses($classname)
    {
        // Get a list of available product classes
        $classnames = ClassInfo::subclassesFor($classname);
        array_shift($classnames);
        $return = [];

        foreach ($classnames as $classname) {
            $instance = singleton($classname);
            $description = Config::inst()->get($classname, 'description');
            $description = ($description) ? $instance->i18n_singular_name() . ': ' . $description : $instance->i18n_singular_name();
            
            $return[$classname] = $description;
        }

        asort($return);
        return $return;
    }
}
