<?php

namespace SilverCommerce\Discounts\Tests;

use LogicException;
use SilverStripe\ORM\DataList;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverCommerce\Discounts\DiscountFactory;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\Discounts\Model\AppliedDiscount;
use SilverCommerce\Discounts\Model\PercentageDiscount;

class DiscountFactoryTest extends SapphireTest
{
    protected static $fixture_file = 'Discounts.yml';

    public function testGetDiscount()
    {
        DBDatetime::set_mock_now('2018-05-17 00:00:00');
        $code = "10percent";

        $discount = DiscountFactory::create($code)->getDiscount();
        $this->assertNotEmpty($discount);
        $this->assertEquals("10% Discount", $discount->Title);

        // Check past
        DBDatetime::set_mock_now('2018-01-15 00:00:00');

        $discount = DiscountFactory::create($code)->getDiscount();
        $this->assertEmpty($discount);

        // Ignore validity
        $discount = DiscountFactory::create($code)->getDiscount(false);
        $this->assertNotEmpty($discount);
        $this->assertEquals("10% Discount", $discount->Title);

        // Check future
        DBDatetime::set_mock_now('2018-09-15 00:00:00');

        $discount = DiscountFactory::create($code)->getDiscount();
        $this->assertEmpty($discount);

        // Ignore validity
        $discount = DiscountFactory::create($code)->getDiscount(false);
        $this->assertNotEmpty($discount);
        $this->assertEquals("10% Discount", $discount->Title);

        DBDatetime::clear_mock_now();
    }

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

        DBDatetime::clear_mock_now();
    }

    public function testGetValidArray()
    {
        DBDatetime::set_mock_now('2018-05-17 00:00:00');
        $results = DiscountFactory::getValidArray();

        $this->assertTrue(is_array($results));

        DBDatetime::clear_mock_now();
    }

    public function testApplyDiscountToEstimate()
    {
        DBDatetime::set_mock_now('2018-05-17 00:00:00');

        $estimate = $this->objFromFixture(Estimate::class, 'estimateone');
        $this->assertEquals(23.952, $estimate->Total);

        $code = '10percent';
        $factory = DiscountFactory::create($code, $estimate);
        $factory->applyDiscountToEstimate();

        $this->assertEquals('10% Discount', $estimate->findDiscount($code)->Title);
        $this->assertEquals(PercentageDiscount::class, get_class($estimate->findDiscount($code)->getDiscount()));
        $this->assertEquals(21.552, $estimate->Total);

        // Clear and reset, attempt to apply single use discount
        $estimate->Discounts()->removeAll();

        $this->assertEquals(23.952, $estimate->Total);

        DBDatetime::clear_mock_now();
    }

    public function testApplyDiscountToEstimateExpiredException()
    {
        $this->expectException(LogicException::class);

        $estimate = $this->objFromFixture(Estimate::class, 'estimateone');
        $this->assertEquals(23.952, $estimate->Total);

        $code = '10percent';
        $factory = DiscountFactory::create($code, $estimate);
        $factory->applyDiscountToEstimate();

        $this->assertEquals(0, $estimate->Discounts()->count());
    }

    public function testApplyDiscountToEstimateUsedException()
    {
        $this->expectException(LogicException::class);
        DBDatetime::set_mock_now('2018-05-17 00:00:00');

        $estimate = $this->objFromFixture(Estimate::class, 'estimateone');
        $this->assertEquals(23.952, $estimate->Total);

        $code = '10percentsingle';
        $applied = AppliedDiscount::create(['Code' => $code]);
        $applied->write();

        $factory = DiscountFactory::create($code, $estimate);
        $factory->applyDiscountToEstimate();

        $this->assertEquals(0, $estimate->Discounts()->count());
        $this->assertEquals(23.952, $estimate->Total);
        $applied->delete();

        DBDatetime::clear_mock_now();
    }

    public function testApplyDiscountToEstimateDuplicateException()
    {
        $this->expectException(LogicException::class);
        DBDatetime::set_mock_now('2018-05-17 00:00:00');

        $estimate = $this->objFromFixture(Estimate::class, 'estimateone');
        $this->assertEquals(23.952, $estimate->Total);

        $code = '10percent';
        $factory = DiscountFactory::create($code, $estimate);
        $factory->applyDiscountToEstimate();

        $this->assertEquals('10% Discount', $estimate->findDiscount($code)->Title);
        $this->assertEquals(21.552, $estimate->Total);

        $factory = DiscountFactory::create($code, $estimate);
        $factory->applyDiscountToEstimate();

        $this->assertEquals(1, $estimate->Discounts()->count());

        DBDatetime::clear_mock_now();
    }
}
