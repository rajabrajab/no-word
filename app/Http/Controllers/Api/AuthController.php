<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $response = $this->authService->register($data);

        if (!$response->status()) {
            return response()->sendError(401, $response->message());
        }

        $user = $response->data()['user'];

        unset($user['profile_image_path']);

        return response()->sendResponse(['user' => $user], $response->message());
    }

    public function userLogin(LoginRequest $request)
    {
        $response = $this->authService->login($request);

        if (!$response->status()) {
           return response()->sendError(401, $response->message());
        }

        $user = $response->data()['user'];

        return response()->sendResponse([
            'token' => $response->data()['token'],
            'user' => new UserResource($user)
        ] , $response->message());
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request);

        return response()->sendResponse([],'Logout successfully.');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'nickname' => 'sometimes|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if (!empty($data['profile_image'])) {
            $data['profile_image'] = $data['profile_image']->store('profile_images', 'public');
        }

        $user = $this->authService->updateProfile($data);

        return response()->sendResponse([
            'user' => new UserResource($user)
        ] , ResponseMessages::UPDATE_SUCCESS);
    }

    public function verifyOtpAndRegister(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'otp' => 'required|numeric',
        ]);

        $response = $this->authService->verifyOtpAndRegister($request);

        if ($response->status()) {
            return response()->sendResponse([
                'token' => $response->data()['token'],
                'user' => (new UserResource($response->data()['user']))->withHidden(['permissions','is_first_login'])
            ] , $response->message());
        }

        return response()->sendError(401, $response->message());
    }

    public function resendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required'
        ]);

        $response = $this->authService->resendCode($data['email']);

        if ($response->status()) {

           return response()->sendResponse([], $response->message());
        }

        return response()->sendError(401, $response->message());
    }
}
