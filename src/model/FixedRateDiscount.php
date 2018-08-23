<?php

namespace SilverCommerce\Discounts\Model;

use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;

class FixedRateDiscount extends Discount
{
    private static $table_name = 'Discount_FixedRate';

    private static $description = "Simple fixed value discount";

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

    public function appliedAmount(AppliedDiscount $item)
    {
        return $this->calculateAmount($item->Estimate()->getSubTotal());        
    }

    public function applyDiscount($estimate)
    {
        $applied = AppliedDiscount::create();
        $applied->Code = $this->Code;
        $applied->Title = $this->Title;
        $applied->Value = $this->calculateAmount($estimate->getTotal());
        $applied->EstimateID = $estimate->ID;

        $applied->write();

        $estimate->Discounts()->add($applied);
    }

}