<?php

namespace App\Helpers;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FCM
{

    private $to;
    private $message;
    private $title;
    private $notification_type;
    private $userId;
    private $response;

    public function to($to)
    {
        $this->to = $to;
        return $this;
    }

    public function userId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function type($notification_type)
    {
        $this->notification_type = $notification_type;
        return $this;
    }

    public function message($message, $title = 'message')
    {
        $this->message = $message;
        $this->title = $title;
        return $this;
    }
    public function send()
    {

        $client = new GoogleClient();
        $client->setAuthConfig(Storage::path('json/pitstop-2ce33-firebase-adminsdk-fbsvc-6aa70dd87e.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->fetchAccessTokenWithAssertion();
        $token = $client->getAccessToken();
        $accessToken = $token['access_token'] ?? null;

        if (!$accessToken) {
            $this->response = ['error' => 'Unable to get access token.'];
            return $this;
        }

        $projectId = json_decode(file_get_contents(Storage::path('json/pitstop-2ce33-firebase-adminsdk-fbsvc-6aa70dd87e.json')), true)['project_id'];

        $payload = [
            'message' => [
                'token' => $this->to,
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->message,
                ],
            ],
        ];

        $this->response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload)
            ->json();

        return $this;
    }

    public function response()
    {
        return $this->response;
    }
}
