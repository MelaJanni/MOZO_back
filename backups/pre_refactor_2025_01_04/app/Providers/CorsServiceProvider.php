<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CorsServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // ULTRA AGRESIVO: Añadir headers CORS a TODA respuesta sin excepción
        $this->app->singleton('cors.handler', function () {
            return function ($request, $response) {
                if ($response) {
                    $response->headers->set('Access-Control-Allow-Origin', '*');
                    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD');
                    $response->headers->set('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Origin');
                    $response->headers->set('Access-Control-Allow-Credentials', 'true');
                    $response->headers->set('Access-Control-Max-Age', '86400');
                }
                return $response;
            };
        });

        // Hook en el response macro para asegurar CORS
        $this->app['events']->listen('Illuminate\Foundation\Http\Events\RequestHandled', function ($event) {
            $handler = $this->app['cors.handler'];
            $handler($event->request, $event->response);
        });
    }
}