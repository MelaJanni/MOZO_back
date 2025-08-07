<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    public function qrApis()
    {
        $apis = [
            'QR System APIs' => [
                [
                    'method' => 'GET',
                    'endpoint' => '/test-qr',
                    'description' => 'Test QR system functionality',
                    'response' => [
                        'status' => 'success',
                        'message' => 'QR System is working!',
                        'timestamp' => '2024-01-01T12:00:00Z'
                    ]
                ],
                [
                    'method' => 'GET',
                    'endpoint' => '/QR/{restaurant_slug}/{table_code}',
                    'description' => 'Display QR table page with menu and call waiter functionality',
                    'parameters' => [
                        'restaurant_slug' => 'Restaurant identifier (e.g., mcdonalds)',
                        'table_code' => 'Table unique code (e.g., aVnyOv)'
                    ],
                    'example' => '/QR/mcdonalds/aVnyOv'
                ],
                [
                    'method' => 'GET',
                    'endpoint' => '/api/qr/{restaurant_slug}/{table_code}',
                    'description' => 'Get table and restaurant information via JSON API',
                    'parameters' => [
                        'restaurant_slug' => 'Restaurant identifier',
                        'table_code' => 'Table unique code'
                    ],
                    'response' => [
                        'success' => true,
                        'data' => [
                            'restaurant' => [
                                'id' => 1,
                                'name' => 'McDonald\'s',
                                'slug' => 'mcdonalds',
                                'logo' => 'logo.png',
                                'menu_pdf' => 'menu.pdf'
                            ],
                            'table' => [
                                'id' => 1,
                                'name' => 'Mesa 1',
                                'code' => 'aVnyOv',
                                'number' => 1
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return response()->json($apis, 200, [], JSON_PRETTY_PRINT);
    }

    public function waiterApis()
    {
        return response()->json([
            'message' => 'Waiter notification APIs documentation',
            'note' => 'Use existing waiter notification endpoints for call waiter functionality'
        ], 200, [], JSON_PRETTY_PRINT);
    }
}