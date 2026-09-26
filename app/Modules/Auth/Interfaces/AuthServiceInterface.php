<?php

namespace App\Modules\Auth\Interfaces;

interface AuthServiceInterface
{
    public function login(array $credentials): array;
    public function me(): array;
    public function refresh(): array;
    public function logout(): void;
}
