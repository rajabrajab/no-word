<?php

use App\Http\Controllers\Api\{AuthController, CategoryController, CountryController, GameController, HelpingMethodController, PasswordController, PlayerAvatarController};
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
Route::post('resend-password-otp', [PasswordController::class, 'sendPasswordRestOtp']);

Route::get('categories', [CategoryController::class, 'index']);
Route::get('countries', [CountryController::class, 'index']);
Route::get('helping-methods', [HelpingMethodController::class, 'index']);
Route::get('player-avatars', [PlayerAvatarController::class, 'index']);

Route::post('games', [GameController::class, 'store']);
Route::get('games/board/{game}', [GameController::class, 'gameBoard']);
Route::post('teams/{team}/use-helping-method', [GameController::class, 'useHelpingMethod']);
