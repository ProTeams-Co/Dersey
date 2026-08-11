@extends('layouts.admin-guest')

@section('content')
    <h1 class="mb-6 text-center text-lg font-semibold text-ink">{{ __('admin.auth.title') }}</h1>

    @if (session('status'))
        <x-ui.alert type="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @error('email')
        <x-ui.alert type="danger" class="mb-6">{{ $message }}</x-ui.alert>
    @enderror

    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
        @csrf

        <x-form.input
            name="email"
            type="email"
            dir="ltr"
            :label="__('admin.auth.email')"
            :value="old('email')"
            required
            autofocus
            autocomplete="username"
        />

        <x-form.input
            name="password"
            type="password"
            dir="ltr"
            :label="__('admin.auth.password')"
            required
            autocomplete="current-password"
        />

        <div class="flex items-center justify-between">
            <x-form.checkbox name="remember" :label="__('admin.auth.remember_me')" />

            <a href="{{ route('admin.password.request') }}" class="text-sm text-primary hover:underline">
                {{ __('admin.auth.forgot_password') }}
            </a>
        </div>

        <x-ui.button type="submit" full-width>
            {{ __('admin.auth.login_button') }}
        </x-ui.button>
    </form>
@endsection
