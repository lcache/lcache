<?php

namespace LCache;

// These are the tests that every functioning L1 should implement.
// NullL1 does not use these tests because the point of NullL1 is to not
// actually cache.

trait L1RequiredTests
{
  public function testAntirollback()
  {
    $this->performL1AntirollbackTest($this->l1);
  }

  public function testExcessiveOverheadSkipping()
  {
    $this->performExcessiveOverheadSkippingTest($this->l1);
  }

  public function testExists()
  {
    $this->performExistsTest($this->l1);
  }

  public function testCounters()
  {
    $this->performHitSetCounterTest($this->l1);
  }
}
