<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache;

/**
 * Description of StateL1Static
 *
 * @author ndobromirov
 */
class StateL1Static implements StateL1Interface
{
    protected $hits;
    protected $misses;
    protected $last_applied_event_id;

    public function __construct()
    {
        $this->last_applied_event_id = null;
        $this->clear();
    }

    public function recordHit()
    {
        $this->hits++;
    }

    public function recordMiss()
    {
        $this->misses++;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function getMisses()
    {
        return $this->misses;
    }

    public function getLastAppliedEventID()
    {
        return $this->last_applied_event_id;
    }

    public function setLastAppliedEventID($eventId)
    {
        $this->last_applied_event_id = $eventId;
        return true;
    }

    public function clear()
    {
        $this->hits = $this->misses = 0;
    }
}
