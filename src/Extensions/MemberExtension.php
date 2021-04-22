<?php

namespace SilverCommerce\Discounts\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ArrayList;

/**
 * Add additional settings to a memeber object
 *
 * @package    orders-admin
 * @subpackage extensions
 */
class MemberExtension extends DataExtension
{
    /**
     * Get a discount from the groups this member is in
     *
     * @return Discount
     */
    public function getDiscount()
    {
        $discounts = ArrayList::create();

        foreach ($this->owner->Groups() as $group) {
            foreach ($group->Discounts() as $discount) {
                $discounts->add($discount);
            }
        }

        $discounts->sort("Amount", "DESC");

        return $discounts->first();
    }
}
