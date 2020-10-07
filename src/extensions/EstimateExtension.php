<?php

namespace SilverCommerce\Discounts\Extensions;

use SilverStripe\Dev\Debug;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\DropdownField;
use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\Discounts\DiscountFactory;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;
use SilverCommerce\Discounts\Model\AppliedDiscount;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\FieldType\DBCurrency;

/**
 * Add extra fields to an estimate (to track the discount)
 */
class EstimateExtension extends DataExtension
{
    private static $has_many = [
        'Discounts' => AppliedDiscount::class
    ];

    private static $casting = [
        'DiscountDetails'   => 'Varchar',
        'DiscountTotal'   => 'Currency'
    ];

    private static $field_labels = [
        'DiscountTotalValue' => 'Total Discount'
    ];

    /**
     * Find the specific discount object for this order
     *
     * @return Discount
     */
    public function findDiscount($code)
    {
        $discount = $this->owner->Discounts()->find('Code', $code);

        return $discount;
    }

    /**
     * Does the current estimate have a discount?
     */
    public function hasDiscount()
    {
        return (ceil($this->owner->getDiscountTotal())) ? true : false;
    }

    public function recalculateDiscounts()
    {
        if ($this->owner->Discounts()->Count() > 0) {
            foreach ($this->owner->Discounts() as $discount) {
                $discount->updateDiscount();
            }
        }
    }

    /**
     * get the total of all discounts applied to this estimate.
     *
     * @return void
     */
    public function getDiscountTotal()
    {
        $total = 0;

        if ($this->owner->Discounts()->Count() > 0) {
            foreach ($this->owner->Discounts() as $discount) {
                $total += $discount->Value;
            }
        }

        $this->owner->extend("updateDiscountTotal", $total);

        return $total;
    }

    /**
     * update the total price with the discount reduction.
     *
     * @param  [type] $total
     * @return void
     */
    public function updateTotal(&$total)
    {
        $total -= $this->owner->getDiscountTotal();
    }

    /**
     * Add discount info to an estimate
     *
     * @param FieldList $fields Current field list
     */
    public function updateCMSFields(FieldList $fields)
    {
        $discounts = $fields->dataFieldByName('Discounts');

        // Switch unlink action to delete
        if (!empty($discounts)) {
            $discounts
                ->getConfig()
                ->removeComponentsByType(GridFieldDeleteAction::class)
                ->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
                ->addComponent(new GridFieldDeleteAction());
        }

        $fields->addFieldToTab(
            'Root.Main.OrdersDetails.OrdersDetailsTotals',
            ReadonlyField::create('DiscountTotalValue', $this->getOwner()->fieldLabel('DiscountTotalValue'))
                ->setValue($this->getOwner()->obj('DiscountTotal')->Nice()),
            'TotalValue'
        );
    }

    /**
     * if necessary, recalculate the discount amount when estimate is saved.
     *
     * @return void
     */
    public function onAfterWrite()
    {
        if ($this->owner->Discounts()->exists() && (!method_exists($this->owner, 'isPaid') || !$this->owner->isPaid())) {
            $this->recalculateDiscounts();
        }
    }
}
