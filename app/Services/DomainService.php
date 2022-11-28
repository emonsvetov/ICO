<?php

namespace App\Services;

use InvalidArgumentException;

use App\Models\Domain;
use App\Models\User;

class DomainService
{
    private HostService $hostService;
    private array $requestDomain;
    private string $requestDomainName;
    private string $requestDomainPort;
    private string $requestDomainScheme;
    private bool $isAdminAppDomain = false;

    public function __construct(
        HostService $hostService
    )
    {
        $this->hostService = $hostService;
        $requestDomain = $this->getDomainFromRequestHeaders();
        $this->setRequestDomain($requestDomain);
        $this->setRequestDomainName(!empty($requestDomain['host']) ? $requestDomain['host'] : '');
        $this->setRequestDomainScheme(!empty($requestDomain['scheme']) ? $requestDomain['scheme'] : 'http');
        $this->setRequestDomainPort(!empty($requestDomain['port']) ? $requestDomain['port'] : '');
    }

    public function getDomainByName( string $name)
    {
        return Domain::whereName($name)->first();
    }

    public function makeUrl()
    {
        $requestDomain = $this->getRequestDomain();
        $host = !empty($requestDomain['host']) ? $requestDomain['host'] : '';
        $port = !empty($requestDomain['port']) ? $requestDomain['port'] : '';
        $scheme = !empty($requestDomain['scheme']) ? $requestDomain['scheme'] : 'http';
        return  $scheme . '://' . $host . ($port ? ':' . $port : '');
    }

    public function isAdminAppDomain()
    {
        return $this->hostIsAdminApp();
    }

    private function setRequestDomain($requestDomain)
    {
        $this->requestDomain = $requestDomain;
    }

    public function getRequestDomain()
    {
        return $this->requestDomain;
    }

    private function setRequestDomainName($requestDomainName)
    {
        $this->requestDomainName = $requestDomainName;
    }

    public function getRequestDomainName()
    {
        return $this->requestDomainName;
    }

    private function setRequestDomainPort($requestDomainPort)
    {
        $this->requestDomainPort = $requestDomainPort;
    }

    public function getRequestDomainPort()
    {
        return $this->requestDomainPort;
    }

    private function setRequestDomainScheme($requestDomainScheme)
    {
        $this->requestDomainScheme = $requestDomainScheme;
    }

    public function getRequestDomainScheme()
    {
        return $this->requestDomainScheme;
    }

    private function hostIsAdminApp()
    {   
        return $this->hostService->isAdminApp();
    }

    private function getDomainFromRequestHeaders()
    {
        $referer = request()->headers->get('referer');

        if(empty($referer))
        {
            throw new InvalidArgumentException ('domainError-0: Invalid host or domain');
        }

        $refs = parse_url($referer);

        if(empty($refs['scheme']) || empty($refs['host']))
        {
            throw new InvalidArgumentException ('domainError 1: Invalid host or domain');
        }

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
            $domainName = $this->getRequestDomainName();
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

            $domainName = $this->getRequestDomainName();
            $domain = $this->getDomainByName($domainName);
            
            if( $user->getProgramRolesByDomain( $domain ) )
            {
                return true;
            }
        }
    }
}
