<?php

namespace LCache;

class NullL1 extends StaticL1
{
    public function setWithExpiration($event_id, Address $address, $value, $created, $expiration = null)
    {
        // Store nothing; always succeed.
        return true;
    }
}
