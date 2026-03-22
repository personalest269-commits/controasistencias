<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class LangSwitcher {

    private $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     * Set local language
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        // Only allow two languages: English (en) and Spanish (es).
        if ($request->headers->has('lang')) {
            $requested = (string) $request->header('lang');

            $allowed = ['en', 'es'];
            try {
                if (Schema::hasTable('pg_idiomas')) {
                    $allowed = DB::table('pg_idiomas')->where('activo', 1)->pluck('codigo')->map(fn ($c) => (string) $c)->all();
                    $allowed = array_values(array_unique(array_filter($allowed)));
                    if (empty($allowed)) {
                        $allowed = ['en', 'es'];
                    }
                }
            } catch (\Throwable $e) {
                $allowed = ['en', 'es'];
            }

            if (in_array($requested, $allowed, true)) {
                $this->app->setLocale($requested);
            }
        }

        $response = $next($request);
        $headers=[
            "Pragma" => "no-cache",
            "Expires" => "Fri, 01 Jan 1990 00:00:00 GMT",
            "Cache-Control" => "no-store,no-cache, must-revalidate, , max-age=0",
        ];
        foreach ($headers as $headerKey=>$headerVal){
            $response->headers->set($headerKey,$headerVal);
        }
        return $response;
    }
}
