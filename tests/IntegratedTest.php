<?php

namespace LCache;

use LCache\l1\L1;
use LCache\l2\L2;
use PHPUnit_Framework_TestCase;
use Phake;

class IntegratedTest extends PHPUnit_Framework_TestCase
{
    protected $integrated;
    protected $l2;

    protected function setUp()
    {
        $l1 = Phake::mock(L1::class);
        Phake::when($l1)->getKeyOverhead(Phake::anyParameters())->thenReturn(0);
        $this->l2 = Phake::mock(L2::class);
        Phake::when($this->l2)->delete(Phake::anyParameters())->thenReturn(1);
        $this->integrated = new Integrated($l1, $this->l2, 1);
    }

    public function testSet()
    {
        $this->assertEquals(0, $this->integrated->set(new Address(), 'value'));

        // test with excess and no negative cache
        $l1 = Phake::mock(L1::class);
        $address = new Address();
        Phake::when($l1)->getKeyOverhead($address)->thenReturn(1);
        $integrated = new Integrated($l1, $this->l2, 1);
        $this->assertEquals(1, $integrated->set($address, 'value'));

        // test with excess and negative cache
        $l1 = Phake::mock(L1::class);
        $address = new Address();
        Phake::when($l1)->getKeyOverhead($address)->thenReturn(1);
        Phake::when($l1)->isNegativeCache($address)->thenReturn(true);
        $integrated = new Integrated($l1, $this->l2, 1);
        $this->assertNull($integrated->set($address, 'value'));
    }
}
