<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Pantalla de ingreso al ERP y al punto de venta. La tienda es pública y no la usa.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ingresá tu email.',
            'email.email' => 'El email no es válido.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'El email o la contraseña no son correctos.',
            ]);
        }

        // Sesión nueva al ingresar: evita que alguien reutilice una sesión anterior.
        $request->session()->regenerate();

        return redirect()->intended(route('erp.products.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
