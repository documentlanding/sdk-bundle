<?php

namespace DocumentLanding\SdkBundle\Events;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;

class RefreshTokenRequestEvent extends Event
{

    protected $request;
    protected $accessToken;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest() {
        return $this->request;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }
    
    public function getAccessToken()
    {
        return $this->accessToken;
    }

}