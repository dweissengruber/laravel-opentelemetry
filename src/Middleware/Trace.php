<?php

namespace SeanHood\LaravelOpenTelemetry\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\Trace\Span;
use OpenTelemetry\Trace\Tracer;

/**
 * Trace an incoming HTTP request
 */
class Trace
{
    /**
     * @var Tracer $tracer OpenTelemetry Tracer
     */
    private $tracer;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $span = $this->tracer->startAndActivateSpan('http_'.$request->method());

        $span->setAttribute('request.path', $request->path())
             ->setAttribute('request.url', $request->fullUrl())
             ->setAttribute('request.method', $request->method())
             ->setAttribute('request.secure', $request->secure())
             ->setAttribute('request.ip', $request->ip())
             ->setAttribute('request.ua', $request->userAgent());

        $this->tagUser($request, $span);

        $response = $next($request);

        $span->setAttribute('response.status', $response->status());
        $this->tracer->endActiveSpan();

        return $response;
    }

    private function tagUser(Request $request, Span $span)
    {
        if($request->user()) {
            $span->setAttribute('request.user', $request->user()->email);
        }
    }
}
