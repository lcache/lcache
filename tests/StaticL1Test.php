<?php

namespace LCache;

//use phpunit\framework\TestCase;

class StaticL1Test extends \PHPUnit_Framework_TestCase
{
    use L1TestHelpers;
    use L1TestsTrait;

    protected function setUp() {
        parent::setUp();
        $this->l1 = new StaticL1();
        // Some tests require comparing two L1s against each other.
        $this->l1_beta = new StaticL1();
    }
}
