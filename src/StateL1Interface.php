<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache;

/**
 *
 * @author ndobromirov
 */
interface StateL1Interface
{
    public function recordHit();

    public function getHits();

    public function recordMiss();

    public function getMisses();

    public function getLastAppliedEventID();

    public function setLastAppliedEventID($eventId);

    public function clear();
}
