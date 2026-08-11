@extends('layouts.admin-guest')

@section('content')
    <h1 class="mb-6 text-center text-lg font-semibold text-ink">{{ __('admin.auth.reset_password_title') }}</h1>

    @error('email')
        <x-ui.alert type="danger" class="mb-6">{{ $message }}</x-ui.alert>
    @enderror

    <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <x-form.input
            name="email"
            type="email"
            dir="ltr"
            :label="__('admin.auth.email')"
            :value="old('email', $email)"
            required
            autofocus
            autocomplete="username"
        />

        <x-form.input
            name="password"
            type="password"
            dir="ltr"
            :label="__('admin.auth.new_password')"
            required
            autocomplete="new-password"
        />

        <x-form.input
            name="password_confirmation"
            type="password"
            dir="ltr"
            :label="__('admin.auth.confirm_password')"
            required
            autocomplete="new-password"
        />

        <x-ui.button type="submit" full-width>
            {{ __('admin.auth.reset_button') }}
        </x-ui.button>
    </form>
@endsection
