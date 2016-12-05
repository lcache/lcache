<?php

namespace LCache;

//use phpunit\framework\TestCase;

class StaticL2Test extends \PHPUnit_Framework_TestCase
{
    use L2TestHelpersTrait;
    use L2TestsTrait;

    protected function setUp()
    {
        parent::setUp();
        $this->l2  = new StaticL2();
    }

    public function testClearStaticL2()
    {
        $l2 = new StaticL2();
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');
        $l2->delete('mypool', new Address());
        $this->assertNull($l2->get($myaddr));
    }

    /**
     * @expectedException LCache\UnserializationException
     */
    public function testStaticL2FailedUnserializationOnGet()
    {
        $l2 = new StaticL2();
        $this->performFailedUnserializationOnGetTest($l2);
    }

    public function testStaticL2GarbageCollection()
    {
        $l2 = new StaticL2();
        $this->performGarbageCollectionTest($l2);

        // Test item limits.
        $pool = new Integrated(new StaticL1(), $l2);
        $myaddr2 = new Address('mybin', 'mykey2');
        $myaddr3 = new Address('mybin', 'mykey3');
        $pool->collectGarbage();
        $pool->set($myaddr2, 'myvalue', -1);
        $pool->set($myaddr3, 'myvalue', -1);
        $this->assertEquals(2, $l2->countGarbage());
        $pool->collectGarbage(1);
        $this->assertEquals(1, $l2->countGarbage());
    }

    public function testStaticL2Expiration()
    {
        $l2 = new StaticL2();
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue', -1);
        $this->assertNull($l2->get($myaddr));
    }

    public function testStaticL2Reread()
    {
        $l2 = new StaticL2();
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
    }

    public function testTaggedSynchronizationStatic()
    {
        $central = new StaticL2();
        $this->performTaggedSynchronizationTest($central, new StaticL1(), new StaticL1());
    }
}
