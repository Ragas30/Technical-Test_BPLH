<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const USER_EXAMPLE = [
        'id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        'name' => 'Admin BPLH',
        'email' => 'admin@docflow.test',
        'is_active' => true,
        'roles' => ['admin'],
        'permissions' => ['dashboard.view', 'user.view_any', 'project.view_any'],
        'email_verified_at' => '2026-08-01T09:00:00+07:00',
        'created_at' => '2026-07-01T09:00:00+07:00',
        'updated_at' => '2026-08-01T09:00:00+07:00',
        'deleted_at' => null,
    ];

    private const LOGIN_EXAMPLE = [
        'data' => [
            'token_type' => 'Bearer',
            'token' => '1|a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
            'expires_at' => '2026-08-15T09:00:00+07:00',
            'user' => [
                'id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                'name' => 'Admin BPLH',
                'email' => 'admin@docflow.test',
                'is_active' => true,
                'roles' => ['admin'],
                'permissions' => ['dashboard.view', 'user.view_any'],
                'email_verified_at' => '2026-08-01T09:00:00+07:00',
                'created_at' => '2026-07-01T09:00:00+07:00',
                'updated_at' => '2026-08-01T09:00:00+07:00',
                'deleted_at' => null,
            ],
        ],
    ];

    public function __construct(private readonly AuthService $authService) {}

    #[Response(201, 'Pengguna berhasil didaftarkan.', examples: [self::LOGIN_EXAMPLE])]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->toDTO());

        return AuthResource::make($result)->response()->setStatusCode(201);
    }

    #[Response(200, 'Login berhasil. Gunakan token pada header Authorization: Bearer <token>.', examples: [self::LOGIN_EXAMPLE])]
    public function login(LoginRequest $request): AuthResource
    {
        return AuthResource::make($this->authService->login($request->toDTO()));
    }

    #[Response(200, 'Logout berhasil.', examples: [['message' => 'Logout berhasil.']])]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logout berhasil.']);
    }

    #[Response(200, 'Profil pengguna yang sedang login.', examples: [['data' => self::USER_EXAMPLE]])]
    public function profile(Request $request): UserResource
    {
        $user = $request->user()->load('roles', 'permissions');

        $this->authorize('view', $user);

        return UserResource::make($user);
    }

    #[Response(200, 'Profil pengguna berhasil diperbarui.', examples: [['data' => self::USER_EXAMPLE]])]
    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        $this->authorize('update', $user);

        $user = $this->authService->updateProfile($user, $request->toDTO());

        return UserResource::make($user->load('roles', 'permissions'));
    }

    #[Response(200, 'Password berhasil diubah.', examples: [['message' => 'Password berhasil diubah.']])]
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('changePassword', $user);

        $this->authService->changePassword($user, $request->toDTO());

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    #[Response(200, 'Link reset password berhasil dikirim.', examples: [['message' => 'Link reset password telah dikirim ke email Anda.']])]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendResetLink($request->string('email')->toString());

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => ['Terlalu banyak permintaan reset password. Silakan coba lagi nanti.'],
            ]);
        }

        return response()->json(['message' => 'Link reset password telah dikirim ke email Anda.']);
    }

    #[Response(200, 'Password berhasil direset.', examples: [['message' => 'Password berhasil direset. Silakan login kembali.']])]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->toDTO());

        return response()->json(['message' => 'Password berhasil direset. Silakan login kembali.']);
    }
}
