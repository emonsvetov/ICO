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

    public function makeUrl()
    {
        $requestDomain = $this->getRequestDomain();
        $host = !empty($requestDomain['host']) ? $requestDomain['host'] : '';
        $port = !empty($requestDomain['port']) ? $requestDomain['port'] : '';
        $scheme = !empty($requestDomain['scheme']) ? $requestDomain['scheme'] : 'http';
        return  $scheme . '://' . $host . ($port ? ':' . $port : '');
    }

    private function setIsAdminAppDomain($isAdminAppDomain)
    {
        $this->isAdminAppDomain = $isAdminAppDomain;
    }

    public function getIsAdminAppDomain()
    {
        return $this->isAdminAppDomain;
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

    public function hostIsAdminApp()
    {   
        $isAdminApp = $this->hostService->isAdminApp();
        $this->setIsAdminAppDomain($isAdminApp); //complimentory function
        return $isAdminApp;
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

    public function refererIsValidDomain( string $requestDomainName = '' )
    {
        if( empty($requestDomainName) )
        {
            $requestDomainName = $this->getRequestDomainName();

            if( !$requestDomainName )
            {
                throw new InvalidArgumentException ('domainError 2: Invalid host or domain');
            }
        }

        $exists = Domain::where('name', $requestDomainName)->exists();
        if( !$exists )   
        {
            throw new InvalidArgumentException ('domainError 3: Invalid host or domain');
        }

        return true;
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

    // public function userHasRole( User $user )
    // {
    //     if( !$user )
    //     {
    //         throw new \InvalidArgumentException ('Invalid user, domain role validation failed');
    //     }
        
    //     return $this->userHasBackendRole($user) || $this->userHasFrontendRole($user);
    //     // if( $this->userHasFrontendRole( $user ) ) return true;
        
    //     // if( $this->userHasBackendRole($user) ) return true; //Do not allow super admin and admin to login to Frontend. Need to discuss! #TODO
    //     return false;
    // }

    public function userHasFrontendRole( User $user )
    {
        if( !$user )
        {
            throw new \InvalidArgumentException ('Invalid user or user does not exist');
        }

        $domainName = $this->getRequestDomainName();

        if( !$domainName )
        {
            throw new \InvalidArgumentException ('Domain is required to validate this request');
        }

        if(Domain::whereName($domainName)->exists())
        {
            $domain = Domain::whereName($domainName)->first();
            if($this->userHasProgramRolesInDomain($user, $domain))
            {
                return true;
            }
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

        $programRoles = $user->getProgramRolesByDomain( $domain );

        // dump($programRoles);

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
}
