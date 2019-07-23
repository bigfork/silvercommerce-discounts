<?php

namespace SilverCommerce\Discounts\Model;

use SilverStripe\ORM\ArrayList;
use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\OrdersAdmin\Model\Estimate;

class FixedRateDiscount extends Discount
{
    private static $table_name = 'Discount_FixedRate';

    private static $description = "Simple fixed value discount";

    private static $db = [
        "Amount"    => "Decimal"
    ];

    public function calculateAmount(Estimate $estimate)
    {
        $cats = $this->Categories();
        $all_products = ArrayList::create();
        $value = $estimate->getTotal();
        $min = (float) $this->MinOrder;

        if ($cats->count() > 0) {
            $value = 0;
            foreach ($cats as $cat) {
                $all_products->merge($cat->Products());
            }

            foreach ($estimate->Items() as $line_item) {
                $match = $line_item->FindStockItem();
                if ($all_products->find('ID', $match->ID)) {
                    $value += ($line_item->Quantity * $line_item->UnitPrice);
                }
            }
        }

        $amount = $this->Amount;

        if ($value < $min) {
            $amount = 0;
        }

        if ($amount > $value) {
            $amount = $value;
        }

        return $amount;
    }

    public function appliedAmount(AppliedDiscount $item)
    {
        return $this->calculateAmount($item->Estimate());        
    }

}
