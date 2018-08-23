<?php

namespace SilverCommerce\Discounts\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverCommerce\Discounts\Model\AppliedDiscount;

class LineItemExtension extends DataExtension
{
    private static $belongs_many_many = [
        'Discounts' => AppliedDiscount::class
    ];
}