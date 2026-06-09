<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Background Jobs Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration mapping for various background jobs
    | used in the Diamond Management System. You can register new background
    | jobs here by adding their snake_case keys and their display names.
    |
    */

    'diamond_upload' => [
        'name' => 'Diamond Upload',
    ],

    'diamond_update' => [
        'name' => 'Diamond Update',
    ],

    'diamond_delete' => [
        'name' => 'Diamond Delete',
    ],

    'diamond_approve' => [
        'name' => 'Diamond Approve',
    ],

    'diamond_reject' => [
        'name' => 'Diamond Reject',
    ],

    'diamond_import' => [
        'name' => 'Diamond Import',
    ],

    'bulk_diamond_upload' => [
        'name' => 'Bulk Diamond Upload',
    ],

    'bulk_diamond_delete' => [
        'name' => 'Bulk Diamond Delete',
    ],

    'image_upload' => [
        'name' => 'Image Upload',
    ],

    'image_delete' => [
        'name' => 'Image Delete',
    ],

    'cloudinary_upload' => [
        'name' => 'Cloudinary Upload',
    ],

    'cloudinary_delete' => [
        'name' => 'Cloudinary Delete',
    ],

    'diamond_export' => [
        'name' => 'Diamond Export',
    ],

    'search_index_rebuild' => [
        'name' => 'Search Index Rebuild',
    ],

];
