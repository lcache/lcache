<?php

namespace LCache;

class NullL1 extends StaticL1
{
    public function setWithExpiration($event_id, Address $address, $value, $created, $expiration = null)
    {
        // Store nothing; always succeed.
        return true;
    }

    public function getLastAppliedEventID()
    {
        // Because we store nothing locally, behave as if all events
        // are applied.
        return PHP_INT_MAX;
    }
}
