<?php

namespace DocumentLanding\SdkBundle\Events;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;

class ApiRequestEvent extends Event
{

    protected $request;
    protected $isValid;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->isValid = true;
    }

    public function getRequest() {
	    return $this->request;
    }

    public function setIsValid($isValid)
    {
        $this->isValid = $isValid;
    }
    
    public function getIsValid()
    {
        return $this->isValid;
    }

}