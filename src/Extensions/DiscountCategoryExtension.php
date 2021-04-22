<?php

namespace SilverCommerce\Discounts\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverCommerce\Discounts\Model\Discount;

class DiscountCategoryExtension extends DataExtension
{
    private static $belongs_many_many = [
        'Discounts' => Discount::class
    ];
}
