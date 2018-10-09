<?php

namespace SilverCommerce\Discounts\Model;

use SilverStripe\ORM\ArrayList;
use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;

class PercentageDiscount extends Discount
{
    private static $table_name = 'Discount_Percentage';

    private static $description = "A simple cost-based discount";

    private static $db = [
        "Amount"    => "Decimal"
    ];

    public function appliedAmount(AppliedDiscount $item)
    {
        return $this->calculateAmount($item->Estimate());        
    }

    public function calculateAmount(Estimate $estimate)
    {
        $cats = $this->Categories();
        $all_products = ArrayList::create();
        $value = $estimate->getSubTotal();
        $min = (float) $this->MinOrder;

        if ($cats->count() > 0) {
            $value = 0;
            foreach ($cats as $cat) {
                $all_products->merge($cat->Products());
            }

            foreach ($estimate->Items() as $line_item) {
                $match = $line_item->FindStockItem();
                if ($all_products->find('ID', $match->ID)) {
                    $value += ($line_item->Quantity * $line_item->Price);
                }
            }
        }

        $converted_value = (int) ($value * 100);

        $converted_amount = $converted_value * ($this->Amount / 100);

        $amount = MathsHelper::round($converted_amount, 0)/100;

        if ($amount > $value) {
            $amount = $value;
        }

        if ($value < $min) {
            $amount = 0;
        }

        return $amount;
    }
}
