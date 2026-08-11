@extends('layouts.admin-guest')

@section('content')
    <h1 class="mb-2 text-center text-lg font-semibold text-ink">{{ __('admin.auth.forgot_password_title') }}</h1>
    <p class="mb-6 text-center text-sm text-muted">{{ __('admin.auth.forgot_password_intro') }}</p>

    @if (session('status'))
        <x-ui.alert type="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @error('email')
        <x-ui.alert type="danger" class="mb-6">{{ $message }}</x-ui.alert>
    @enderror

    <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-5">
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

        <x-ui.button type="submit" full-width>
            {{ __('admin.auth.send_reset_link') }}
        </x-ui.button>
    </form>

    <a href="{{ route('admin.login') }}" class="mt-6 block text-center text-sm text-primary hover:underline">
        {{ __('admin.auth.back_to_login') }}
    </a>
@endsection
