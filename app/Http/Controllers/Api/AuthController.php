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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->toDTO());

        return AuthResource::make($result)->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): AuthResource
    {
        return AuthResource::make($this->authService->login($request->toDTO()));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function profile(Request $request): UserResource
    {
        $user = $request->user()->load('roles', 'permissions');

        $this->authorize('view', $user);

        return UserResource::make($user);
    }

    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        $this->authorize('update', $user);

        $user = $this->authService->updateProfile($user, $request->toDTO());

        return UserResource::make($user->load('roles', 'permissions'));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('changePassword', $user);

        $this->authService->changePassword($user, $request->toDTO());

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

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

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->toDTO());

        return response()->json(['message' => 'Password berhasil direset. Silakan login kembali.']);
    }
}
