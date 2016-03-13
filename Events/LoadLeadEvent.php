<?php

namespace DocumentLanding\SdkBundle\Events;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;

class LoadLeadEvent extends Event
{

    protected $request;
    protected $searchCriteria;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    public function getRequest() {
        return $this->request;
    }

    public function setSearchCriteria($searchCriteria)
    {
        if (is_array($searchCriteria)) {
            $this->searchCriteria = $searchCriteria;   
        }
    }
    
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

}