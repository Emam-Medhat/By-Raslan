<?php

namespace App\Shared\Support;

/**
 * Explicit result object that replaces the legacy `{ ok, error }` contract
 * returned by the Next.js server actions.
 *
 * Services return Result; controllers translate it to HTTP / redirects.
 */
class Result
{
    public function __construct(
        public readonly bool $ok,
        public readonly mixed $data = null,
        public readonly ?string $error = null,
    ) {
    }

    public static function success(mixed $data = null): self
    {
        return new self(true, $data);
    }

    public static function fail(string $error): self
    {
        return new self(false, null, $error);
    }
}