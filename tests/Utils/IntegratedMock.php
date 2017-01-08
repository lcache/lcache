<?php

/**
 * @file
 * Contains a more open implementation to allow more thorough testing on the
 * integration layer.
 */

namespace LCache\Utils;

use \LCache\Integrated;
use \LCache\L1;
use \LCache\L2;

/**
 * Utility class needed for testing the Integrated layer better.
 *
 * @author ndobromirov
 */
class IntegratedMock extends Integrated
{
    public function __construct(L1 $l1, L2 $l2, $overhead_threshold = null)
    {
        parent::__construct($l1, $l2, $overhead_threshold);
    }

    /**
     * Accessor needed for tests.
     *
     * @return L1
     */
    public function getL1()
    {
        return $this->l1;
    }

    /**
     * Accessor needed for tests.
     *
     * @return L2
     */
    public function getL2()
    {
        return $this->l2;
    }

    /**
     * Utility accessor.
     *
     * @return int
     */
    public function getMissesL1()
    {
        return $this->l1->getMisses();
    }

    /**
     * Utility accessor.
     *
     * @return int
     */
    public function getMissesL2()
    {
        return $this->getMisses();
    }

    public function collectGarbageL1($item_limit = null)
    {
        return $this->l1->collectGarbage($item_limit);
    }

    public function collectGarbageL2($item_limit = null)
    {
        return $this->collectGarbage($item_limit);
    }
}
