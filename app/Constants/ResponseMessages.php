<?php

namespace App\Constants;

/**
 * Translation keys for the messages the API puts in its response envelope.
 *
 * These are keys, not sentences: the response macros translate whatever they are
 * given, so the client is answered in its own language. The wording lives in
 * lang/en/api.php and lang/ar/api.php, and a key must exist in both.
 */
class ResponseMessages
{
    public const INDEX_SUCCESS = 'api.index_success';

    public const SHOW_SUCCESS = 'api.show_success';

    public const CREATE_SUCCESS = 'api.create_success';

    public const STORE_SUCCESS = 'api.store_success';

    public const EDIT_SUCCESS = 'api.edit_success';

    public const UPDATE_SUCCESS = 'api.update_success';

    public const DELETE_SUCCESS = 'api.delete_success';

    public const GENERAL_SUCCESS = 'api.general_success';

    public const GENERAL_FAILURE = 'api.general_failure';

    public const NOT_FOUND = 'api.not_found';

    public const VALIDATION_FAILURE = 'api.validation_failure';

    public const UNAUTHORIZED = 'api.unauthorized';

    public const SESSION_EXPIRED = 'api.session_expired';

    public const APPLY_COUPON_SUCCESS = 'api.package.coupon_applied';

    /**
     * Translate a message for the response envelope.
     *
     * A known key becomes the translated sentence; anything else — a message from
     * an exception, a validation error already translated by the validator — is
     * returned as it came in.
     *
     * @param  array<string, mixed>  $replace
     */
    public static function translate(mixed $message, array $replace = []): string
    {
        if (! is_string($message) || $message === '') {
            return '';
        }

        $translated = __($message, $replace);

        return is_string($translated) ? $translated : $message;
    }
}
