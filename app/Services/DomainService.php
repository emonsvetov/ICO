<?php

namespace App\Services;

use InvalidArgumentException;

use App\Models\Domain;
use App\Models\User;

class DomainService
{
    private HostService $hostService;
    private string $requestDomainPort = '';
    private $referer = null;
    private Domain $domain;
    private $isValidDomain = null;

    private bool $isAdminAppDomain = false;

    public function __construct(
        HostService $hostService
    )
    {
        $this->hostService = $hostService;
        $this->initialize(); //cannot catch exception so called manually
    }

    public function setIsValidDomain( $flag )
    {
        $this->isValidDomain = $flag;
    }

    public function isValidDomain()
    {
        return $this->isValidDomain;
    }

    public function initialize()
    {
        $this->setDomainFromRequestKey(); // Try "domainKey" in request

        if( !$this->isAdminAppDomain() )
        {
            if( !$this->getDomain() )
            {
                $this->setDomainFromRequestHeaders(); //Try "Referer" Header
            }
        }
    }

    public function setDomain( Domain $domain )
    {
        $this->domain = $domain;
    }

    public function getDomain()
    {
        if(isset($this->domain) && $this->domain) {
            return $this->domain;
        }
    }

    public function getDomainName()
    {
        if( $this->isValidDomain() ) {
            return ($domain = $this->getDomain()) ? $domain->name : null;
        }
    }
    public function setReferer($referer)
    {
        return $this->referer = $referer;
    }

    public function getReferer()
    {
        return $this->referer;
    }

    public function getRefererHost()
    {
        $referer = $this->getReferer();
        if( $referer )
        {
            return $referer->host;
        }
    }

    public function getDomainHost()
    {
        $domain = $this->getDomain();
        return !empty($domain->host) ? $domain->host : $domain->name ;
    }

    public function getDomainPort()
    {
        $domain = $this->getDomain();
        return !empty($domain->port) ? $domain->port : $this->getRequestDomainPort() ;
    }

    public function setDomainFromRequestKey()
    {
        if( request()->get('domainKey') )
        {
            $domain = $this->getDomainByKey( request()->get('domainKey') );
            if( $domain && $domain->exists())
            {
                $this->setIsValidDomain(true);
                $this->setDomain($domain);
            }
            else
            {
                $this->setIsValidDomain(false);
            }
        }
    }

    private function setDomainFromRequestHeaders()
    {
        $referer = request()->headers->get('referer');
        if( ! $referer ) return;
        $refs = parse_url( $referer );

        if( $refs && !empty($refs['host']) )
        {
            $this->setReferer((object) $refs);
            $domain = $this->getDomainByName( $refs['host'] );
            if( $domain && $domain->exists() )
            {
                $this->setIsValidDomain(true);
                $this->setDomain( $domain );
                if( $refs && !empty($refs['port']) )
                {
                    $this->setRequestDomainPort( $refs['port'] );
                }
            }
            else
            {
                $this->setIsValidDomain(false);
            }
        }
    }

    public function getDomainByName( string $name)
    {
        return Domain::whereName($name)->first();
    }

    public function getDomainByKey( string $key)
    {
        return Domain::whereSecretKey($key)->first();
    }

    public function makeUrl()
    {
        $domain = $this->getDomain();
        if( !$domain ) {
            if( $this->isAdminAppDomain() )
            {
                return $this->hostService->getReferer();
            }
            return null;
        }
        $host = $this->getDomainHost();
        $port = $this->getDomainPort();
        $scheme = !empty($domain['scheme']) ? $domain['scheme'] : 'http';
        return  $scheme . '://' . $host . ($port ? ':' . $port : '');
    }

    public function getProgram()
    {
        $domain = $this->getDomain();
        if( !$domain || $this->isAdminAppDomain() ) {
            return null;
        }
        if($domain->programs()->exists())
        {
            return $domain->programs()->first();
        }
    }

    public function isAdminAppDomain()
    {
        return $this->hostIsAdminApp();
    }

    public function setRequestDomainPort( $port )
    {
        return $this->requestDomainPort = $port;
    }

    public function getRequestDomainPort()
    {
        return $this->requestDomainPort;
    }

    private function hostIsAdminApp()
    {
        return $this->hostService->isAdminApp();
    }

    public function isValidDomainRequest()
    {
        if($this->hostIsAdminApp())
        {
            return true;
        }
        if( $this->isValidDomain() )
        {
            return true;
        }
        return false;
    }

    public function getRequestHostName()
    {
        return $this->hostService->getRequestHostName();
    }

    // public function isValidSystemDomain( $domainName = null ) //is valid system domain
    // {
    //     dd($this->isValidDomain());

    //     if( !$domainName ) {
    //         $domainName = $this->getDomainName();
    //     }

    //     if( !$domainName )
    //     {
    //         throw new \InvalidArgumentException ('A domain is required to validate request');
    //     }

    //     if(!Domain::whereName($domainName)->exists())
    //     {
    //         throw new \InvalidArgumentException ('Invalid system domain or wrong entry point');
    //     }

    //     return true;
    // }

    public function userHasFrontendRole( User $user, Domain $domain )
    {
        if( !$user )
        {
            throw new \InvalidArgumentException ('Invalid user or role');
        }

        if( !$domain )
        {
            throw new \InvalidArgumentException ('Invalid domain to find user role');
        }

        if($this->userHasProgramRolesInDomain($user, $domain))
        {
            return true;
        }

        return false;
    }

    private function userHasProgramRolesInDomain(User $user, Domain $domain)
    {
        // dump($user);
        if( $user->organization_id != $domain->organization_id)
        {
            throw new \InvalidArgumentException ('User does not belong to domain');
        }

        $programRoles = $user->getProgramRolesByDomain( $domain ); //Need a boolean check; TODO

        if( !$programRoles )
        {
            throw new \InvalidArgumentException ('User has no program role in domain');
        }

        return true;
    }

    public function userHasBackendRole( User $user )
    {
        if( !$user )
        {
            throw new \InvalidArgumentException ('Invalid user, domain role validation failed');
        }
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function validateDomainRequest()
    {
        if($this->isValidDomainRequest())
        {
            $isAdminAppDomain = $this->isAdminAppDomain();

            $user = User::whereEmail(request()->get('email'))->first();

            if( !$user )
            {
                throw new \InvalidArgumentException ('User not found with given email address');
            }

            if( $isAdminAppDomain && ($user->isSuperAdmin() || $user->isAdmin()) )
            {
                return true;
            }

            if( !$this->isValidDomain() )
            {
                throw new \InvalidArgumentException ('Domain is not a valid system domain');
            }

            return $user->hasProgramRolesByDomain( $this->getDomain() );
        }
    }
}
