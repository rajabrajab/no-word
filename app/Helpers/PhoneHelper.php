<?php

namespace App\Helpers;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class PhoneHelper
{
    private static $regions = ['SA'];

    public static function normalize(string $phone): ?string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $defaultRegion = null;
        if (preg_match('/^01\d+$/', $phone)) {
            $defaultRegion = 'SA';
        }

        if ($defaultRegion) {
            try {
                $numberProto = $phoneUtil->parse($phone, $defaultRegion);
                if ($phoneUtil->isValidNumber($numberProto)) {
                    return $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
                }
            } catch (NumberParseException $e) {

            }
        }


        foreach (self::$regions as $region) {
            try {
                $numberProto = $phoneUtil->parse($phone, $region);
                if ($phoneUtil->isValidNumber($numberProto)) {
                    return $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
                }
            } catch (NumberParseException $e) {
                continue;
            }
        }

        return null;
    }

    public static function isValid(string $phone, string $defaultRegion = 'SA'): bool
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $numberProto = $phoneUtil->parse($phone, $defaultRegion);
            return $phoneUtil->isValidNumber($numberProto);
        } catch (NumberParseException $e) {
            return false;
        }
    }
}
