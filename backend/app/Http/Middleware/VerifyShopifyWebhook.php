<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyShopifyWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        if (!$hmac) {
            if (config('app.env') === 'local' || config('app.env') === 'testing') {
                Log::channel('shopify')->warning('VerifyShopifyWebhook: Bypassing signature verification in local/testing environment.');
                return $next($request);
            }
            Log::channel('shopify')->warning('VerifyShopifyWebhook: X-Shopify-Hmac-Sha256 header missing.');
            return response()->json(['message' => 'Unauthorized: Missing HMAC header'], 401);
        }

        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        if (!$shopDomain) {
            Log::channel('shopify')->warning('VerifyShopifyWebhook: X-Shopify-Shop-Domain header missing.');
            return response()->json(['message' => 'Unauthorized: Missing Shop Domain header'], 401);
        }

        // Resolve store specific webhook secret
        $secret = null;
        $store = \App\Models\ShopifyStore::where('shop_domain', $shopDomain)->first();
        if ($store && $store->webhook_secret) {
            $secret = $store->webhook_secret;
        }

        // Fallback to config secret
        if (!$secret) {
            $secret = config('services.shopify.webhook_secret');
        }

        if (!$secret) {
            if (config('app.env') === 'local' || config('app.env') === 'testing') {
                return $next($request);
            }
            Log::channel('shopify')->error("VerifyShopifyWebhook: Webhook secret not configured for domain {$shopDomain}.");
            return response()->json(['message' => 'Internal Server Error: Webhook secret not configured'], 500);
        }

        $data = $request->getContent();
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));

        if (!hash_equals($hmac, $calculatedHmac)) {
            if (config('app.env') === 'local' || config('app.env') === 'testing') {
                Log::channel('shopify')->warning('VerifyShopifyWebhook: HMAC signature verification failed, bypassing in local/testing.');
                return $next($request);
            }
            Log::channel('shopify')->warning('VerifyShopifyWebhook: HMAC signature verification failed.', [
                'shop' => $shopDomain,
                'received' => $hmac,
                'calculated' => $calculatedHmac
            ]);
            return response()->json(['message' => 'Unauthorized: Invalid signature'], 401);
        }

        return $next($request);
    }
}
