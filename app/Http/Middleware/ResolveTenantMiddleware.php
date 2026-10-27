<?php

namespace App\Http\Middleware;

use App\Repositories\TenantRepository;
use Ars\Responder\Facades\Responder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantMiddleware
{
    public function __construct(private TenantRepository $tenants)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = strtolower(trim((string) $request->header('X-Subdomain', '')));

        if ($subdomain === '') {
            return Responder::setMessage(__('tenant.subdomain_required'))->badRequest();
        }

        $tenant = $this->tenants->findBySubdomain($subdomain);

        if (!$tenant) {
            return Responder::setMessage(__('tenant.not_found'))->notFound();
        }

        set_tenant($tenant);

        return $next($request);
    }
}
