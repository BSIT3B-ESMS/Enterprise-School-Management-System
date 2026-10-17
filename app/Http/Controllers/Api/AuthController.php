<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\AuditLogger;
use App\Services\TwoFactorService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * JWT authentication for both portals (NFR-01). Every endpoint answers in
 * the { data } / { message, errors } shape the rest of the API uses.
 */
class AuthController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactor,
        private AuditLogger $audit,
    ) {}

    public function register(RegisterRequest $request, ApplicationService $applications): JsonResponse
    {
        $user = $applications->register($request->validated());

        return $this->tokenResponse($user, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => "The email or password doesn't match our records."]);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'This account is disabled. Contact the admission office.']);
        }
        if (! $this->allowedOnPortal($user, $request->string('portal'))) {
            throw ValidationException::withMessages(['email' => "This account can't sign in here."]);
        }

        if ($this->twoFactor->required($user)) {
            return response()->json(['data' => [
                'two_factor_required' => true,
                'method' => $this->twoFactor->method($user),
                'challenge' => $this->twoFactor->challenge($user),
            ]]);
        }

        return $this->completeLogin($user);
    }

    /** Second step of login (UI pending: there is no code screen yet). */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->twoFactor->verify($data['challenge'], $data['code']);
        if (! $user) {
            throw ValidationException::withMessages(['code' => 'The code is wrong or has expired.']);
        }

        return $this->completeLogin($user);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userPayload($request->user())]);
    }

    public function refresh(): JsonResponse
    {
        return $this->tokenPayload(auth('api')->refresh(), auth('api')->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->primaryRole()?->isStaff()) {
            $this->audit->record('auth.logout', 'Signed out of the admission office panel');
        }
        auth('api')->logout();

        return response()->json(['message' => "You've been signed out."]);
    }

    /** Same answer whether or not the account exists (no account enumeration). */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => $data['email']]);

        return response()->json(['message' => "If an account exists for {$data['email']}, a reset link is on its way."]);
    }

    /** UI pending: the portal has no "new password" screen yet. */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => 'Your password has been changed. You can sign in now.']);
    }

    private function completeLogin(User $user): JsonResponse
    {
        $user->forceFill(['last_login_at' => now()])->save();
        if ($user->primaryRole()?->isStaff()) {
            $this->audit->record('auth.login', 'Signed in to the admission office panel', actor: $user);
        }

        return $this->tokenResponse($user);
    }

    private function allowedOnPortal(User $user, string $portal): bool
    {
        $role = $user->primaryRole();

        return $portal === 'applicant' ? $role === Role::Applicant : (bool) $role?->isStaff();
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        return $this->tokenPayload(auth('api')->login($user), $user, $status);
    }

    private function tokenPayload(string $token, User $user, int $status = 200): JsonResponse
    {
        return response()->json(['data' => [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $this->userPayload($user),
        ]], $status);
    }

    private function userPayload(User $user): array
    {
        $role = $user->primaryRole();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'initials' => $user->initials(),
            'email' => $user->email,
            'role' => $role?->value,
            'role_label' => $role?->label(),
            'two_factor_enabled' => $user->hasTwoFactor(),
        ];
    }
}
