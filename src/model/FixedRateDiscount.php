<?php

namespace SilverCommerce\Discounts\Model;

use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;

class FixedRateDiscount extends Discount
{
    private static $table_name = 'Discount_FixedRate';

    private static $db = [
        "Amount"    => "Decimal"
    ];

    public function calculateAmount($value)
    {
        $converted_value = (int) ($value * 100);

        $amount = $this->Amount;

        if ($amount > $value) {
            $amount = $value;
        }

        return $amount;
    }

}