<?php

namespace SilverCommerce\Discounts\Model;

use SilverStripe\ORM\ArrayList;
use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\OrdersAdmin\Model\Estimate;

class FreePostageDiscount extends Discount
{
    private static $table_name = 'Discount_FreePostage';

    private static $description = "removes the postage cost from an order";

    public function calculateAmount(Estimate $estimate)
    {
        $cats = $this->Categories();
        $all_products = ArrayList::create();
        $postage_value = $estimate->getPostage()->getPrice();
        $value = (float) $estimate->getSubTotal();
        $min = (float) $this->MinOrder;

        // If total value less than minimum, discount doesn't apply
        if ($value < $min) {
            return 0;
        } 

        // If not limiting by category, return the postage value
        if (!$cats->exists()) {
            return $postage_value;
        }

        // Finally, ensure all items in the cart are in allowed
        // categories
        foreach ($cats as $cat) {
            $all_products->merge($cat->AllProducts());
        }

        foreach ($estimate->Items() as $line_item) {
            $stock_item = $line_item->FindStockItem();
            if (!empty($stock_item) && !$all_products->find('ID', $stock_item->ID)) {
                $postage_value = 0;
            }
        }

        return $postage_value;
    }

    public function appliedAmount(AppliedDiscount $item)
    {
        return $this->calculateAmount($item->Estimate());
    }
}
