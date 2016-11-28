<?php

namespace LCache;

//use phpunit\framework\TestCase;

trait L1TestHelpers {

    protected function performTombstoneTest($l1)
    {
        $central = new Integrated($l1, new StaticL2());

        $dne = new Address('mypool', 'mykey-dne');
        $this->assertNull($central->get($dne));

        $tombstone = $central->getEntry($dne, true);
        $this->assertNotNull($tombstone);
        $this->assertNull($tombstone->value);
        // The L1 should return the tombstone entry so the integrated cache
        // can avoid rewriting it.
        $tombstone = $l1->getEntry($dne);
        $this->assertNotNull($tombstone);
        $this->assertNull($tombstone->value);

        // The tombstone should also count as non-existence.
        $this->assertFalse($central->exists($dne));

        // This is a no-op for most L1 implementations, but it should not
        // return false, regardless.
        $this->assertTrue(false !== $l1->collectGarbage());
    }

}