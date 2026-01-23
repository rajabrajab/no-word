<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AuthRepository;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Services\AuthService;

class PasswordController extends Controller
{

    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }


    public function sendPasswordRestOtp(Request $request)
    {
        $request->validate([
          'email' => 'required|email|exists:users,email'
        ]);

        $response = $this->authService->sendPasswordRestOtp($request->email);

        if ($response->status()) {

           return response()->sendResponse([], $response->message());
        }

        return response()->sendError(401, $response->message());
    }

    public function confirmPasswordOtp(Request $request)
    {
        $data = $request->validate([
          'email' => 'required|email|exists:users,email',
          'otp' => 'required|string',
        ]);

        $response = $this->authService->confirmPasswordOtp($data);

        if($response->status()){
            return response()->sendResponse([], $response->message());
        }

        return response()->sendError(401, $response->message());

    }

    public function passwordRest(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        $response = $this->authService->updatePassword($data,$request);

        if ($response->status()) {
            return response()->sendResponse([
                'token' => $response->data()['token'],
                'user' => new UserResource($response->data()['user'])
            ] , $response->message());
        }

        return response()->sendError(401,$response->message());

    }
}
