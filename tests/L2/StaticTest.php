<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache\L2;

/**
 * Description of StaticTest
 *
 * @author ndobromirov
 */
class StaticTest extends \LCache\L2CacheTest
{

    protected function l2FactoryOptions()
    {
        return ['static', []];
    }
}
