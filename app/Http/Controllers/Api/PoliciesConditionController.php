<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\PoliciesCondition;

class PoliciesConditionController extends Controller
{

    public function getPoliciesConditions(Request $request)
    {
        $latestDate = PoliciesCondition::max('last_updated');

        $privacyPolicies = PoliciesCondition::where('type', 'privacy_policy')->where('last_updated', $latestDate)->select('title', 'content')->get();

        $termsAndConditions = PoliciesCondition::where('type', 'terms_and_conditions')->where('last_updated', $latestDate)->select('title', 'content')->get();

        $response = [
            'last_updated' => $latestDate,
            'privacy_policies' => $privacyPolicies,
            'terms_and_conditions' => $termsAndConditions,
        ];

        return response()->sendResponse($response,__('general.index_success'));
    }
}
