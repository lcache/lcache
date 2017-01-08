<?php

/*
 * @file
 * Testing file for the defaul integration layer in the LCache library.
 */

namespace Lcache\Pool;

use \LCache\Utils\IntegratedMock as Integrated;
use \LCache\L1;
use \LCache\L2;

/**
 * Default test driver for the integration layer in LCache.
 *
 * @author ndobromirov
 */
class DefaultTest extends \LCache\IntegrationCacheTest
{
    protected function getDriverInstance(L1 $l1, L2 $l2, $threshold = null)
    {
        return new Integrated($l1, $l2, $threshold);
    }
}
