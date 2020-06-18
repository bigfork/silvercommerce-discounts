<?php

namespace SilverCommerce\Discounts\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverCommerce\Discounts\DiscountFactory;
use SilverStripe\ORM\DataList;

class DiscountFactoryTest extends SapphireTest
{
    /**
     * Add some scaffold order records
     *
     * @var string
     */
    protected static $fixture_file = 'Discounts.yml';

    public function testgetByIdent()
    {
        DBDatetime::set_mock_now('2018-05-17 00:00:00');
        $code = "10percent";

        $discount = DiscountFactory::create()->getByIdent($code);
        $this->assertNotEmpty($discount);
        $this->assertEquals($code, $discount->Code);

        // Check past
        DBDatetime::set_mock_now('2018-01-15 00:00:00');

        $discount = DiscountFactory::create()->getByIdent($code);
        $this->assertEmpty($discount);

        // Ignore validity
        $discount = DiscountFactory::create()->getByIdent($code, false);
        $this->assertNotEmpty($discount);
        $this->assertEquals($code, $discount->Code);

        // Check future
        DBDatetime::set_mock_now('2018-09-15 00:00:00');

        $discount = DiscountFactory::create()->getByIdent($code);
        $this->assertEmpty($discount);

        // Ignore validity
        $discount = DiscountFactory::create()->getByIdent($code, false);
        $this->assertNotEmpty($discount);
        $this->assertEquals($code, $discount->Code);
    }

    /**
     * Test that valid discounts are displayed
     */
    public function testGetValid()
    {
        DBDatetime::set_mock_now('2018-05-17 00:00:00');
        $results = DiscountFactory::getValid();

        $this->assertEquals(3, $results->count());
        $this->assertTrue($results instanceof DataList);

        DBDatetime::set_mock_now('2018-01-15 00:00:00');
        $results = DiscountFactory::getValid();

        $this->assertEquals(1, $results->count());
        $this->assertTrue($results instanceof DataList);
    }

    /**
     * Test that valid discounts are displayed
     */
    public function testGetValidArray()
    {
        DBDatetime::set_mock_now('2018-05-17 00:00:00');
        $results = DiscountFactory::getValidArray();

        $this->assertTrue(is_array($results));
    }
}
