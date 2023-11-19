@extends('emails.master')

@section('content')
    <h1>Reset your password</h1>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    <p>If you did not request a password reset, no further action is required.</p>
    <div>{{ token }}</div>
    <p>This password reset link will expire in {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes.</p>
@endsection
