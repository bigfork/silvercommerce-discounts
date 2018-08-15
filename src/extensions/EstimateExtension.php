<?php

namespace SilverCommerce\Discounts\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\DropdownField;
use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\Discounts\DiscountFactory;

/**
 * Add extra fields to an estimate (to track the discount)
 * 
 */
class EstimateExtension extends DataExtension
{
    private static $db = [
        'DiscountCode' => 'Varchar(99)',
        'DiscountAmount' => 'Decimal'
    ];

    private static $casting = [
        'DiscountDetails'   => 'Varchar'
    ];

    /**
     * Find the specific discount object for this order
     * 
     * @return Discount
     */
    public function getDiscount()
    {
        $discount = null;

        if (!empty($this->owner->DiscountCode)) {
            $discount = DiscountFactory::getDiscountByCode(
                $this->owner->DiscountCode,
                false
            );
        }

        if (empty($discount)) {
            $discount = Discount::create();
            $discount->ID = -1;
        }

        return $discount;
    }

    /**
     * Does the current estimate have a discount?
     */
    public function hasDiscount()
    {
        return (ceil($this->owner->DiscountAmount)) ? true : false;
    }

    /**
     * Generate a string outlining the details of selected
     * discount
     *
     * @return string
     */
    public function getDiscountDetails()
    {
        $discount = null;
        $name = null;
        $amount = null;

        // Is there a discount code
        if (!empty($this->owner->DiscountCode)) {
            $discount = $this->owner->getDiscount();

            if ($discount->exists()) {
                $name = $discount->Title;
            } else {
                $name = $this->owner->DiscountCode;
            }
        }

        if (ceil($this->owner->DiscountAmount) > 0) {
            $amount = $this->owner->dbObject("DiscountAmount")->Nice();
        }

        if (isset($name) && isset($amount)) {
            return $name . " (" . $amount . ")";
        } else {
            return "";
        }
    }

    /**
     * Add discount info to an estimate
     * 
     * @param FieldList $fields Current field list
     */
    public function updateCMSFields(FieldList $fields)
    {
        $main = $fields->findOrMakeTab("Root.Main");
        $statuses = $this->owner->config()->get("statuses");
        $details = null;
        $totals = null;
        $misc = null;

        $discount_code = $fields->dataFieldByName('DiscountCode');
        $discount_amount = $fields->dataFieldByName('DiscountAmount');

        // Manually loop through fields to find info composite field, as
        // fieldByName cannot reliably find this.
        foreach ($main->getChildren() as $field) {
            if ($field->getName() == "OrdersDetails") {
                foreach ($field->getChildren() as $field) {
                    if ($field->getName() == "OrdersDetailsInfo") {
                        $details = $field;
                    }
                    if ($field->getName() == "OrdersDetailsTotals") {
                        $totals = $field;
                    }
                    if ($field->getName() == "OrdersDetailsMisc") {
                        $misc = $field;
                    }
                }
            }
        }

        if ($details && $statuses && is_array($statuses)) {
            $details->insertBefore(
                "Number",
                DropdownField::create(
                    'Status',
                    null,
                    $this->owner->config()->get("statuses")
                )
            );
        }

        if ($totals && $discount_amount) {
            $totals->insertBefore(
                "TotalValue",
                $discount_amount
            );
        }

        if ($misc && $discount_code) {
            $misc->push(
                $discount_code
            );
        }
        
    }
}