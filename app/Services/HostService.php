<?php

namespace App\Services;

class HostService
{
    private string $requestHostName;

    private function setRequestHostName($requestHostName)
    {
        $this->requestHostName = $requestHostName;
    }

    public function getRequestHostName()
    {
        return $this->requestHostName;
    }

    public function getAppDomainName()
    {
        // $apps = parse_url(env('APP_URL'));
    }

    public function isAdminApp()
    {
        $appUrl = env('APP_URL'); //This should be the url of the AdminFrontEnd
        $referer = request()->headers->get('referer');

        if(empty($referer) || empty($appUrl))
        {
            return false;
        }

        $apps = parse_url($appUrl);
        $refs = parse_url($referer);

        if(empty($refs['host']) || empty($apps['host']))
        {
            return false;
        }
        if( $apps['host'] != $refs['host'] ) return false;
        if( $apps['scheme'] != $refs['scheme'] ) return false;
        if(!empty($refs['port']) && !empty($apps['port']) && $refs['port'] != $apps['port']) return false;

        $this->setRequestHostName($refs['host']);

        return true;
    }
}
