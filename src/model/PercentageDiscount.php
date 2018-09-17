<?php

namespace SilverCommerce\Discounts\Model;

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
        $value = $estimate->getSubTotal();

        $converted_value = (int) ($value * 100);

        $converted_amount = $converted_value * ($this->Amount / 100);

        $amount = MathsHelper::round($converted_amount, 0)/100;

        if ($amount > $value) {
            $amount = $value;
        }

        return $amount;
    }
}
