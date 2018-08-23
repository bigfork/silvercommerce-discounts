<?php

namespace SilverCommerce\Discounts\Model;

use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;

class FreePostageDiscount extends Discount
{
    private static $table_name = 'Discount_FreePostage';

    private static $description = "removes the postage cost from an order";

    public function calculateAmount($value)
    {
        $converted_value = (int) ($value * 100);

        $converted_amount = $converted_value;

        $amount = MathsHelper::round_up($converted_amount, 0)/100;

        if ($amount > $value) {
            $amount = $value;
        }

        return $amount;
    }

    public function appliedAmount(AppliedDiscount $item)
    {
        return $this->calculateAmount($item->Estimate()->getPostage()->getPrice());
    }

    public function applyDiscount($estimate)
    {
        $postage = $estimate->getPostage();
        $applied = AppliedDiscount::create();
        $applied->Code = $this->Code;
        $applied->Title = $this->Title;
        $applied->Value = $this->calculateAmount($postage->getPrice());
        $applied->EstimateID = $estimate->ID;

        $applied->write();

        $estimate->Discounts()->add($applied);
    }

}