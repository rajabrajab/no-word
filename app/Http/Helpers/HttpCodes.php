<?php

/*
*    Developer : Abd alwahed rajab
*/

namespace App\Http\Helpers;

class HttpCodes
{
    const OK = '200';
    const ERROR_WITH_REASON = '205';
    const STRIPE_PAYMENT_ERROR = '306';
    const UNAUTHENTICATED = '401';
    const VALIDATION_ERROR = '403';
    const NOT_FOUND = '404';
    const QUERY_ERROR = '406';
    const NOT_ALLOWED = '405';
    const FATAL_ERROR = '500';
    const MODEL_NOT_FOUND = '900';
    const VIEW_ERROR = '970';
    const TOKEN_NOT_FOUND = '980';
}
