<?php

namespace Tests\Feature;

use App\Constants\ResponseMessages;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_api_answers_in_arabic_by_default(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $this->assertSame(__('api.index_success', [], 'ar'), $response->json('message'));
        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $response->json('message'));
    }

    public function test_a_client_can_ask_for_english(): void
    {
        $response = $this->getJson('/api/categories', ['X-Locale' => 'en']);

        $response->assertOk();
        $this->assertSame('Data retrieved successfully.', $response->json('message'));
    }

    public function test_a_regional_locale_still_resolves(): void
    {
        $response = $this->getJson('/api/categories', ['X-Locale' => 'ar-SA']);

        $this->assertSame(__('api.index_success', [], 'ar'), $response->json('message'));
    }

    public function test_an_unsupported_language_falls_back_to_arabic(): void
    {
        $response = $this->getJson('/api/categories', ['X-Locale' => 'fr-FR']);

        $this->assertSame(__('api.index_success', [], 'ar'), $response->json('message'));
    }

    public function test_the_phone_language_does_not_override_the_arabic_default(): void
    {
        // An Arabic app on an English handset still answers in Arabic; only a
        // deliberate X-Locale or the user's own stored language switches it.
        $response = $this->getJson('/api/categories', ['Accept-Language' => 'en-US,en;q=0.9']);

        $this->assertSame(__('api.index_success', [], 'ar'), $response->json('message'));
    }

    public function test_a_signed_in_user_is_answered_in_their_own_language(): void
    {
        $user = User::factory()->create(['language' => 'en']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/packages');

        $response->assertOk();
        $this->assertSame('Data retrieved successfully.', $response->json('message'));
    }

    public function test_validation_errors_are_arabic(): void
    {
        $response = $this->postJson('/api/login', []);

        $message = $response->json('message');

        $this->assertNotNull($message);
        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $message);
        // The field name is translated too, not left as the column name.
        $this->assertStringNotContainsString('email', $message);
    }

    public function test_a_missing_route_reports_in_arabic(): void
    {
        $response = $this->getJson('/api/there-is-no-such-endpoint');

        $response->assertStatus(404);
        $this->assertSame(__('api.not_found', [], 'ar'), $response->json('message'));
    }

    public function test_an_expired_session_reports_in_arabic(): void
    {
        $response = $this->getJson('/api/packages');

        $this->assertSame(440, $response->json('code'));
        $this->assertSame(__('api.session_expired', [], 'ar'), $response->json('message'));
    }

    public function test_the_response_envelope_keys_are_unchanged(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertJsonStructure(['state', 'code', 'message', 'data']);
        $this->assertTrue($response->json('state'));
        // HttpCodes holds strings and mobile clients read them as such — unchanged.
        $this->assertSame('200', $response->json('code'));
    }

    public function test_every_arabic_api_key_has_an_english_twin(): void
    {
        $flatten = function (array $lines, string $prefix = '') use (&$flatten): array {
            $keys = [];

            foreach ($lines as $key => $value) {
                $keys = is_array($value)
                    ? array_merge($keys, $flatten($value, $prefix.$key.'.'))
                    : array_merge($keys, [$prefix.$key]);
            }

            return $keys;
        };

        $arabic = $flatten(require lang_path('ar/api.php'));
        $english = $flatten(require lang_path('en/api.php'));

        $this->assertSame([], array_diff($arabic, $english), 'Arabic keys missing from lang/en/api.php');
        $this->assertSame([], array_diff($english, $arabic), 'English keys missing from lang/ar/api.php');
    }

    public function test_response_message_constants_all_resolve_to_a_translation(): void
    {
        $reflection = new \ReflectionClass(ResponseMessages::class);

        foreach ($reflection->getConstants() as $name => $key) {
            foreach (['ar', 'en'] as $locale) {
                $this->assertNotSame(
                    $key,
                    __($key, [], $locale),
                    "ResponseMessages::{$name} has no {$locale} translation for '{$key}'.",
                );
            }
        }
    }

    public function test_a_plain_sentence_is_passed_through_untouched(): void
    {
        // Messages that are not keys — an exception's own text, say — must survive.
        $this->assertSame('Something went sideways', ResponseMessages::translate('Something went sideways'));
        $this->assertSame('', ResponseMessages::translate(null));
    }
}
