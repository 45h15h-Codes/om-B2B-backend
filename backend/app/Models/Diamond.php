<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diamond extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';

    /**
     * The boolean fields used for toggles.
     */
    public const BOOLEAN_FIELDS = [
        'show_on_OM',
        'fancy_color_enabled',
        'advance_shape_enabled',
        'is_matched_pair',
        'is_pair_separable',
        'allow_download',
        'is_parcel',
    ];

    /**
     * The request-key to database-column mappings for multi-select filters.
     */
    public const MULTI_SELECT_FILTERS = [
        'shapes' => 'shape',
        'colors' => 'color',
        'clarities' => 'clarity',
        'cuts' => 'cut',
        'polishes' => 'polish',
        'symmetries' => 'symmetry',
        'fluorescences' => 'fluorescence_intensity',
        'labs' => 'lab',
        'sellers' => 'created_by',
    ];

    /**
     * The prefix-key to database-column mappings for range filters.
     */
    public const RANGE_FILTERS = [
        'size' => 'size',
        'total_size' => 'size',
        'piece_per_carat' => 'number_of_diamonds',
        'price_ct' => 'asking_price',
        'price_total' => 'cash_price',
    ];

    /**
     * The physical columns that actually exist in the diamonds database table.
     */
    public const PHYSICAL_COLUMNS = [
        'stock_no',
        'asking_price',
        'asking_price_unit',
        'cash_price',
        'cash_price_unit',
        'availability',
        'country',
        'state',
        'city',
        'shape',
        'size',
        'color',
        'clarity',
        'show_on_OM',
        'is_matched_pair',
        'is_parcel',
        'number_of_diamonds',
        'status',
        'inventory_status',
        'created_by',
        'user_id',
        'assigned_admin_id',
        'hold_by',
        'hold_reason',
        'hold_at',
        'hold_shopify_store_id',
        'sold_store_id',
        'sold_at',
        'sold_by_store_id',
        'sold_by_store_name',
        'sold_by_user_id',
        'sold_order_number',
        'sold_order_date',
        'specifications',
    ];

    /**
     * The virtual specification fields stored inside the specifications JSON column.
     */
    public const VIRTUAL_FIELDS = [
        'cut',
        'polish',
        'symmetry',
        'fluorescence_intensity',
        'fluorescence_color',
        'length',
        'width',
        'depth',
        'depth_percent',
        'table_percent',
        'crown_angle',
        'crown_height',
        'pavilion_angle',
        'pavilion_depth',
        'girdle_condition',
        'girdle_min',
        'girdle_max',
        'girdle_percent',
        'culet_condition',
        'culet_size',
        'treatment',
        'laser_inscription',
        'star_length',
        'lab',
        'report_no',
        'report_date',
        'lab_location',
        'report_comment',
        'key_to_symbols',
        'fancy_color_enabled',
        'fancy_color_intensity',
        'fancy_color_overtone',
        'fancy_color_color1',
        'fancy_color_color2',
        'advance_shape_enabled',
        'advance_shape_detail',
        'matched_pair_stock_no',
        'is_pair_separable',
        'allow_download',
        'trade_show',
        'brand',
        'supplier_comment',
        'report_file',
        'report_link',
        'diamond_image',
        'diamond_image_link',
        'sarine_loupe',
        'additional_comments',
        'internal_notes',
        'appraisal_value',
    ];

    protected $fillable = self::PHYSICAL_COLUMNS;

    protected $casts = [
        'specifications' => 'array',
        'show_on_OM' => 'boolean',
        'is_matched_pair' => 'boolean',
        'is_parcel' => 'boolean',
        'asking_price' => 'decimal:2',
        'cash_price' => 'decimal:2',
        'size' => 'decimal:3',
        'user_id' => 'integer',
        'assigned_admin_id' => 'integer',
        'hold_by' => 'integer',
        'hold_shopify_store_id' => 'integer',
        'sold_store_id' => 'integer',
        'sold_by_store_id' => 'integer',
        'sold_by_user_id' => 'integer',
        'hold_at' => 'datetime',
        'sold_at' => 'datetime',
        'sold_order_date' => 'datetime',
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
        $value = $specs[$key] ?? null;

        if (!is_null($value)) {
            if (in_array($key, ['fancy_color_enabled', 'is_pair_separable', 'allow_download', 'advance_shape_enabled'])) {
                return (bool) $value;
            }
            if (in_array($key, ['depth_percent', 'table_percent', 'crown_angle', 'crown_height', 'pavilion_angle', 'pavilion_depth', 'girdle_percent', 'star_length', 'appraisal_value'])) {
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
        $specs[$key] = $value;
        return parent::setAttribute('specifications', $specs);
    }

    /**
     * Get the list of boolean fields.
     */
    public static function getBooleanFields(): array
    {
        return self::BOOLEAN_FIELDS;
    }

    /**
     * Get the multi-select filters configuration.
     */
    public static function getMultiSelectFilters(): array
    {
        return self::MULTI_SELECT_FILTERS;
    }

    /**
     * Get the range filters configuration.
     */
    public static function getRangeFilters(): array
    {
        return self::RANGE_FILTERS;
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

    public function holdShopifyStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'hold_shopify_store_id');
    }

    public function soldStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'sold_store_id');
    }

    public function soldByStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'sold_by_store_id');
    }

    public function soldByUser()
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }

    public function storeAssignments()
    {
        return $this->hasMany(DiamondStoreAssignment::class, 'diamond_id');
    }

    public function assignedStores()
    {
        return $this->belongsToMany(ShopifyStore::class, 'diamond_store_assignments', 'diamond_id', 'shopify_store_id')
            ->withPivot('is_published')
            ->withTimestamps();
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
                    ->join('diamonds', 'diamonds.user_id', '=', 'users.id')
                    ->whereColumn('diamonds.id', 'shopify_products.product_id')
                    ->limit(1);
            });
    }

    public function inventoryHistories()
    {
        return $this->morphMany(InventoryHistory::class, 'product');
    }

    protected static function booted()
    {
        static::created(function ($diamond) {
            \App\Services\AuditService::log(
                'create_diamond',
                \App\Models\Diamond::class,
                $diamond->id,
                $diamond->only(['stock_no', 'asking_price', 'shape', 'size', 'color', 'clarity'])
            );
        });

        static::updated(function ($diamond) {
            $action = 'update_diamond';
            if ($diamond->isDirty('inventory_status')) {
                $action = 'inventory_status_change';
            }
            \App\Services\AuditService::log(
                $action,
                \App\Models\Diamond::class,
                $diamond->id,
                [
                    'old' => array_intersect_key($diamond->getOriginal(), $diamond->getDirty()),
                    'new' => $diamond->getDirty()
                ]
            );
        });

        static::saved(function ($diamond) {
            if ($diamond->inventory_status !== 'available') {
                return;
            }
            $assignments = $diamond->storeAssignments()->where('is_published', true)->get();
            foreach ($assignments as $assignment) {
                \App\Jobs\PublishDiamondToShopifyJob::dispatch($diamond->id, $assignment->shopify_store_id);
            }
        });

        static::deleted(function ($diamond) {
            \App\Services\AuditService::log(
                'delete_diamond',
                \App\Models\Diamond::class,
                $diamond->id,
                ['stock_no' => $diamond->stock_no]
            );

            $shopifyProducts = $diamond->shopifyProducts;
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


