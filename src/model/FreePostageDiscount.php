<?php

namespace SilverCommerce\Discounts\Model;

use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;

class FreePostageDiscount extends Discount
{
    private static $table_name = 'Discount_FreePostage';

    private static $description = "removes the postage cost from an order";

    public function calculateAmount(Estimate $estimate)
    {
        $value = $estimate->getPostage()->getPrice();

        $converted_value = (int) ($value * 100);

        $converted_amount = $converted_value;

        $amount = MathsHelper::round($converted_amount, 0)/100;

        if ($amount > $value) {
            $amount = $value;
        }
        
        if ($value < $min) {
            $amount = 0;
        }

        return $amount;
    }

    public function appliedAmount(AppliedDiscount $item)
    {
        return $this->calculateAmount($item->Estimate());
    }

}
