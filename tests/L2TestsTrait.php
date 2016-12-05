<?php

namespace LCache;

trait L2TestsTrait
{

    public function testL2FailedUnserialization()
    {
        $this->performFailedUnserializationTest($this->l2);
        $this->performCaughtUnserializationOnGetTest($this->l2);
    }
}
