<?php

namespace App\Infrastructure\Middleware;

use App\Modules\IdentityAccess\Models\Location;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocationScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $param = $request->route('location');
        $locationId = $param instanceof Location ? $param->id : (int) $param;

        if (! $user->isGlobal() && ! $user->locationScopes->contains('id', $locationId)) {
            abort(403, 'Akses ke lokasi ini ditolak.');
        }

        return $next($request);
    }
}
