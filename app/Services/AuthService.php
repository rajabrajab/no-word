<?php

namespace App\Services;

use App\Constants\ResponseMessages;
use App\Helpers\PhoneHelper;
use App\Mail\OtpMail;
use App\Models\User;
use App\Models\VendorRegistrationRequest;
use App\Traits\ImageUploadTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Exception;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AuthService
{
    use ImageUploadTrait;

    protected $status = false;
    protected $message = '';
    protected $data = [];


    public function register($data)
    {
        if (Cache::has($data['email'])) {
            $this->status = false;
            $this->message = 'OTP already sent. Please check your email.';
            return $this;
        }

        $otp = rand(100000, 999999);

        $imgPath = $data['profile_image'] ?? null;
        $imgUrl  = $imgPath ? Storage::disk('public')->url($imgPath) : null;

        $user = [
            'name' => $data['name'],
            'email' => $data['email'],
            'type' => 'user',
            'number' => $data['number'],
            'country_code' => $data['country_code'],
            'iso_code' => $data['iso_code'],
            'normalized' => $data['normalized'],
            'profile_image'      => $imgUrl ?? null,
            'profile_image_path'  => $imgPath ?? null,
        ];

        $hashedPassword = Hash::make($data['password']);

        try{
            Mail::to($data['email'])->send(new OtpMail($otp));
        }
        catch (Exception $e) {
            $this->status = false;
            $this->message = 'Failed to send OTP. Error: ' . $e->getMessage();
            return $this;
        }

        Cache::put($data['email'], [
            'code' => $otp,
            'user_data' => array_merge($user, ['password' => $hashedPassword])
        ], now()->addMinutes(10));

        $this->status = true;
        $this->message = 'OTP sent to your email.';
        $this->data['user'] = $user;

        return $this;
    }

    public function login($request)
    {
        $user = null;
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        if(!$user){
            $this->status = false;
            $this->message = 'User not found.';
            return $this;
        }

        if (!$user || !Hash::check($password, $user->password)) {
            $this->status = false;
            $this->message = ResponseMessages::UNAUTHORIZED;
            return $this;
        }

        $token = $this->loginAndCreateToken($user,$request);

        $this->status = true;
        $this->message = 'Logged in successfully.';
        $this->data['token'] = $token;
        $this->data['user'] = $user;

        return $this;
    }

    public function logout()
    {
        $user = Auth::user();

        return $user->tokens()->delete();
    }

    public function updateProfile(array $data)
    {
        $user = Auth::user();

        if (array_key_exists('profile_image', $data)) {
            if ($data['profile_image'] === null) {
                if ($user->profile_image) {
                    $this->deleteImage($user->profile_image);
                }
                $data['profile_image'] = null;
            } elseif ($data['profile_image'] instanceof \Illuminate\Http\UploadedFile) {
                if ($user->profile_image) {
                    $this->deleteImage($user->profile_image);
                }
                $data['profile_image'] = $this->uploadImage(
                    $data['profile_image'],
                    'profile-images'
                );
            }
        }

        $user->update($data);
        return $user;
    }


    public function sendPasswordRestOtp($email)
    {
        $otp = rand(100000, 999999);

        try{
            Mail::to($email)->send(new OtpMail($otp));


           DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
            ]);

            $this->status = true;
            $this->message = 'OTP sent successfully' ;

            return $this;
        }
        catch (Exception $e) {
            $this->status = false;
            $this->message = 'Failed to send OTP. Error: ' . $e->getMessage();
        }

        return $this;
    }

    public function confirmPasswordOtp($data)
    {
        $record = DB::table('password_resets')->where('email',$data['email'])->first();

        if($record && $record->otp == $data['otp']){
            if(Carbon::parse($record->expires_at)->lt(Carbon::now())) {

                $this->status = false;
                $this->message = 'expired OTP.';

                return $this;
            }
            $this->status = true;
            $this->message = 'OTP confirmed.';
        }else{
            $this->status = false;
            $this->message = 'Invalid OTP.';
        }

        return $this;

    }

    public function updatePassword($data,$request)
    {

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $user->password = Hash::make($data['password']);
            $user->save();

            $user->tokens()->delete();

            $token = $this->loginAndCreateToken($user,$request);

            DB::table('password_resets')->where('email', $data['email'])->delete();

            $this->status = true;
            $this->message = 'Password updated successfully.';
            $this->data['token'] = $token;
            $this->data['user'] = $user;
        } else {
            $this->message = 'User not found.';
        }

        return $this;
    }

    public function verifyOtpAndRegister($request)
    {
        $tempData = Cache::get($request['email']);

        if (!$tempData) {
            $this->status = false;
            $this->message = 'Invalid or expired temporary key.';
            return $this;
        }

        $storedOtp = $tempData['code'];

        if ($request['otp'] != $storedOtp) {
            $this->status = false;
            $this->message = 'Invalid or expired OTP.';
            return $this;
        }

        $tempData;

        $user = User::create([
            'name' => $tempData['user_data']['name'],
            'email' => $tempData['user_data']['email'],
            'password' => $tempData['user_data']['password'],
            'type' => $tempData['user_data']['type'],
            'number' => $tempData['user_data']['number'] ?? null,
            'country_code' => $tempData['user_data']['country_code'] ?? null,
            'iso_code' => $tempData['user_data']['iso_code'] ?? null,
            'normalized' => $tempData['user_data']['normalized'] ?? null,
            'profile_image' => $tempData['user_data']['profile_image_path'] ?? null,
        ]);

        $user->joined_at = now();
        $user->email_verified_at = now();
        $user->save();

        $fcm_token = $request->header('x-token');
        if($fcm_token){
            $user->updateDeviceToken($fcm_token);
        }

        $token = $this->loginAndCreateToken($user,$request);

        Cache::forget($request['email']);

        $this->status = true;
        $this->message = "User Registerd succesfully !";
        $this->data['token'] = $token;

        if ($user->profile_image) {
            $user->profile_image = url('storage/' . $user->profile_image);
        }

        $this->data['user'] = $user;

        return $this;

    }

    private function loginAndCreateToken($user,$request = null)
    {
        if($token = $request->header('x-token')){
            $user->updateDeviceToken($token);
        }

        // Auth::login($user);

        $token = $user->createToken('API Token')->plainTextToken;

        return $token;
    }

    public function resendRegisterCode($email)
    {
        $newOtp = rand(100000, 999999);

        $cachedData = Cache::get($email);

        if (!$cachedData) {
            $this->status = false;
            $this->message = 'No registration data found for this email.';
            return $this;
        }

        $cachedData['code'] = $newOtp;
        Cache::put($email, $cachedData, now()->addMinutes(10));

        try {
            Mail::to($email)->send(new OtpMail($newOtp));

            $this->status = true;
            $this->message = 'New OTP sent to your email.';
        } catch (Exception $e) {
            $this->status = false;
            $this->message = 'Failed to send OTP. Error: ' . $e->getMessage();
        }

        return $this;
    }

     public function resendCodeForReset($email)
    {
        $existingOtp = DB::table('password_resets')
                        ->where('email', $email)
                        ->first();

        if ($existingOtp && now()->lt($existingOtp->expires_at)) {
            $cooldownEnd = Carbon::parse($existingOtp->created_at)->addMinutes(2);

            if (now()->lt($cooldownEnd)) {
                $remainingSeconds = now()->diffInSeconds($cooldownEnd);
                $this->status = false;
                $this->message = 'Please wait' . $remainingSeconds .'seconds before requesting a new code.';
                return $this;
            }
        }

        return $this->sendPasswordRestOtp($email);
    }

    public function refreshToken($request)
    {
        $user = Auth::user();

        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        $token = $this->loginAndCreateToken($user, $request);

        $this->status = true;
        $this->message = 'Token refreshed successfully.' ;

        $this->data['token'] = $token;
        $this->data['user'] = $user;

        return $this;

    }

    public function status()
    {
        return $this->status;
    }

    public function message()
    {
        return $this->message;
    }

    public function data()
    {
        return $this->data;
    }
}
