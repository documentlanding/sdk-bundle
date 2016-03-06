<?php

namespace DocumentLanding\SdkBundle\Events;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;

class PostUpdateSchemaEvent extends Event
{

    protected $leadClass;

    public function __construct($leadClass)
    {
        $this->leadClass = $leadClass;
    }

    public function getLeadClass() {
	    return $this->leadClass;
    }

}