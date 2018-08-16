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

/**
 * Add extra fields to an estimate (to track the discount)
 * 
 */
class EstimateExtension extends DataExtension
{
    private static $db = [
        'DiscountCode' => 'Varchar(99)',
        'DiscountAmount' => 'Currency'
    ];

    private static $casting = [
        'DiscountDetails'   => 'Varchar'
    ];

    /**
     * retrieve the appropriate discount and assign its code to this.
     *
     * @param [type] $code
     * @param boolean $valid use only valid discount codes, defaults to true;
     * @return void
     */
    public function setDiscount($code, $valid = true)
    {
        $discount = null;

        $discount = DiscountFactory::getByCode(
            $code,
            $valid
        );

        if ($discount) {
            $this->owner->DiscountCode = $code;
            $this->calculateDiscountAmount($discount);
        }
    }

    /**
     * Find the specific discount object for this order
     * 
     * @return Discount
     */
    public function getDiscount()
    {
        $discount = null;

        if (!empty($this->owner->DiscountCode)) {
            $discount = DiscountFactory::getByCode(
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
     * retrieve to discount amount for this estimate.
     *
     * @return void
     */
    public function calculateDiscountAmount()
    {
        $discount = DiscountFactory::getByCode(
            $this->owner->DiscountCode,
            false
        );

        $total = $this->owner->getSubTotal();

        $amount = $discount->calculateAmount($total);

        $this->owner->DiscountAmount = $amount;
    }

    /**
     * update the total price with the discount reduction.
     *
     * @param [type] $total
     * @return void
     */
    public function updateTotal(&$total) 
    {
        $total -= $this->owner->DiscountAmount;
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

    /**
     * if necessary, recalculate the discount amount when estimate is saved.
     *
     * @return void
     */
    public function onBeforeWrite() 
    {
        if ($this->owner->DiscountCode && (!method_exists($this->owner, 'isPaid') || !$this->owner->isPaid())) {
            $this->calculateDiscountAmount();
        } 
    }
}