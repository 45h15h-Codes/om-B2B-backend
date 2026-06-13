<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jewelery extends Model
{
    use HasFactory;

    protected $table = 'jeweleries';

    public const STATUS_PENDING = 'Pending';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';

    /**
     * The physical columns in the jeweleries database table.
     */
    public const PHYSICAL_COLUMNS = [
        'sku',
        'name',
        'type',
        'price',
        'image_url',
        'location',
        'created_by',
        'user_id',
        'assigned_admin_id',
        'hold_by',
        'hold_reason',
        'hold_at',
        'status',
        'inventory_status',
        'specifications',
        'images',
        'videos',
    ];

    /**
     * The virtual specifications fields stored inside the specifications JSON column.
     */
    public const VIRTUAL_FIELDS = [
        'is_available',
        'is_available_for_memo',
        'type_style',
        'category',
        'condition',
        'brand',
        'quality',
        'metal_type',
        'metal_karat',
        'total_weight',
        'gemstone_type',
        'gemstone_shape',
        'carat_weight',
        'lab',
        'lab_no',
        'lot_no',
        'description',
        'designer',
        'keywords',
        'shipping_from',
        'currency',
        'terms',
        'msrp',
        'delivery_time',
        'in_stock',
        'min_order',
        'treatment',
        'treatment_yes_no',
        'stone_count',
        'supplier_comment',
        'size',
        'ring_size',
        'is_unpublished',
        'is_shareable',
        'is_own_stock',
    ];

    protected $fillable = self::PHYSICAL_COLUMNS;

    protected $casts = [
        'specifications' => 'array',
        'images' => 'array',
        'videos' => 'array',
        'price' => 'decimal:2',
        'user_id' => 'integer',
        'assigned_admin_id' => 'integer',
        'hold_by' => 'integer',
        'hold_at' => 'datetime',
    ];

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        return array_merge(self::PHYSICAL_COLUMNS, self::VIRTUAL_FIELDS);
    }

    /**
     * Override isFillable to allow dynamic virtual fields mass assignment.
     */
    public function isFillable($key)
    {
        return in_array($key, self::PHYSICAL_COLUMNS) || in_array($key, self::VIRTUAL_FIELDS);
    }

    /**
     * Intercept attribute retrieval to support virtual properties from specifications column.
     */
    public function getAttribute($key)
    {
        if (in_array($key, self::PHYSICAL_COLUMNS) || 
            in_array($key, ['id', 'created_at', 'updated_at']) ||
            $this->hasGetMutator($key) || 
            $this->relationLoaded($key) || 
            method_exists($this, $key)) {
            return parent::getAttribute($key);
        }

        $specs = $this->getAttributeValue('specifications') ?? [];
        
        // Handle defaults for booleans if not set
        if (!array_key_exists($key, $specs)) {
            if ($key === 'is_available') {
                return true;
            }
            if ($key === 'is_available_for_memo') {
                return false;
            }
            return null;
        }

        $value = $specs[$key];

        if (!is_null($value)) {
            if (in_array($key, ['is_available', 'is_available_for_memo', 'treatment_yes_no', 'is_unpublished', 'is_shareable', 'is_own_stock'])) {
                return (bool) $value;
            }
            if (in_array($key, ['total_weight', 'carat_weight', 'msrp'])) {
                return (float) $value;
            }
        }

        return $value;
    }

    /**
     * Intercept attribute setting to serialize virtual properties inside specifications column.
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, self::PHYSICAL_COLUMNS) || 
            in_array($key, ['id', 'created_at', 'updated_at']) ||
            $this->hasSetMutator($key)) {
            return parent::setAttribute($key, $value);
        }

        $specs = $this->getAttributeValue('specifications') ?? [];
        $valueCast = $value;
        
        // Cast boolean values when storing in json specifications
        if (in_array($key, ['is_available', 'is_available_for_memo', 'treatment_yes_no', 'is_unpublished', 'is_shareable', 'is_own_stock'])) {
            $valueCast = (bool) $value;
        }
        
        // Cast float values when storing in json specifications
        if (in_array($key, ['total_weight', 'carat_weight', 'msrp'])) {
            $valueCast = is_null($value) ? null : (float) $value;
        }

        $specs[$key] = $valueCast;
        return parent::setAttribute('specifications', $specs);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function holdBy()
    {
        return $this->belongsTo(User::class, 'hold_by');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function shopifyProducts()
    {
        return $this->morphMany(ShopifyProduct::class, 'product');
    }

    public function shopifyProduct()
    {
        return $this->morphOne(ShopifyProduct::class, 'product')
            ->where('shopify_products.shopify_store_id', '=', function ($query) {
                $query->select('users.active_shopify_store_id')
                    ->from('users')
                    ->join('jeweleries', 'jeweleries.user_id', '=', 'users.id')
                    ->whereColumn('jeweleries.id', 'shopify_products.product_id')
                    ->limit(1);
            });
    }

    public function inventoryHistories()
    {
        return $this->morphMany(InventoryHistory::class, 'product');
    }

    protected static function booted()
    {
        static::created(function ($jewelry) {
            \App\Services\AuditService::log(
                'create_jewelry',
                \App\Models\Jewelery::class,
                $jewelry->id,
                $jewelry->only(['sku', 'name', 'type', 'price', 'location'])
            );
        });

        static::updated(function ($jewelry) {
            $action = 'update_jewelry';
            if ($jewelry->isDirty('inventory_status')) {
                $action = 'inventory_status_change';
            }
            \App\Services\AuditService::log(
                $action,
                \App\Models\Jewelery::class,
                $jewelry->id,
                [
                    'old' => array_intersect_key($jewelry->getOriginal(), $jewelry->getDirty()),
                    'new' => $jewelry->getDirty()
                ]
            );
        });

        static::saved(function ($jewelry) {
            if ($jewelry->inventory_status !== 'available') {
                return;
            }
            $activeStore = $jewelry->user ? $jewelry->user->activeShopifyStore : null;
            if ($activeStore) {
                \App\Jobs\PublishJewelryToShopifyJob::dispatch($jewelry->id, $activeStore->id);
            }
        });

        static::deleted(function ($jewelry) {
            \App\Services\AuditService::log(
                'delete_jewelry',
                \App\Models\Jewelery::class,
                $jewelry->id,
                ['sku' => $jewelry->sku]
            );

            $shopifyProducts = $jewelry->shopifyProducts;
            foreach ($shopifyProducts as $shopifyProduct) {
                if ($shopifyProduct->shopify_product_id && $shopifyProduct->shopify_store_id) {
                    \App\Jobs\DeleteProductFromShopifyJob::dispatch(
                        $shopifyProduct->shopify_product_id,
                        $shopifyProduct->shopify_store_id
                    );
                }
                $shopifyProduct->delete();
            }
        });
    }
}

