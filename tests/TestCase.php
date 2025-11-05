<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    /**
     * Setup the test environment.
     * Desactiva observers para evitar efectos secundarios automáticos en tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Desactivar observers para evitar:
        // - WaiterProfile automático al crear User
        // - Otros efectos secundarios automáticos
        // Los tests deben crear datos explícitamente
        User::unsetEventDispatcher();
    }
}
