<?php

return [
    'api_version' => env('SHOPIFY_API_VERSION', '2025-10'),
    'api_key'     => env('SHOPIFY_API_KEY'),
    'api_secret'  => env('SHOPIFY_API_SECRET'),
    'scopes'      => env('SHOPIFY_SCOPES', 'write_products,read_products,write_inventory,read_inventory,write_orders,read_orders,read_all_orders'),
];
