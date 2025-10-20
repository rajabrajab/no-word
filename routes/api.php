<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtpAndRegister']);
Route::post('resend-otp', [AuthController::class, 'resendOtp']);

Route::post('login', [AuthController::class, 'userLogin']);

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::put('profile/update', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');

Route::post('send-otp', [PasswordController::class, 'sendPasswordRestOtp']);
Route::post('confirm-otp', [PasswordController::class, 'confirmPasswordOtp']);
Route::post('password-reset', [PasswordController::class, 'passwordRest']);
Route::post('resend-password-otp', [PasswordController::class, 'passwordRest']);
