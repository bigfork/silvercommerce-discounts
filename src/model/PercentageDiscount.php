<?php

namespace SilverCommerce\Discounts\Model;

use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;

class PercentageDiscount extends Discount
{
    private static $table_name = 'Discount_Percentage';

    private static $db = [
        "Amount"    => "Decimal"
    ];

    public function calculateAmount($value)
    {
        $converted_value = (int) ($value * 100);

        $converted_amount = $converted_value * ($this->Amount / 100);

        $amount = MathsHelper::round_up($converted_amount, 0)/100;

        if ($amount > $value) {
            $amount = $value;
        }

        return $amount;
    }
}