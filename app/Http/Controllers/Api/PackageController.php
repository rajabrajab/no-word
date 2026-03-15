<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyCouponRequest;
use App\Http\Requests\SubscribeRequest;
use App\Http\Resources\PackageResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $packages = Package::all();
        $user = auth()->user();

        $subscribedPackageId = null;
        if ($user) {
            $userSubscription = UserSubscription::where('user_id', $user->id)->where('status', 'active')->first();
            $subscribedPackageId = $userSubscription?->package_id;
        }

        $request->merge(['subscribed_package_id' => $subscribedPackageId]);

        return response()->sendResponse(
            PackageResource::collection($packages),
            ResponseMessages::INDEX_SUCCESS
        );
    }

    public function getSubscriptions()
    {
        $subscriptions = UserSubscription::where('user_id', auth()->user()->id)
            ->with(['package'])
            ->orderByRaw('CASE WHEN status = "active" AND games_remaining > 0 THEN 0 ELSE 1 END')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->sendResponse(
            SubscriptionResource::collection($subscriptions),
            ResponseMessages::INDEX_SUCCESS
        );
    }

    public function subscribe(SubscribeRequest $request)
    {
        $data = $request->validated();
        $user = auth()->user();

        DB::beginTransaction();

        try {
            $package = Package::findOrFail($data['package_id']);

            $existingSubscription = UserSubscription::where('user_id', $user->id)->first();

            if ($existingSubscription) {
                $existingSubscription->update([
                    'package_id' => $package->id,
                    'status' => 'active',
                    'games_remaining' => $package->games_count ?? 0,
                ]);
            } else {
                UserSubscription::create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'status' => 'active',
                    'games_remaining' => $package->games_count ?? 0,
                ]);
            }

            DB::commit();

            return response()->sendResponse(
                [],
                'Subscription successful.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->sendError(500, 'Failed to subscribe to package: ' . $e->getMessage());
        }
    }

    public function applyCoupon(ApplyCouponRequest $request)
    {
        try {
            $data = $request->validated();
            $user = auth()->user();

            $coupon = Coupon::where('code', $data['coupon_code'])->first();
            $package = Package::findOrFail($data['package_id']);

            if (!$coupon->is_active) {
                return response()->sendError(500, 'Coupon is not active.');
            }

            if ($coupon->valid_to && $coupon->valid_to < now()) {
                return response()->sendError(500, 'Coupon has expired.');
            }

            $usesRemaining = $coupon->max_uses ? ($coupon->max_uses - ($coupon->used_count ?? 0)) : null;
            if ($usesRemaining !== null && $usesRemaining <= 0) {
                return response()->sendError(500, 'Coupon has no remaining uses.');
            }

            $originalPrice = $package->price;
            $discountAmount = $coupon->calculateDiscount($originalPrice);
            $finalPrice = max(0, $originalPrice - $discountAmount);

            return response()->sendResponse([
                'coupon' => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'type' => $coupon->discount_type,
                    'value' => $coupon->discount_value,
                ],
                'price_before_discount' => $originalPrice,
                'price_after_discount' => $finalPrice,
            ], ResponseMessages::APPLY_COUPON_SUCCESS);
        } catch (\Exception $e) {
            return response()->sendError(500, 'Failed to apply coupon: ' . $e->getMessage());
        }
    }
}
