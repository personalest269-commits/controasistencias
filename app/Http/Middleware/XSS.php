<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class XSS
{
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            // Evita warnings/deprecations cuando el valor viene null u objetos
            if ($value === null || is_object($value)) {
                return;
            }
            if (is_string($value)) {
                $value = strip_tags($value);
                return;
            }
            // Números/booleanos/etc.
            $value = strip_tags((string) $value);
        });
        $request->merge($input);
        return $next($request);
    }
}
?>