<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\InventoryRequest;
use App\Models\InventoryHistory;
use App\Services\InventoryManager;
use App\Notifications\NewRequestNotification;
use App\Notifications\RequestStatusChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryRequestController extends Controller
{
    protected InventoryManager $inventoryManager;

    public function __construct(InventoryManager $inventoryManager)
    {
        $this->inventoryManager = $inventoryManager;
    }

    /**
     * Display a listing of requests created by the current Normal Admin.
     */
    public function myRequests(Request $request)
    {
        $this->authorize('request', InventoryRequest::class);

        $status = $request->input('status');
        $priority = $request->input('priority');

        $query = InventoryRequest::where('user_id', auth()->id());

        if ($status) {
            $query->where('status', $status);
        }
        if ($priority) {
            $query->where('priority', $priority);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $assignedDiamonds = \App\Models\Diamond::where('assigned_admin_id', auth()->id())->get();
        $assignedJewelery = \App\Models\Jewelery::where('assigned_admin_id', auth()->id())->get();

        return view('my_requests.index', compact('requests', 'assignedDiamonds', 'assignedJewelery'));
    }

    /**
     * Display a listing of all requests for Super Admin.
     */
    public function allRequests(Request $request)
    {
        $this->authorize('manageRequests', InventoryRequest::class);

        $status = $request->input('status');
        $priority = $request->input('priority');
        $userFilter = $request->input('user_id');

        $query = InventoryRequest::with(['user', 'approver']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($priority) {
            $query->where('priority', $priority);
        }
        if ($userFilter) {
            $query->where('user_id', $userFilter);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $users = User::where('role', 'normal_admin')->get();

        return view('all_requests.index', compact('requests', 'users'));
    }

    /**
     * Store a newly created request.
     */
    public function store(Request $request)
    {
        $this->authorize('request', InventoryRequest::class);

        $request->validate([
            'request_type' => 'required|string|in:Hold Inventory,Release Inventory,Shopify Sync,Price Change,Inventory Correction',
            'product_type' => 'required|string|in:diamond,jewelry',
            'product_id' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'required|string|in:Low,Medium,High',
            'reason' => 'required_if:request_type,Hold Inventory|nullable|string|max:255',
            'remarks' => 'required_if:request_type,Release Inventory|nullable|string|max:255',
            'price' => 'required_if:request_type,Price Change|nullable|numeric|min:0',
        ]);

        $product = $this->inventoryManager->resolveProduct($request->product_type, $request->product_id);
        if (!$product) {
            return back()->with('error', 'Inventory product not found.');
        }

        if ($request->request_type === 'Hold Inventory' && $product->inventory_status !== 'available') {
            return back()->with('error', 'This item is not available to be held.');
        }

        // Action payload mapping
        $payload = [];
        if ($request->request_type === 'Hold Inventory') {
            $payload['reason'] = $request->reason;
        } elseif ($request->request_type === 'Release Inventory') {
            $payload['remarks'] = $request->remarks;
        } elseif ($request->request_type === 'Price Change') {
            $payload['price'] = $request->price;
        }

        $invRequest = InventoryRequest::create([
            'user_id' => auth()->id(),
            'request_type' => $request->request_type,
            'product_type' => $request->product_type,
            'product_id' => $request->product_id,
            'notes' => $request->notes,
            'action_payload' => $payload,
            'priority' => $request->priority,
            'status' => 'Pending',
        ]);

        // Notify all Super Admins
        $superAdmins = User::where('role', 'super_admin')->get();
        foreach ($superAdmins as $super) {
            $super->notify(new NewRequestNotification($invRequest));
        }

        return redirect()->route('my-requests')->with('success', 'Request submitted successfully.');
    }

    /**
     * Approve and execute the request.
     */
    public function approve(Request $request, $id)
    {
        $this->authorize('manageRequests', InventoryRequest::class);

        $invRequest = InventoryRequest::findOrFail($id);
        if ($invRequest->status !== 'Pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $product = $this->inventoryManager->resolveProduct($invRequest->product_type, $invRequest->product_id);
        if (!$product) {
            return back()->with('error', 'Associated inventory item not found.');
        }

        try {
            DB::transaction(function () use ($invRequest, $product, $request) {
                $payload = $invRequest->action_payload;
                $userIp = $request->ip();

                switch ($invRequest->request_type) {
                    case 'Hold Inventory':
                        $this->inventoryManager->hold(
                            $product,
                            $invRequest->user_id,
                            $payload['reason'] ?? 'Hold request approved',
                            $userIp
                        );
                        break;

                    case 'Release Inventory':
                        $this->inventoryManager->release(
                            $product,
                            $invRequest->user_id,
                            $payload['remarks'] ?? 'Release request approved',
                            $userIp
                        );
                        break;

                    case 'Shopify Sync':
                        $this->inventoryManager->sync(
                            $product,
                            $invRequest->user_id,
                            'Sync request approved',
                            $userIp
                        );
                        break;

                    case 'Price Change':
                        $newPrice = $payload['price'] ?? 0;
                        $oldPrice = ($invRequest->product_type === 'diamond')
                            ? $product->asking_price
                            : $product->price;

                        // Update local product price
                        if ($invRequest->product_type === 'diamond') {
                            $product->update([
                                'asking_price' => $newPrice,
                                'cash_price' => $newPrice
                            ]);
                        } else {
                            $product->update([
                                'price' => $newPrice
                            ]);
                        }

                        // Create history
                        InventoryHistory::create([
                            'product_type' => $invRequest->product_type,
                            'product_id' => $product->id,
                            'action' => 'price_change',
                            'old_value' => $oldPrice,
                            'new_value' => $newPrice,
                            'user_id' => $invRequest->user_id,
                            'remarks' => 'Price change request approved',
                            'ip_address' => $userIp,
                        ]);

                        // Sync to Shopify
                        $this->inventoryManager->sync(
                            $product,
                            $invRequest->user_id,
                            'Price change sync approved',
                            $userIp
                        );
                        break;

                    case 'Inventory Correction':
                        // Log a history correction and trigger sync
                        InventoryHistory::create([
                            'product_type' => $invRequest->product_type,
                            'product_id' => $product->id,
                            'action' => 'correction',
                            'old_value' => $product->inventory_status,
                            'new_value' => $product->inventory_status,
                            'user_id' => $invRequest->user_id,
                            'remarks' => 'Inventory correction request approved: ' . $invRequest->notes,
                            'ip_address' => $userIp,
                        ]);
                        $this->inventoryManager->sync(
                            $product,
                            $invRequest->user_id,
                            'Inventory correction sync approved',
                            $userIp
                        );
                        break;
                }

                // Update request status
                $invRequest->update([
                    'status' => 'Approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            });

            // Send notification to the requester
            if ($invRequest->user) {
                $invRequest->user->notify(new RequestStatusChangedNotification($invRequest));
            }

            return back()->with('success', 'Request approved and executed successfully.');
        } catch (\Throwable $e) {
            Log::error("Request approval transaction failed for request {$id}: " . $e->getMessage());
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Reject the request.
     */
    public function reject(Request $request, $id)
    {
        $this->authorize('manageRequests', InventoryRequest::class);

        $invRequest = InventoryRequest::findOrFail($id);
        if ($invRequest->status !== 'Pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $invRequest->update([
            'status' => 'Rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Send notification to the requester
        if ($invRequest->user) {
            $invRequest->user->notify(new RequestStatusChangedNotification($invRequest));
        }

        return back()->with('success', 'Request rejected.');
    }
}
