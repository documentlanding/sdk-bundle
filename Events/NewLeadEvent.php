<?php

namespace DocumentLanding\SdkBundle\Events;

use DocumentLanding\SdkBundle\Model\LeadInterface;
use Symfony\Component\EventDispatcher\Event;

class NewLeadEvent extends Event
{

    protected $lead;
    protected $data;

    public function __construct(LeadInterface $lead, $data)
    {
        $this->lead = $lead;
        $this->data = $data;
    }
    
    public function getLead()
    {
        return $this->lead;
    }

    public function getDataSource()
    {
        return $this->data['Source'];
    }

    public function getData()
    {
        return $this->data;
    }

}