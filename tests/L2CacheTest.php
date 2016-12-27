<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache;

/**
 * Description of L2CacheTest
 *
 * @author ndobromirov
 */
abstract class L2CacheTest extends \PHPUnit_Framework_TestCase
{

    abstract protected function createL2();

    /**
     * https://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers
     *
     * @return array
     */
    public function l1DriverNameProvider()
    {
        return ['apcu', 'static', 'sqlite'];
    }

    public function l1Factory()
    {
        return new L1CacheFactory();
    }
}
