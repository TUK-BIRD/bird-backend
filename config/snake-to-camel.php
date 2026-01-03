<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable or Disable Response Conversion
    |--------------------------------------------------------------------------
    |
    | When set to true, all response keys will be converted from snake_case
    | to camelCase automatically.
    |
    */
    'convert_response' => true,

    /*
    |--------------------------------------------------------------------------
    | Enable or Disable Request Conversion
    |--------------------------------------------------------------------------
    |
    | When set to true, all Request keys will be converted from camelCase
    | to snake_case automatically.
    |
    */
    'convert_request'  => true, // Convert camelCase to snake_case in incoming requests


    /*
    |--------------------------------------------------------------------------
    | Apply Middleware To
    |--------------------------------------------------------------------------
    |
    | This option determines where the middleware should be applied:
    | - all: Applies to all routes (web + api)
    | - api: Applies only to routes in the "api" middleware group
    |
    */
    'apply_to' => 'all', // Options: 'all', 'api'
];