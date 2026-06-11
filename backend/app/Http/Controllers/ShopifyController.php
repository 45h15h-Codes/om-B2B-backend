<?php

namespace App\Http\Controllers;

use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyProduct;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShopifyController extends Controller
{
    protected ShopifyService $shopify;

    public function __construct(ShopifyService $shopify)
    {
        $this->shopify = $shopify;
    }

    /**
     * Display the Shopify Dashboard.
     */
    public function index(Request $request)
    {
        $isAdmin = (session('admin_role') === 'super_admin');
        if (!$isAdmin) {
            $this->shopify->forUser(Auth::id());
        }

        $storeName = $this->shopify->getStore() ?: 'Not Configured';
        $connectionStatus = $this->shopify->testConnection();

        $userId = Auth::id();
        $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');
        $cacheKey = $isSuper ? 'shopify_dashboard_order_stats_super' : "shopify_dashboard_order_stats_user_{$userId}";

        $orderStats = cache()->remember($cacheKey, 60, function() use ($isSuper, $userId) {
            $query = \App\Models\Order::query();
            if (!$isSuper) {
                $storeIds = \App\Models\ShopifyStore::where('user_id', $userId)->pluck('id')->toArray();
                $query->whereIn('shopify_store_id', $storeIds);
            }

            return [
                'total' => (clone $query)->count(),
                'pending' => (clone $query)->where('status', 'pending')
                    ->whereNull('shopify_order_id')
                    ->whereNull('shopify_draft_id')
                    ->count(),
                'synced' => (clone $query)->where(function($q) {
                    $q->whereNotNull('shopify_order_id')
                      ->orWhereNotNull('shopify_draft_id');
                })->count(),
                'failed' => (clone $query)->where('status', 'failed')->count(),
                'paid' => (clone $query)->where('status', 'paid')->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            ];
        });

        if ($isAdmin) {
            // Bulk verify and clean up deleted products
            $shopifyProductIds = ShopifyProduct::whereNotNull('shopify_product_id')->pluck('shopify_product_id')->toArray();
            if (!empty($shopifyProductIds)) {
                $this->shopify->verifyAndCleanupProducts($shopifyProductIds);
            }

            // --- SUPER ADMIN VIEW (ALL DATA) ---
            $totalDiamonds = Diamond::count();
            $totalJewelry = Jewelery::count();

            $inventoryOnHold = Diamond::whereIn('inventory_status', ['hold', 'on_hold'])->count() + Jewelery::whereIn('inventory_status', ['hold', 'on_hold'])->count();
            $inventorySoldToday = Diamond::where('inventory_status', 'sold')->where('updated_at', '>=', now()->startOfDay())->count() + Jewelery::where('inventory_status', 'sold')->where('updated_at', '>=', now()->startOfDay())->count();
            $inventoryAvailable = Diamond::where(function($q) { $q->whereNull('inventory_status')->orWhere('inventory_status', 'available'); })->count() + Jewelery::where(function($q) { $q->whereNull('inventory_status')->orWhere('inventory_status', 'available'); })->count();
            $activeStoresCount = \App\Models\ShopifyStore::where('is_active', true)->count();
            $unreadNotificationsCount = Auth::user()->unreadNotifications()->count();

            $syncedCount = ShopifyProduct::where('sync_status', 'synced')->count();
            $pendingCount = ShopifyProduct::whereIn('sync_status', ['pending', 'processing'])->count();
            $failedCount = ShopifyProduct::where('sync_status', 'failed')->count();

            // Active Tab
            $activeTab = $request->input('tab', 'diamonds');
            $searchQuery = $request->input('q');
            $statusFilter = $request->input('status');
            $inventoryStatusFilter = $request->input('inventory_status');

            // Tab 1: Diamonds List (paginated with search & status filters)
            $diamondsQuery = Diamond::query()->with(['shopifyProduct', 'user.activeShopifyStore', 'holdShopifyStore', 'soldStore']);
            if ($activeTab === 'diamonds' && $searchQuery) {
                $diamondsQuery->where(function($query) use ($searchQuery) {
                    $query->where('stock_no', 'like', "%{$searchQuery}%")
                          ->orWhere('specifications->report_no', 'like', "%{$searchQuery}%")
                          ->orWhere('shape', 'like', "%{$searchQuery}%")
                          ->orWhere('color', 'like', "%{$searchQuery}%")
                          ->orWhere('clarity', 'like', "%{$searchQuery}%");
                });
            }
            if ($activeTab === 'diamonds' && $statusFilter) {
                $this->applyStatusFilter($diamondsQuery, $statusFilter);
            }
            if ($activeTab === 'diamonds' && $inventoryStatusFilter) {
                $diamondsQuery->where('inventory_status', $inventoryStatusFilter);
            }
            $diamonds = $diamondsQuery->orderBy('id', 'desc')->paginate(15, ['*'], 'diamonds_page')->appends($request->query());

            // Tab 2: Jewelry List (paginated with search & status filters)
            $jewelryQuery = Jewelery::query()->with(['shopifyProduct', 'user.activeShopifyStore']);
            if ($activeTab === 'jewelry' && $searchQuery) {
                $jewelryQuery->where(function($query) use ($searchQuery) {
                    $query->where('sku', 'like', "%{$searchQuery}%")
                          ->orWhere('name', 'like', "%{$searchQuery}%")
                          ->orWhere('type', 'like', "%{$searchQuery}%");
                });
            }
            if ($activeTab === 'jewelry' && $statusFilter) {
                $this->applyStatusFilter($jewelryQuery, $statusFilter);
            }
            if ($activeTab === 'jewelry' && $inventoryStatusFilter) {
                $jewelryQuery->where('inventory_status', $inventoryStatusFilter);
            }
            $jewelry = $jewelryQuery->orderBy('id', 'desc')->paginate(15, ['*'], 'jewelry_page')->appends($request->query());

            // Tab 3: Unified Shopify Products (paginated with search & status filters)
            $shopifyProductsQuery = ShopifyProduct::query()->with([
                'shopifyStore',
                'product' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\Diamond::class => ['user.activeShopifyStore', 'holdShopifyStore', 'soldStore'],
                        \App\Models\Jewelery::class => ['user.activeShopifyStore'],
                    ]);
                }
            ]);
            if ($activeTab === 'synced' && $searchQuery) {
                $shopifyProductsQuery->where(function($query) use ($searchQuery) {
                    $query->whereHasMorph('product', [Diamond::class], function($subQuery) use ($searchQuery) {
                        $subQuery->where('stock_no', 'like', "%{$searchQuery}%")
                                 ->orWhere('shape', 'like', "%{$searchQuery}%");
                    })
                    ->orWhereHasMorph('product', [Jewelery::class], function($subQuery) use ($searchQuery) {
                        $subQuery->where('sku', 'like', "%{$searchQuery}%")
                                 ->orWhere('name', 'like', "%{$searchQuery}%");
                    })
                    ->orWhere('shopify_product_id', 'like', "%{$searchQuery}%");
                });
            }
            if ($activeTab === 'synced' && $statusFilter) {
                if ($statusFilter === 'synced') {
                    $shopifyProductsQuery->where('sync_status', 'synced');
                } elseif ($statusFilter === 'pending') {
                    $shopifyProductsQuery->whereIn('sync_status', ['pending', 'processing']);
                } elseif ($statusFilter === 'failed') {
                    $shopifyProductsQuery->where('sync_status', 'failed');
                }
            }
            $shopifyProducts = $shopifyProductsQuery->orderBy('updated_at', 'desc')->paginate(15, ['*'], 'synced_page')->appends($request->query());

            // Tab 4: Reservation History (Super Admin Only)
            $reservations = null;
            if ($activeTab === 'reservations') {
                $reservations = \App\Models\ShopifyInventoryReservation::with(['shopifyStore', 'order', 'product'])
                    ->orderBy('id', 'desc')
                    ->paginate(15, ['*'], 'reservations_page')
                    ->appends($request->query());
            }

            // Tab 5: Sync Audit Logs (Super Admin Only)
            $audits = null;
            if ($activeTab === 'audits') {
                $audits = \App\Models\ShopifyInventoryAudit::with(['shopifyStore', 'diamond', 'jewelry'])
                    ->where('action', 'sold_set_zero')
                    ->orderBy('id', 'desc')
                    ->paginate(15, ['*'], 'audits_page')
                    ->appends($request->query());

                foreach ($audits as $audit) {
                    $productId = $audit->diamond_id ?? $audit->jewelry_id;
                    if ($productId) {
                        $autoDrafts = \App\Models\ShopifyInventoryAudit::where('action', 'auto_draft')
                            ->where(function($q) use ($audit) {
                                if ($audit->diamond_id) {
                                    $q->where('diamond_id', $audit->diamond_id);
                                } else {
                                    $q->where('jewelry_id', $audit->jewelry_id);
                                }
                            })
                            ->where('created_at', '>=', $audit->created_at->subMinutes(5))
                            ->where('created_at', '<=', $audit->created_at->addMinutes(5))
                            ->with('shopifyStore')
                            ->get();

                        $audit->auto_drafted_stores = $autoDrafts->map(function($ad) {
                            return $ad->shopifyStore ? $ad->shopifyStore->store_name : 'Unknown Store';
                        })->toArray();
                    } else {
                        $audit->auto_drafted_stores = [];
                    }
                }
            }

            // Calculate Store Performance Rankings for Super Admin (always display at the top of the dashboard page)
            $rankings = [];
            foreach (\App\Models\ShopifyStore::all() as $st) {
                $paidOrders = \App\Models\ShopifyOrder::where('shopify_store_id', $st->id)->where('financial_status', 'paid')->count();
                $totalOrders = \App\Models\ShopifyOrder::where('shopify_store_id', $st->id)->count();
                $rev = (float) \App\Models\ShopifyOrder::where('shopify_store_id', $st->id)->where('financial_status', 'paid')->sum('total_price');
                
                $diamondsCount = Diamond::where('sold_store_id', $st->id)->where('inventory_status', 'sold')->count();
                $jewelryCount = Jewelery::where('sold_store_id', $st->id)->where('inventory_status', 'sold')->count();
                
                $convRate = $totalOrders > 0 ? round(($paidOrders / $totalOrders) * 100, 2) : 0.00;
                
                $rankings[] = [
                    'store_name' => $st->store_name,
                    'revenue' => $rev,
                    'orders_count' => $paidOrders,
                    'diamonds_sold' => $diamondsCount,
                    'jewelry_sold' => $jewelryCount,
                    'conversion_rate' => $convRate,
                ];
            }

            // Sort rankings by revenue descending
            usort($rankings, function($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });

            return view('shopify.dashboard', compact(
                'storeName',
                'connectionStatus',
                'totalDiamonds',
                'totalJewelry',
                'syncedCount',
                'pendingCount',
                'failedCount',
                'activeTab',
                'diamonds',
                'jewelry',
                'shopifyProducts',
                'orderStats',
                'reservations',
                'audits',
                'rankings',
                'inventoryOnHold',
                'inventorySoldToday',
                'inventoryAvailable',
                'activeStoresCount',
                'unreadNotificationsCount'
            ));
        } else {
            // --- NORMAL ADMIN VIEW (PERSONAL DATA ISOLATION) ---
            $userId = Auth::id();

            // Bulk verify and clean up deleted products belonging to this user
            $shopifyProductIds = ShopifyProduct::where(function($query) use ($userId) {
                $query->whereHasMorph('product', [Diamond::class], function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->orWhereHasMorph('product', [Jewelery::class], function($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })->whereNotNull('shopify_product_id')->pluck('shopify_product_id')->toArray();

            if (!empty($shopifyProductIds)) {
                $this->shopify->verifyAndCleanupProducts($shopifyProductIds);
            }

            $activeStoreId = Auth::user()->active_shopify_store_id;

            $totalDiamonds = Diamond::where('user_id', $userId)->count();
            $totalJewelry = Jewelery::where('user_id', $userId)->count();

            $inventoryOnHold = Diamond::where('user_id', $userId)->whereIn('inventory_status', ['hold', 'on_hold'])->count() + Jewelery::where('user_id', $userId)->whereIn('inventory_status', ['hold', 'on_hold'])->count();
            $inventorySoldToday = Diamond::where('user_id', $userId)->where('inventory_status', 'sold')->where('updated_at', '>=', now()->startOfDay())->count() + Jewelery::where('user_id', $userId)->where('inventory_status', 'sold')->where('updated_at', '>=', now()->startOfDay())->count();
            $inventoryAvailable = Diamond::where('user_id', $userId)->where(function($q) { $q->whereNull('inventory_status')->orWhere('inventory_status', 'available'); })->count() + Jewelery::where('user_id', $userId)->where(function($q) { $q->whereNull('inventory_status')->orWhere('inventory_status', 'available'); })->count();
            $activeStoresCount = \App\Models\ShopifyStore::where('user_id', $userId)->where('is_active', true)->count();
            $unreadNotificationsCount = Auth::user()->unreadNotifications()->count();

            // Synced count matching user's owned products in their active store
            $syncedCount = ShopifyProduct::where('shopify_store_id', $activeStoreId)
                ->where('sync_status', 'synced')
                ->where(function($query) use ($userId) {
                    $query->whereHasMorph('product', [Diamond::class], function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })
                    ->orWhereHasMorph('product', [Jewelery::class], function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                })->count();

            $pendingCount = ShopifyProduct::where('shopify_store_id', $activeStoreId)
                ->whereIn('sync_status', ['pending', 'processing'])
                ->where(function($query) use ($userId) {
                    $query->whereHasMorph('product', [Diamond::class], function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })
                    ->orWhereHasMorph('product', [Jewelery::class], function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                })->count();

            $failedCount = ShopifyProduct::where('shopify_store_id', $activeStoreId)
                ->where('sync_status', 'failed')
                ->where(function($query) use ($userId) {
                    $query->whereHasMorph('product', [Diamond::class], function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })
                    ->orWhereHasMorph('product', [Jewelery::class], function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                })->count();

            // Recent Sync Tables (user's owned items in current active store)
            $recentDiamonds = ShopifyProduct::where('product_type', 'diamond')
                ->where('shopify_store_id', $activeStoreId)
                ->whereHasMorph('product', [Diamond::class], function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get();

            $recentJewelry = ShopifyProduct::where('product_type', 'jewelry')
                ->where('shopify_store_id', $activeStoreId)
                ->whereHasMorph('product', [Jewelery::class], function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get();

            // Newly Added Scoped Dashboard Metrics for Normal Admin
            $userStoreIds = \App\Models\ShopifyStore::where('user_id', $userId)->pluck('id')->toArray();
            $shopifyOrderQuery = \App\Models\ShopifyOrder::whereIn('shopify_store_id', $userStoreIds);
            
            $ordersStats = [
                'total' => (clone $shopifyOrderQuery)->count(),
                'today' => (clone $shopifyOrderQuery)->whereDate('created_at', \Carbon\Carbon::today())->count(),
                'this_month' => (clone $shopifyOrderQuery)->where('created_at', '>=', \Carbon\Carbon::now()->startOfMonth())->count(),
                'pending' => (clone $shopifyOrderQuery)->whereNull('fulfillment_status')->count(),
                'completed' => (clone $shopifyOrderQuery)->where('fulfillment_status', 'fulfilled')->count(),
            ];

            $paidOrderQuery = \App\Models\ShopifyOrder::whereIn('shopify_store_id', $userStoreIds)->where('financial_status', 'paid');
            $revenueStats = [
                'today' => (clone $paidOrderQuery)->whereDate('created_at', \Carbon\Carbon::today())->sum('total_price'),
                'this_month' => (clone $paidOrderQuery)->where('created_at', '>=', \Carbon\Carbon::now()->startOfMonth())->sum('total_price'),
                'this_year' => (clone $paidOrderQuery)->where('created_at', '>=', \Carbon\Carbon::now()->startOfYear())->sum('total_price'),
            ];

            $availableDiamonds = Diamond::where(function($q) use ($userStoreIds) {
                    $q->whereIn('id', function($sub) use ($userStoreIds) {
                        $sub->select('diamond_id')->from('diamond_store_assignments')->whereIn('shopify_store_id', $userStoreIds);
                    })
                    ->orWhereIn('id', function($sub) use ($userStoreIds) {
                        $sub->select('product_id')->from('shopify_products')->where('product_type', 'diamond')->whereIn('shopify_store_id', $userStoreIds);
                    });
                })
                ->where(function($q){$q->whereNull('inventory_status')->orWhere('inventory_status', 'available');})
                ->count();

            $availableJewelry = Jewelery::where(function($q) use ($userStoreIds) {
                    $q->whereIn('id', function($sub) use ($userStoreIds) {
                        $sub->select('product_id')->from('shopify_products')->where('product_type', 'jewelry')->whereIn('shopify_store_id', $userStoreIds);
                    });
                })
                ->where(function($q){$q->whereNull('inventory_status')->orWhere('inventory_status', 'available');})
                ->count();

            $soldDiamonds = Diamond::whereIn('sold_store_id', $userStoreIds)->where('inventory_status', 'sold')->count();
            $soldJewelry = Jewelery::whereIn('sold_store_id', $userStoreIds)->where('inventory_status', 'sold')->count();

            return view('shopify.normal_dashboard', compact(
                'storeName',
                'connectionStatus',
                'totalDiamonds',
                'totalJewelry',
                'syncedCount',
                'pendingCount',
                'failedCount',
                'recentDiamonds',
                'recentJewelry',
                'orderStats',
                'inventoryOnHold',
                'inventorySoldToday',
                'inventoryAvailable',
                'activeStoresCount',
                'unreadNotificationsCount',
                'ordersStats',
                'revenueStats',
                'availableDiamonds',
                'availableJewelry',
                'soldDiamonds',
                'soldJewelry'
            ));
        }
    }

    /**
     * Apply sync status filters to Eloquent queries.
     */
    protected function applyStatusFilter($query, string $status)
    {
        if ($status === 'synced') {
            $query->whereHas('shopifyProduct', function($q) {
                $q->where('sync_status', 'synced');
            });
        } elseif ($status === 'pending') {
            $query->whereHas('shopifyProduct', function($q) {
                $q->whereIn('sync_status', ['pending', 'processing']);
            });
        } elseif ($status === 'failed') {
            $query->whereHas('shopifyProduct', function($q) {
                $q->where('sync_status', 'failed');
            });
        } elseif ($status === 'not_synced') {
            $query->where(function($q) {
                $q->whereDoesntHave('shopifyProduct')
                  ->orWhereHas('shopifyProduct', function($subQ) {
                      $subQ->whereNotIn('sync_status', ['synced', 'pending', 'processing', 'failed']);
                  });
            });
        }
    }

    /**
     * List all connected stores.
     */
    public function stores(Request $request)
    {
        $isAdmin = (session('admin_role') === 'super_admin');
        if ($isAdmin) {
            $stores = \App\Models\ShopifyStore::with('user')->get();
        } else {
            $stores = \App\Models\ShopifyStore::where('user_id', Auth::id())->get();
        }
        
        $activeStoreId = Auth::user()->active_shopify_store_id;
        
        return view('shopify.stores', compact('stores', 'activeStoreId'));
    }

    /**
     * Connect a new Shopify store via OAuth initiation.
     */
    public function connectStore(Request $request)
    {
        // Super Admin cannot connect stores
        if (session('admin_role') === 'super_admin') {
            abort(403, 'Super Admin cannot connect Shopify stores.');
        }

        $request->validate([
            'shop_domain' => 'required|string',
            'store_name' => 'nullable|string',
            'access_token' => 'nullable|string',
        ]);

        try {
            $domain = $request->input('shop_domain');
            $domain = preg_replace('/^https?:\/\//i', '', $domain);
            $domain = rtrim($domain, '/');

            // Check if domain is already connected
            $exists = \App\Models\ShopifyStore::where('shop_domain', $domain)->exists();

            if ($exists) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'This store domain is already registered in the system.');
            }

            $accessToken = $request->input('access_token');

            // 1. Manual Connection (Primary / Default)
            if (!empty($accessToken)) {
                // Create temporary store record
                $store = \App\Models\ShopifyStore::create([
                    'user_id'      => Auth::id(),
                    'store_name'   => $request->input('store_name') ?: $domain,
                    'shop_domain'  => $domain,
                    'access_token' => $accessToken,
                    'auth_type'    => 'manual',
                    'is_active'    => true,
                ]);

                $this->shopify->forStore($store);

                if ($this->shopify->testConnection()) {
                    $user = Auth::user();

                    if (!$user->active_shopify_store_id) {
                        $user->update([
                            'active_shopify_store_id' => $store->id
                        ]);
                    }

                    // Automatically verify and register webhooks
                    try {
                        app(\App\Services\ShopifySyncService::class)->verifyWebhooks($store->id);
                    } catch (\Throwable $webhookEx) {
                        Log::error('Failed to register webhooks during manual store connection', [
                            'store_id' => $store->id,
                            'error' => $webhookEx->getMessage(),
                        ]);
                    }

                    return redirect()
                        ->route('shopify.stores')
                        ->with('success', 'Shopify store connected and verified successfully!');
                }

                $store->delete();

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to connect. Please verify your shop domain and Admin API Access Token.');
            }

            // 2. OAuth Connection (Optional Fallback)
            $apiKey = config('shopify.api_key');
            $scopes = config('shopify.scopes');
            $redirectUri = route('shopify.callback');

            if (empty($apiKey) || empty($scopes) || empty(config('shopify.api_secret'))) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Admin API Access Token is required to connect a Shopify store.');
            }

            $state = bin2hex(random_bytes(16));
            session([
                'shopify_oauth_state' => $state,
                'shopify_oauth_shop' => $domain,
                'shopify_oauth_store_name' => $request->input('store_name')
            ]);

            $authorizeUrl = "https://{$domain}/admin/oauth/authorize?" . http_build_query([
                'client_id' => $apiKey,
                'scope' => $scopes,
                'redirect_uri' => $redirectUri,
                'state' => $state,
            ]);

            Log::info('OAuth Callback URL', [
                'callback' => route('shopify.callback'),
            ]);

            Log::info('OAuth Authorize URL', [
                'url' => $authorizeUrl,
            ]);

            return redirect()->away($authorizeUrl);

        } catch (\Throwable $e) {
            Log::error('Shopify Connect Store Failed', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error initiating connection flow: ' . $e->getMessage());
        }
    }

    /**
     * Handle Shopify OAuth callback.
     */
    public function oauthCallback(Request $request)
    {
        $code = $request->query('code');
        $shop = $request->query('shop');
        $state = $request->query('state');
        $hmac = $request->query('hmac');

        if (!$code || !$shop || !$state || !$hmac) {
            return redirect()->route('shopify.stores')
                ->with('error', 'Invalid OAuth callback parameters.');
        }

        // Validate state
        if ($state !== session('shopify_oauth_state')) {
            return redirect()->route('shopify.stores')
                ->with('error', 'OAuth state validation failed. Possible CSRF attack.');
        }

        // Clean up session state
        session()->forget(['shopify_oauth_state', 'shopify_oauth_shop']);

        // Verify HMAC signature
        $params = $request->except('hmac');
        ksort($params);
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = "{$k}=" . (is_array($v) ? implode(',', $v) : $v);
        }
        $queryString = implode('&', $pairs);
        $calculatedHmac = hash_hmac('sha256', $queryString, config('shopify.api_secret'));

        if (!hash_equals($hmac, $calculatedHmac)) {
            return redirect()->route('shopify.stores')
                ->with('error', 'OAuth signature verification failed.');
        }

        try {
            // Exchange code for access token
            $response = \Illuminate\Support\Facades\Http::post("https://{$shop}/admin/oauth/access_token", [
                'client_id' => config('shopify.api_key'),
                'client_secret' => config('shopify.api_secret'),
                'code' => $code,
            ]);

            if (!$response->successful()) {
                Log::error('Shopify Token Exchange Failed', ['response' => $response->body()]);
                return redirect()->route('shopify.stores')
                    ->with('error', 'Failed to retrieve access token from Shopify.');
            }

            $accessToken = $response->json('access_token');
            $scopes = $response->json('scope');

            // Save/update ShopifyStore
            $storeName = session('shopify_oauth_store_name') ?: $shop;
            session()->forget('shopify_oauth_store_name');

            $store = \App\Models\ShopifyStore::updateOrCreate(
                ['shop_domain' => $shop],
                [
                    'user_id' => Auth::id(),
                    'store_name' => $storeName,
                    'access_token' => $accessToken,
                    'scopes' => $scopes,
                    'auth_type' => 'oauth',
                    'webhook_secret' => config('shopify.api_secret'),
                    'is_active' => true,
                ]
            );

            // Set as active store for the user
            $user = Auth::user();
            $user->update([
                'active_shopify_store_id' => $store->id,
            ]);

            // Automatically verify and register webhooks
            try {
                app(\App\Services\ShopifySyncService::class)->verifyWebhooks($store->id);
            } catch (\Throwable $webhookEx) {
                Log::error('Failed to register webhooks during OAuth callback', [
                    'store_id' => $store->id,
                    'error' => $webhookEx->getMessage(),
                ]);
            }

            return redirect()->route('shopify.stores')
                ->with('success', 'Shopify store connected and authenticated successfully!');

        } catch (\Throwable $e) {
            Log::error('Shopify OAuth Callback Failed', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('shopify.stores')
                ->with('error', 'Error completing authentication: ' . $e->getMessage());
        }
    }

    /**
 * Set the active store for the user.
 */
public function setActiveStore($storeId)
{
    // Super Admin can only view stores
    if (session('admin_role') === 'super_admin') {
        abort(403, 'Super Admin cannot change active stores.');
    }

    $store = \App\Models\ShopifyStore::where('user_id', Auth::id())
        ->findOrFail($storeId);

    $user = Auth::user();
    $user->update([
        'active_shopify_store_id' => $store->id
    ]);

    return redirect()->back()->with(
        'success',
        "Active store switched to {$store->store_name} ({$store->shop_domain})"
    );
}

    /**
 * Delete/Disconnect a Shopify store.
 */
public function deleteStore($storeId)
{
    // Super Admin can only view stores
    if (session('admin_role') === 'super_admin') {
        abort(403, 'Super Admin cannot disconnect stores.');
    }

    $store = \App\Models\ShopifyStore::where('user_id', Auth::id())
        ->findOrFail($storeId);

    // Clear active store on users using it
    \App\Models\User::where('active_shopify_store_id', $store->id)
        ->update([
            'active_shopify_store_id' => null
        ]);

    // Delete local shopify product references for this store
    $store->shopifyProducts()->delete();

    $store->delete();

    return redirect()->back()->with(
        'success',
        'Shopify store disconnected successfully.'
    );
}

    /**
     * Trigger Bulk Sync for products.
     */
    public function syncAll(Request $request)
    {
        $isAdmin = (session('admin_role') === 'super_admin');
        $userId = $isAdmin ? null : Auth::id();
        $activeStoreId = Auth::user()->active_shopify_store_id;

        if (!$activeStoreId) {
            return redirect()->back()->with('error', 'Please connect and select an active Shopify store first.');
        }

        $this->shopify->forStore($activeStoreId);

        $type = $request->input('type', 'all');
        
        try {
            if ($type === 'diamonds') {
                $count = $this->shopify->syncAllDiamonds($userId);
                $message = "Dispatched sync jobs for " . ($userId ? "your " : "") . "{$count} unsynced/failed diamonds.";
            } elseif ($type === 'jewelry') {
                $count = $this->shopify->syncAllJewelry($userId);
                $message = "Dispatched sync jobs for " . ($userId ? "your " : "") . "{$count} unsynced/failed jewelry products.";
            } else {
                $counts = $this->shopify->syncAllProducts($userId);
                $total = $counts['diamonds'] + $counts['jewelry'];
                $message = "Dispatched sync jobs for " . ($userId ? "your " : "") . "{$total} products ({$counts['diamonds']} diamonds, {$counts['jewelry']} jewelry).";
            }

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Bulk Sync Trigger Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to trigger sync: ' . $e->getMessage());
        }
    }

    /**
     * Retry all failed syncs.
     */
    public function retryFailed()
    {
        $isAdmin = (session('admin_role') === 'super_admin');
        $userId = $isAdmin ? null : Auth::id();
        $activeStoreId = Auth::user()->active_shopify_store_id;

        try {
            $query = ShopifyProduct::where('sync_status', 'failed');
            if ($userId) {
                $query->where('shopify_store_id', $activeStoreId)
                    ->where(function($q) use ($userId) {
                        $q->whereHasMorph('product', [Diamond::class], function($subQ) use ($userId) {
                            $subQ->where('user_id', $userId);
                        })
                        ->orWhereHasMorph('product', [Jewelery::class], function($subQ) use ($userId) {
                            $subQ->where('user_id', $userId);
                        });
                    });
            }

            $failedProducts = $query->get();
            $count = 0;

            foreach ($failedProducts as $sync) {
                if ($sync->product_type === 'diamond') {
                    \App\Jobs\PublishDiamondToShopifyJob::dispatch($sync->product_id, $sync->shopify_store_id);
                } elseif ($sync->product_type === 'jewelry') {
                    \App\Jobs\PublishJewelryToShopifyJob::dispatch($sync->product_id, $sync->shopify_store_id);
                }
                $sync->update(['sync_status' => 'pending']);
                $count++;
            }

            return redirect()->back()->with('success', "Retrying " . ($userId ? "your " : "") . "{$count} failed products in the background.");
        } catch (\Throwable $e) {
            Log::error('Retry Failed Syncs Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to retry syncs: ' . $e->getMessage());
        }
    }

    /**
     * Retry a single failed sync.
     */
    public function retrySingle($id)
    {
        try {
            $sync = ShopifyProduct::findOrFail($id);
            $isAdmin = (session('admin_role') === 'super_admin');

            // Data ownership check for normal admins
            if (!$isAdmin) {
                $product = $sync->product;
                if (!$product || $product->user_id !== Auth::id()) {
                    abort(403, 'Unauthorized action. You can only retry syncing your own products.');
                }
            }

            if ($sync->product_type === 'diamond') {
                \App\Jobs\PublishDiamondToShopifyJob::dispatch($sync->product_id, $sync->shopify_store_id);
            } elseif ($sync->product_type === 'jewelry') {
                \App\Jobs\PublishJewelryToShopifyJob::dispatch($sync->product_id, $sync->shopify_store_id);
            }

            $sync->update(['sync_status' => 'pending']);

            return redirect()->back()->with('success', 'Sync job queued for retry.');
        } catch (\Throwable $e) {
            Log::error('Retry Single Sync Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to retry sync: ' . $e->getMessage());
        }
    }

    /**
     * Delete a local shopify_products sync record.
     */
    public function deleteSync($id)
    {
        try {
            $sync = ShopifyProduct::findOrFail($id);
            $isAdmin = (session('admin_role') === 'super_admin');

            if (!$isAdmin) {
                $product = $sync->product;
                if (!$product || $product->user_id !== Auth::id()) {
                    abort(403, 'Unauthorized action. You can only delete your own sync records.');
                }
            }

            $sync->delete();
            return redirect()->back()->with('success', 'Shopify sync record deleted successfully from database.');
        } catch (\Throwable $e) {
            Log::error('Delete Sync Record Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete sync record: ' . $e->getMessage());
        }
    }

    /**
     * Manually publish a Diamond to Shopify.
     */
    public function publishDiamond(Diamond $diamond)
    {
        $isAdmin = (session('admin_role') === 'super_admin');
        if (!$isAdmin && $diamond->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action. You can only publish your own diamonds.');
        }

        if (($diamond->inventory_status ?? 'available') !== 'available') {
            return redirect()->back()->with('error', 'Cannot publish Hold or Sold diamonds.');
        }

        try {
            $activeStore = $diamond->user ? $diamond->user->activeShopifyStore : null;
            if (!$activeStore) {
                return redirect()->back()->with('error', 'No active Shopify store context found for this account.');
            }

            $sync = $diamond->shopifyProducts()->where('shopify_store_id', $activeStore->id)->first();
            if (!$sync) {
                $sync = $diamond->shopifyProducts()->create([
                    'shopify_store_id' => $activeStore->id,
                    'sync_status' => 'pending',
                ]);
            } else {
                $sync->update(['sync_status' => 'pending']);
            }

            \App\Jobs\PublishDiamondToShopifyJob::dispatch($diamond->id, $activeStore->id);

            return redirect()->back()->with('success', 'Diamond publishing job dispatched successfully.');
        } catch (\Throwable $e) {
            Log::error('Manual Diamond Publish Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to dispatch publish job: ' . $e->getMessage());
        }
    }

    /**
     * Manually publish a Jewelry product to Shopify.
     */
    public function publishJewelry(Jewelery $jewelry)
    {
        $isAdmin = (session('admin_role') === 'super_admin');
        if (!$isAdmin && $jewelry->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action. You can only publish your own jewelry.');
        }

        if (($jewelry->inventory_status ?? 'available') !== 'available') {
            return redirect()->back()->with('error', 'Cannot publish Hold or Sold jewelry items.');
        }

        try {
            $activeStore = $jewelry->user ? $jewelry->user->activeShopifyStore : null;
            if (!$activeStore) {
                return redirect()->back()->with('error', 'No active Shopify store context found for this account.');
            }

            $sync = $jewelry->shopifyProducts()->where('shopify_store_id', $activeStore->id)->first();
            if (!$sync) {
                $sync = $jewelry->shopifyProducts()->create([
                    'shopify_store_id' => $activeStore->id,
                    'sync_status' => 'pending',
                ]);
            } else {
                $sync->update(['sync_status' => 'pending']);
            }

            \App\Jobs\PublishJewelryToShopifyJob::dispatch($jewelry->id, $activeStore->id);

            return redirect()->back()->with('success', 'Jewelry publishing job dispatched successfully.');
        } catch (\Throwable $e) {
            Log::error('Manual Jewelry Publish Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to dispatch publish job: ' . $e->getMessage());
        }
    }

    /**
     * Legacy connect method.
     */
    public function connect(Request $request)
    {
        return redirect()->route('shopify.stores');
    }
}
