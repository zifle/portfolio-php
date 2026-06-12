<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class CopyrightInfo
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('copyrightHolder', config('portfolio.copyright.holder'));

        $copyFrom = config('portfolio.copyright.from_year', date('Y'));
        $copy = date('Y');
        if ($copyFrom !== $copy) {
            $copy = $copyFrom.'-'.$copy;
        }
        View::share('copyrightYear', $copy);

        return $next($request);
    }
}
