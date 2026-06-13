<?php

namespace Core;

class Request
{
    public string $method;
    public string $uri;
    public array  $params = [];   // route params {id}
    private array $body   = [];
    private array $query  = [];
    private array $files  = [];

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query  = $_GET;
        $this->files  = $_FILES;
        $this->params = [];

        $this->parseBody();
    }

    private function parseBody(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $this->body = json_decode($raw, true) ?? [];
        } elseif ($this->method === 'POST') {
            $this->body = $_POST;
        } else {
            // PUT/PATCH with form data
            parse_str(file_get_contents('php://input'), $this->body);
        }
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isApi(): bool
    {
        $base = parse_url(APP_URL, PHP_URL_PATH) ?? '';
        $uri  = ($base && str_starts_with($this->uri, $base))
            ? substr($this->uri, strlen($base))
            : $this->uri;
        return str_starts_with($uri, '/api/');
    }

    public function header(string $key): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? null;
    }

    public function bearerToken(): ?string
    {
        // Jeton transmis exclusivement via l'en-tête Authorization (pas de
        // cookie), ce qui évite toute soumission cross-site de type CSRF.
        $auth = $_SERVER['HTTP_AUTHORIZATION']
             ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
             ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    public function withParams(array $params): static
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }
}
