<?php

namespace SilverCommerce\OrdersAdmin\Tasks;

use SilverCommerce\Discounts\Model\Discount;
use SilverCommerce\Discounts\Model\DiscountCode;
use SilverStripe\ORM\DB;
use SilverStripe\Control\Director;
use SilverStripe\Dev\MigrationTask;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Control\Controller;

/**
 * Task to handle migrating single dicount codes to new assotiations
 */
class MigrateDiscountCodesTask extends MigrationTask
{
    /**
     * Should this task be invoked automatically via dev/build?
     *
     * @config
     *
     * @var bool
     */
    private static $run_during_dev_build = true;

    private static $segment = 'MigrateDiscountCodesTask';

    protected $description = "Migrate Discount Codes";

    /**
     * Upgrade tasks
     */
    public function up()
    {
        $this->log("Upgrading Discount Codes");
        $i = 0;

        foreach (Discount::get()->filter('Code:not', null) as $discount) {
            if (!$discount->Codes()->exists()) {
                $code = DiscountCode::create();
                $code->Code = $discount->Code;
                $code->DiscountID = $discount->ID;
                $code->write();

                $discount->Code = null;
                $discount->write();

                $i++;
            }
        }

        $this->log("Upgraded {$i} Codes");
    }

    /**
     * Downgrade task
     */
    public function down()
    {
        $this->log("Downgrading Discount Codes");
        $i = 0;

        foreach (Discount::get() as $discount) {
            if (!$discount->Codes()->exists()) {
                $discount->Code = $discount->Codes()->first()->Code;
                $discount->write();
                $i++;
            }
        }

        $this->log("Downgraded {$i} Codes");
    }

    /**
     * @param string $text
     */
    protected function log($text)
    {
        if (Controller::curr() instanceof DatabaseAdmin) {
            DB::alteration_message($text, 'obsolete');
        } elseif (Director::is_cli()) {
            echo $text . "\n";
        } else {
            echo $text . "<br/>";
        }
    }
}
