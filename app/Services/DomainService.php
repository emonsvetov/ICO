<?php

namespace App\Services;

use InvalidArgumentException;

use App\Models\Domain;
use App\Models\User;

class DomainService
{
    private HostService $hostService;
    private string $requestDomainPort = '';
    private Domain $domain;

    private bool $isAdminAppDomain = false;

    public function __construct(
        HostService $hostService
    )
    {
        $this->hostService = $hostService;
        $this->initialize(); //cannot catch exception so called manually
    }

    public function initialize()
    {
        $domain = $this->getDomainFromRequestKey(); // Try "domainKey" in request
        $requestDomain = $this->getDomainFromRequestHeaders(); //Try "Referer" Header

        if( !$domain )
        {
            if( $requestDomain && !empty($requestDomain['host']) )
            {
                $domain = $this->getDomainByName( $requestDomain['host'] );
            }
        }

        if( $domain && $domain->exists())
        {
            $this->setDomain($domain);
        }
        
        if( $requestDomain && !empty($requestDomain['port']) )
        {
            $this->setRequestDomainPort( $requestDomain['port'] ); //For xx.localhost:3000 purpose
        }
    }

    public function setDomain( Domain $domain )
    {
        $this->domain = $domain;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getDomainName()
    {
        return $this->getDomain()->name;
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

    public function getDomainFromRequestKey()
    {
        if( request()->get('domainKey') )
        {
            return $this->getDomainByKey( request()->get('domainKey') );
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
        if( !$domain ) return null;
        $host = $this->getDomainHost();
        $port = $this->getDomainPort();
        $scheme = !empty($domain['scheme']) ? $domain['scheme'] : 'http';
        return  $scheme . '://' . $host . ($port ? ':' . $port : '');
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

    private function getDomainFromRequestHeaders()
    {
        $referer = request()->headers->get('referer');
        if( !$referer ) return;
        $refs = parse_url($referer);
        return $refs;
    }

    /**
     * refererIsValidDomain
     * Get domain from request or "passed string" and determine if domain exists in records
     * domainError 0: Invalid Headers["Referer"]
     * domainError 1: host entry not available in "parse_url" vars
     * domainError 2: Failed to get domain from request headers for one of any reasons above
     * domainError 3: Could not find domain in "domains" table
     * Return value: bool
     */

    public function refererIsValidDomain()
    {
        return $this->isValidSystemDomain();
    }

    public function isValidDomainRequest()
    {
        if($this->hostIsAdminApp())
        {
            return true;
        }
        if( $this->refererIsValidDomain() )
        {
            return true;
        }
        return false;
    }

    public function isValidSystemDomain( $domainName = null ) //is valid system domain
    {
        if( !$domainName ) {
            $domainName = $this->getDomainName();
        }

        if( !$domainName )
        {
            throw new \InvalidArgumentException ('A domain is required to validate request');
        }

        if(!Domain::whereName($domainName)->exists())
        {
            throw new \InvalidArgumentException ('Invalid system domain or wrong entry point');
        }

        return true;
    }

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
            
            if( !$this->isValidSystemDomain() )
            {
                throw new \InvalidArgumentException ('Domain is not a valid system domain');
            }

            $domainName = $this->getDomainName();
            $domain = $this->getDomainByName($domainName);
            
            if( $user->getProgramRolesByDomain( $domain ) )
            {
                return true;
            }
        }
    }
}
