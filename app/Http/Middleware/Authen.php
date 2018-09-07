<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class Authen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
			
            $request->session()->flash('message', 'inactive');
            return redirect()->route('login');
          
          }
        return $next($request);
    }
}
