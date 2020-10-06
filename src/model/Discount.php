<?php

namespace SilverCommerce\Discounts\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Security\PermissionProvider;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\Discounts\Model\DiscountCode;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverCommerce\Discounts\Model\AppliedDiscount;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverCommerce\CatalogueAdmin\Model\CatalogueCategory;
use SilverStripe\Forms\HeaderField;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;

/**
 * Main class that provdies base functionality for all discounts
 *
 * @property string $Title
 * @property string $Code
 * @property float  $MinOrder
 * @property string $Starts
 * @property string $Expires
 * @property string $I18nType
 *
 * @method SiteConfig Site Currently assigned SiteConfig
 * @method ManyManyList Groups List of user groups discount will be applied to
 * @method ManyManyList Categories List of Product Categories
 */
class Discount extends DataObject implements PermissionProvider
{
    private static $table_name = 'Discount';

    private static $db = [
        "Title"     => "Varchar",
        "Code"      => "Varchar(99)", // retaained for legacy support/migration
        "MinOrder"  => "Decimal",
        "Starts"    => "Date",
        "Expires"   => "Date"
    ];

    private static $has_one = [
        "Site"      => SiteConfig::class
    ];

    private static $has_many = [
        'Codes' => DiscountCode::class
    ];

    private static $many_many = [
        "Groups"    => Group::class,
        "Categories" => CatalogueCategory::class
    ];

    private static $casting = [
        'I18nType',
        'CodesList'
    ];

    private static $summary_fields = [
        'I18nType',
        "Title",
        "CodesList",
        "Starts",
        "Expires"
    ];

    private static $field_labels = [
        'I18nType' => 'Type',
        'CodesList' => 'Codes'
    ];

    public function getCMSFields()
    {
        $self = $this;

        $this->beforeUpdateCMSFields(
            function ($fields) use ($self) {
                // Hide legacy code field
                $fields->removeByName('Code');

                // Add i18n type field to field list
                $fields->addFieldToTab(
                    'Root.Main',
                    ReadonlyField::create('I18nType', $this->fieldLabel('I18nType')),
                    'Title'
                );

                $min = $fields->dataFieldByName("MinOrder");

                if ($min) {
                    $min->setDescription(
                        _t(self::class.".MinOrderPreTax", "This is the SubTotal of an order EXCLUDING vat and tax")
                    );
                }

                // Add inline edit field for codes
                if ($this->isInDB()) {
                    $fields->removeByName('Codes');

                    $fields->addFieldToTab(
                        'Root.Main',
                        HeaderField::create(_t(__CLASS__ . '.CodesTitle', 'Codes (leave blank and save to auto generate)')),
                        'Codes'
                    );

                    $codes_field = GridField::create(
                        'Codes',
                        '',
                        $this->Codes(),
                        GridFieldConfig::create()
                            ->addComponent(new GridFieldButtonRow('before'))
                            ->addComponent(new GridFieldToolbarHeader())
                            ->addComponent(new GridFieldTitleHeader())
                            ->addComponent(new GridFieldEditableColumns())
                            ->addComponent(new GridFieldDeleteAction())
                            ->addComponent(new GridFieldAddNewInlineButton())
                    );

                    $fields->addFieldToTab('Root.Main', $codes_field);
                }
            }
        );

        return parent::getCMSFields();
    }

    /**
     * Generate a translated type of this discount (based on it's classname)
     *
     * @return string
     */
    public function getI18nType()
    {
        return $this->i18n_singular_name();
    }

    /**
     * Get a list of codes (seperated by a ',')
     *
     * @return string
     */
    public function getCodesList()
    {
        return implode(', ', $this->Codes()->column('Code'));
    }

    /**
     * calculate the price reduction for this discount
     *
     * @param  Currency $value - the total/sub-total of the items this discount applies to.
     * @return int
     */
    public function calculateAmount(Estimate $estimate)
    {
        return 0;
    }

    /**
     * calculate the value of a discount using an AppliedDiscount item.
     *
     * @param  AppliedDiscount $item
     * @return float
     */
    public function appliedAmount(AppliedDiscount $item)
    {
        return 0;
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
            $member = Security::getCurrentUser();
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
            $member = Security::getCurrentUser();
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
            $member = Security::getCurrentUser();
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
