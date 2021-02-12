<?php

namespace SilverCommerce\Discounts\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;
use SilverCommerce\Discounts\DiscountFactory;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverCommerce\Discounts\Model\DiscountCode;

/**
 * A discount that has been applied to an Estimate/Invoice
 * and/or LineItems
 */
class AppliedDiscount extends DataObject
{
    private static $table_name = 'AppliedDiscount';

    private static $db = [
        'Code' => 'Varchar',
        'Title' => 'Varchar',
        'Value' => 'Currency'
    ];

    private static $has_one = [
        'Estimate' => Estimate::class
    ];

    private static $many_many = [
        'Items' => LineItem::class
    ];

    private static $summary_fields = [
        'Code',
        'Title',
        'Value'
    ];

    public function appliedAmount(AppliedDiscount $item)
    {
        return $this->calculateValue($item->Estimate()->getTotal());
    }

    /**
     * Automatically update this discount's value based on it's estimate
     *
     * @param bool $write Should this discount be written
     *
     * @return self
     */
    public function updateDiscount($write = true)
    {
        $discount = $this->getDiscount();
        if ($discount->exists()) {
            $value = $discount->appliedAmount($this);

            $this->Value = $value;

            if ($write) {
                $this->write();
            }
        }

        return $this;
    }

    public function getDiscount()
    {
        return DiscountFactory::create($this->Code)->getDiscount(false);
    }

    /**
     * Create a slightly nicer UI for setting up new discounts via the admin
     *
     * {@inheritDoc}
     */
    public function getCMSFields()
    {
        $self = $this;
        $codes = DiscountCode::getValidCodes();

        $this->beforeUpdateCMSFields(function ($fields) use ($self) {
            if (!$self->isInDB()) {
                $codes = DiscountCode::getValidCodes();
                $fields->replaceField(
                    'Code',
                    DropdownField::create('Code', $this->fieldLabel('Code'))
                        ->setSource($codes->map('Code', 'Title'))
                        ->setEmptyString(_t(__CLASS__ . ".SelectExistingDiscount", "Select an existing Discount"))
                );
            }
        });

        return parent::getCMSFields();
    }

    /**
     * If AppliedDiscount is set with code and no title/value set these up
     *
     * @return null
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (empty($this->Title) || empty($this->Value)) {
            $discount = $this->getDiscount();
            $this->Title = $discount->Title;
            $this->updateDiscount(false);
        }
    }
}
