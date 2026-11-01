<!DOCTYPE html>
<html lang="es-AR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <title>Ingresar · {{ config('app.name') }}</title>

    @fonts('instrument-sans')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid h-full place-items-center bg-zinc-50 px-4 text-zinc-900 antialiased">
    <main class="w-full max-w-sm">
        <div class="flex flex-col items-center text-center">
            <img src="{{ asset('images/logo-decobazar.png') }}" alt="DecoBazar" class="size-14">
            <h1 class="mt-4 text-xl font-semibold tracking-tight">Ingresar al ERP</h1>
            <p class="mt-1 text-sm text-zinc-500">Productos, inventario, compras y punto de venta.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="card mt-8 space-y-5 p-6">
            @csrf

            <x-form.field label="Email" for="email">
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-input" required autofocus autocomplete="username">
            </x-form.field>

            <x-form.field label="Contraseña" for="password">
                <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
            </x-form.field>

            <label class="flex items-center gap-2 text-sm text-zinc-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-zinc-300" @checked(old('remember'))>
                Mantener la sesión iniciada
            </label>

            <button type="submit" class="btn-primary w-full">Ingresar</button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-500">
            <a href="{{ route('shop.home') }}" class="hover:text-zinc-900">← Ir a la tienda</a>
        </p>
    </main>
</body>
</html>
