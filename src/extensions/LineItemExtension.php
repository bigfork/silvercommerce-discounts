<?php

namespace SilverCommerce\Discounts\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverCommerce\Discounts\Model\AppliedDiscount;

class LineItemExtension extends DataExtension
{
    private static $belongs_many_many = [
        'Discounts' => AppliedDiscount::class
    ];

    /**
     * Get the amount of tax for a single unit of this item
     *
     * @return float
     */
    public function updateUnitTax(&$total)
    {
        $percent = $this->getOwner()->TaxPercentage;
        $price = $this->getOwner()->UnitPrice;
        $total = (($price - $this->getDiscountTotal()) / 100) * $percent;

        return $total;
    }

    public function getDiscountTotal()
    {
        $parent = $this->getOwner()->Parent();
        $total = $parent->getDiscountTotal();
        $count = $parent->getTotalItems();
        if ($count > 0) {
            return $total/$count;
        }
        return 0;
    }
}
