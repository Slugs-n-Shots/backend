<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->environment(['local', 'testing']) || !config('request_logger.enabled', true)) {
            return $next($request);
        }

        $uuid = Str::uuid();
        $data = [
            'Request Method' => $request->method(),
            'Request Path' => $request->path(),
            'Requesting User' => $this->mask($request->user() ? $request->user()->toArray() : "none"),
            'Request Params' => $this->mask($request->all()),
            'Request IP' => $request->ip(),
            'Request URI' => $request->getRequestUri(),
            'lang' => $request->getLanguages(),
            'Origin' => $request->header('host'),
        ];

        // $header = $request->headers->all();

        $header = collect($request->header())->transform(function ($item) {
            return $item[0];
        })->all();

        $header = $this->mask($header);

//        Log::channel('requests')->info(json_encode($request->headers->all(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        Log::channel('requests')->info($uuid . ":\n" . json_encode(['data' => $data, 'header' => $header], JSON_UNESCAPED_SLASHES| JSON_PRETTY_PRINT));

        $ret = $next($request);

        Log::channel('requests')->info($uuid . ":\n" . json_encode($this->responseData($ret), JSON_UNESCAPED_SLASHES| JSON_PRETTY_PRINT));

        return $ret;
    }

    private function responseData(Response $response): array
    {
        $content = $response->getContent();
        $decoded = json_decode($content, true);

        return [
            'status' => $response->getStatusCode(),
            'content' => $this->mask(json_last_error() === JSON_ERROR_NONE ? $decoded : $content),
        ];
    }

    private function mask(mixed $value): mixed
    {
        if (!config('request_logger.mask_sensitive', true)) {
            return $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $sensitiveKeys = array_map(
            static fn (string $key): string => strtolower($key),
            config('request_logger.sensitive_keys', [])
        );

        $masked = [];
        foreach ($value as $key => $item) {
            $normalizedKey = strtolower((string) $key);
            $masked[$key] = in_array($normalizedKey, $sensitiveKeys, true)
                ? '[masked]'
                : $this->mask($item);
        }

        return $masked;
    }
}
