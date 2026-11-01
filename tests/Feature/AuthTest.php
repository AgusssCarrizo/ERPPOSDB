<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected bool $signedIn = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_erp_and_pos_require_login(): void
    {
        $this->get(route('erp.products.index'))->assertRedirect(route('login'));
        $this->get('/erp')->assertRedirect(route('login'));
        $this->get(route('pos.index'))->assertRedirect(route('login'));
        $this->patchJson(route('erp.products.featured', 1))->assertUnauthorized();
    }

    public function test_erp_entry_goes_to_products_when_logged_in(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/erp')
            ->assertRedirect('/erp/productos');
    }

    public function test_store_is_public(): void
    {
        $this->get(route('shop.home'))->assertOk();
        $this->get('/')->assertRedirect('/tienda');
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = User::factory()->create(['email' => 'agus@decobazar.com', 'password' => 'secreto123']);

        $this->get(route('login'))->assertOk()->assertSee('Ingresar al ERP');

        $this->post(route('login.store'), ['email' => 'agus@decobazar.com', 'password' => 'secreto123'])
            ->assertRedirect(route('erp.products.index'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_wrong_password_shows_error_in_spanish(): void
    {
        User::factory()->create(['email' => 'agus@decobazar.com', 'password' => 'secreto123']);

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => 'agus@decobazar.com', 'password' => 'otra-cosa'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'El email o la contraseña no son correctos.']);
        $this->assertGuest();
    }

    public function test_command_creates_user_and_changes_password(): void
    {
        $this->artisan('erp:usuario', ['email' => 'agus@decobazar.com', '--name' => 'Agus', '--password' => 'secreto123'])
            ->assertSuccessful();
        $this->assertTrue(auth()->attempt(['email' => 'agus@decobazar.com', 'password' => 'secreto123']));

        $this->artisan('erp:usuario', ['email' => 'agus@decobazar.com', '--password' => 'nueva-clave-1'])
            ->expectsOutput('Contraseña de agus@decobazar.com actualizada.')
            ->assertSuccessful();
        $this->assertSame(1, User::count());
        $this->assertSame('Agus', User::first()->name);
    }
}
