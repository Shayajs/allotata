<?php

namespace App\Http\Controllers;

use App\Models\PlayPurchase;
use App\Services\PlayBilling\PlayBillingFulfillment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PlayBillingController extends Controller
{
    public function products(): JsonResponse
    {
        return response()->json([
            'package_name' => config('play.package_name'),
            'products' => config('play.products'),
        ]);
    }

    public function verify(Request $request, PlayBillingFulfillment $fulfillment): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string', 'max:191'],
            'purchase_token' => ['required', 'string', 'max:2048'],
            'order_id' => ['nullable', 'string', 'max:191'],
            'entreprise_id' => ['nullable', 'integer'],
        ]);

        try {
            $result = $fulfillment->fulfill(
                $request->user(),
                $data['product_id'],
                $data['purchase_token'],
                $data['order_id'] ?? null,
                isset($data['entreprise_id']) ? (int) $data['entreprise_id'] : null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Play Billing verify failed', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Vérification Google Play impossible.'], 502);
        }

        return response()->json([
            'ok' => true,
            'granted' => $result['granted'],
            'purchase' => [
                'id' => $result['purchase']->id,
                'product_id' => $result['purchase']->product_id,
                'grants' => $result['purchase']->grants,
                'status' => $result['purchase']->status,
                'expires_at' => $result['purchase']->expires_at?->toIso8601String(),
            ],
        ]);
    }

    public function restore(Request $request, PlayBillingFulfillment $fulfillment): JsonResponse
    {
        $purchases = PlayPurchase::query()
            ->where('user_id', $request->user()->id)
            ->get();

        foreach ($purchases as $purchase) {
            $fulfillment->refresh($purchase);
        }

        return response()->json([
            'ok' => true,
            'purchases' => $purchases->fresh()->map(fn (PlayPurchase $purchase) => [
                'product_id' => $purchase->product_id,
                'status' => $purchase->status,
                'expires_at' => $purchase->expires_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function webhook(Request $request, PlayBillingFulfillment $fulfillment): JsonResponse
    {
        $encoded = data_get($request->all(), 'message.data');
        if (! is_string($encoded) || $encoded === '') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $payload = json_decode(base64_decode($encoded), true);
        if (! is_array($payload)) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $token = data_get($payload, 'subscriptionNotification.purchaseToken')
            ?: data_get($payload, 'oneTimeProductNotification.purchaseToken');

        if (! is_string($token) || $token === '') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $purchase = PlayPurchase::query()->where('purchase_token', $token)->first();
        if ($purchase) {
            $fulfillment->refresh($purchase);
        }

        return response()->json(['ok' => true]);
    }
}
