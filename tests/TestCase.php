<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * El ERP y el POS piden login: por defecto los tests entran con un usuario.
     * Los que prueban el ingreso (AuthTest) lo apagan.
     */
    protected bool $signedIn = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->signedIn) {
            $this->actingAs(new User(['name' => 'Agus', 'email' => 'agus@example.com']));
        }
    }
}
