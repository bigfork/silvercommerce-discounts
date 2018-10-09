<?php

namespace SilverCommerce\Discounts\Model;

use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;

class FixedRateDiscount extends Discount
{
    private static $table_name = 'Discount_FixedRate';

    private static $description = "Simple fixed value discount";

    private static $db = [
        "Amount"    => "Decimal"
    ];

    public function calculateAmount(Estimate $estimate)
    {
        $value = $estimate->getTotal();
        $min = (float) $this->MinOrder;

        $converted_value = (int) ($value * 100);

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
