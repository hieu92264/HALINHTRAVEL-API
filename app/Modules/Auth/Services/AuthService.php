<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Models\User;
use Illuminate\Auth\AuthenticationException;
use LogicException;
use Tymon\JWTAuth\JWTGuard;

class AuthService implements AuthServiceInterface
{
    /**
     * Authenticate an active user and issue a JWT access token.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{access_token: string, token_type: string, expires_in: int}
     *
     * @throws AuthenticationException
     */
    public function login(array $credentials): array
    {
        $guard = $this->guard();
        $rememberMe = (bool) ($credentials['remember_me'] ?? false);

        unset($credentials['remember_me']);

        $ttl = $rememberMe ? 60 * 24 * 7 : (int) config('jwt.ttl');
        $guard->factory()->setTTL($ttl);

        $credentials['is_active'] = true;

        if (! $token = $guard->attempt($credentials)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $user = $guard->user();

        if (! $user instanceof User) {
            throw new AuthenticationException();
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return $this->respondWithToken($guard, $token);
    }

    protected function respondWithToken(JWTGuard $guard, string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     */
    public function me(): array
    {
        $user = $this->guard()->user();

        if (! $user instanceof User) {
            throw new AuthenticationException();
        }

        return $this->userPayload($user);
    }

    /**
     * @return array{access_token: string, token_type: string, expires_in: int}
     */
    public function refresh(): array
    {
        $guard = $this->guard();

        return $this->respondWithToken($guard, $guard->refresh());
    }

    /** @return void */
    public function logout(): void
    {
        $this->guard()->logout();
    }

    private function guard(): JWTGuard
    {
        $guard = auth()->guard('api');

        if (! $guard instanceof JWTGuard) {
            throw new LogicException('The api authentication guard must use JWTGuard.');
        }

        return $guard;
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'user_name' => $user->user_name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()
                ->pluck('name')
                ->values()
                ->all(),
        ];
    }
}
