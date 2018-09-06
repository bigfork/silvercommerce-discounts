<?php

namespace SilverCommerce\Discounts\Model;

use SilverStripe\ORM\DataObject;
use SilverCommerce\Discounts\DiscountFactory;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\OrdersAdmin\Model\LineItem;

class AppliedDiscount extends DataObject
{
    private static $table_name = 'AppliedDiscount';

    private static $db = [
        'Title' => 'Varchar',
        'Code' => 'Varchar',
        'Value' => 'Currency'
    ];

    private static $has_one = [
        'Estimate' => Estimate::class
    ];

    private static $many_many = [
        'Items' => LineItem::class
    ];

    private static $summary_fields = [
        'Title',
        'Code',
        'Value'
    ];

    public function appliedAmount(AppliedDiscount $item)
    {
        return $this->calculateValue($item->Estimate()->getTotal());        
    }

    public function updateDiscount()
    {
        $discount = $this->getDiscount();

        $value = $discount->appliedAmount($this);

        $this->Value = $value;
        $this->write();
    }

    public function getDiscount()
    {
        return DiscountFactory::create()->getByIdent($this->Code);
    }
}