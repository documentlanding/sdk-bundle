<?php

namespace DocumentLanding\SdkBundle\Events;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;

class PreUpdateSchemaEvent extends Event
{

    protected $request;
    protected $error;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest() {
	    return $this->request;
    }

    public function setError($error)
    {
        $this->error = $error;
    }
    
    public function getError()
    {
        return $this->error;
    }

}