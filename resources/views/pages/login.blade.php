@extends('layouts.app')

@section('content')
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-card-header">
            <div class="d-inline-block mb-2">
                <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest" style="height:34px;">
            </div>
            <h4 class="fw-bold mb-0" style="letter-spacing:0.5px;">Legal Document System Monitoring</h4>
        </div>

        <div class="p-4 p-md-4">
            <form method="POST" action="<?= base_url('?page=login') ?>">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email / User Code</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-user-tag text-primary"></i></span>
                        <input type="text" name="identitas" class="form-control" placeholder="e.g. FJR or fjr.medquest@gmail.com" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password..." required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mt-1">
                    <i class="fas fa-sign-in-alt me-1"></i> Sign In
                </button>
            </form>

            <hr class="my-4">

            <div class="text-center">
                <p class="mb-2 text-muted small">Don't have an account yet or need to register your initial code &amp; email?</p>
                <a href="<?= base_url('?page=register') ?>" class="btn btn-sm btn-outline-primary py-1 px-4 fw-semibold" style="font-size:0.82rem;">
                    <i class="fas fa-user-plus me-1"></i> Create New Account
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
