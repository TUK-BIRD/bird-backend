<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TransformCase
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldApply($request)) {
            return $next($request);
        }

        if (config('snake-to-camel.convert_request', false)) {
            $this->convertRequest($request);
        }

        $response = $next($request);

        if (config('snake-to-camel.convert_response', false)) {
            $this->convertResponse($response);
        }

        return $response;
    }

    private function shouldApply(Request $request): bool
    {
        $applyTo = config('snake-to-camel.apply_to', 'api');

        if ($applyTo === 'all') {
            return true;
        }

        if ($applyTo === 'api') {
            return $request->is('api/*');
        }

        return true;
    }

    private function convertRequest(Request $request): void
    {
        $request->query->replace(
            $this->convertArrayKeys($request->query->all(), 'snake')
        );

        $request->request->replace(
            $this->convertArrayKeys($request->request->all(), 'snake')
        );
    }

    private function convertResponse(Response $response): void
    {
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            $response->setData($this->convertValue($data, 'camel'));
            return;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'application/json')) {
            return;
        }

        $decoded = json_decode($response->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        $response->setContent(json_encode(
            $this->convertValue($decoded, 'camel'),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    private function convertValue(mixed $value, string $mode): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        return $this->convertArrayKeys($value, $mode);
    }

    private function convertArrayKeys(array $data, string $mode): array
    {
        $converted = [];

        foreach ($data as $key => $value) {
            $newKey = is_string($key)
                ? ($mode === 'camel' ? Str::camel($key) : Str::snake($key))
                : $key;

            if (is_array($value)) {
                $converted[$newKey] = $this->convertArrayKeys($value, $mode);
                continue;
            }

            $converted[$newKey] = $value;
        }

        return $converted;
    }
}
