<?php

namespace Core;
use Closure;

class Middleware
{
  public function handle($request, Closure $next)
    {
        return $next($request);
    }
    public function library($name)
    {
        ##
    }
    public function middleware($name)
    {
        ##
    }
}
