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
        $total = (($this->owner->UnitPrice - $this->getDiscountTotal()) / 100) * $this->owner->TaxRate;

        return $total;
    }

    public function getDiscountTotal()
    {
        $parent = $this->owner->Parent();
        $total = $parent->getDiscountTotal();
        $count = $parent->getTotalItems();

        return $total/$count;
    }
}