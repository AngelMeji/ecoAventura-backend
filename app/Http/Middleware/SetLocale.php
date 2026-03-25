<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language');

        if ($locale) {
            // Se puede enviar "es", "en", o combinaciones complejas
            // Extraemos solo el código de 2 letras
            $lang = substr($locale, 0, 2);
            
            $supportedLanguages = ['en', 'es'];
            
            if (in_array($lang, $supportedLanguages)) {
                App::setLocale($lang);
            }
        }

        return $next($request);
    }
}
