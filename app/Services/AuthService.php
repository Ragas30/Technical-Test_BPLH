<?php

namespace App\Services;

use App\DTO\Auth\ChangePasswordDTO;
use App\DTO\Auth\LoginDTO;
use App\DTO\Auth\RegisterUserDTO;
use App\DTO\Auth\ResetPasswordDTO;
use App\DTO\Auth\UpdateProfileDTO;
use App\Enums\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const TOKEN_NAME = 'auth-token';

    private const REMEMBER_EXPIRY_DAYS = 30;

    private const DEFAULT_EXPIRY_DAYS = 1;

    private const RESET_STATUS_MESSAGES = [
        Password::INVALID_USER => 'Kami tidak dapat menemukan pengguna dengan email tersebut.',
        Password::INVALID_TOKEN => 'Token reset password ini tidak valid.',
        Password::RESET_THROTTLED => 'Terlalu banyak percobaan reset password. Silakan coba lagi nanti.',
    ];

    public function __construct(private readonly UserRepository $userRepository) {}

    public function register(RegisterUserDTO $dto): array
    {
        return DB::transaction(function () use ($dto): array {
            $user = $this->userRepository->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password,
                'is_active' => true,
            ]);

            $user->assignRole(Role::Applicant);
            $user->load('roles', 'permissions');

            return $this->authPayload($user, remember: false);
        });
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        if (! $user->is_active) {
            throw new AuthenticationException('Akun Anda telah dinonaktifkan.');
        }

        $user->load('roles', 'permissions');

        return $this->authPayload($user, $dto->remember);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function updateProfile(User $user, UpdateProfileDTO $dto): User
    {
        return $this->userRepository->update($user->id, [
            'name' => $dto->name,
            'email' => $dto->email,
        ]);
    }

    public function changePassword(User $user, ChangePasswordDTO $dto): void
    {
        $this->userRepository->update($user->id, [
            'password' => $dto->newPassword,
        ]);
    }

    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(ResetPasswordDTO $dto): void
    {
        $status = Password::reset(
            ['email' => $dto->email, 'password' => $dto->password, 'token' => $dto->token],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [self::RESET_STATUS_MESSAGES[$status] ?? 'Reset password gagal. Silakan coba lagi.'],
            ]);
        }
    }

    private function authPayload(User $user, bool $remember): array
    {
        $expiresAt = now()->addDays($remember ? self::REMEMBER_EXPIRY_DAYS : self::DEFAULT_EXPIRY_DAYS);
        $token = $user->createToken(self::TOKEN_NAME, ['*'], $expiresAt);

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt,
        ];
    }
}
