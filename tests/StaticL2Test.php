<?php

namespace LCache;

//use phpunit\framework\TestCase;

class StaticL2Test extends \PHPUnit_Framework_TestCase
{
    use L2TestHelpers;

    public function testClearStaticL2()
    {
        $l2 = new StaticL2();
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');
        $l2->delete('mypool', new Address());
        $this->assertNull($l2->get($myaddr));
    }
}
