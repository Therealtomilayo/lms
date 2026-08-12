<?php $this->layout('layouts/auth', ['title' => '401 - Authentication Required']); ?>

<div class="text-center py-4">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-danger-100 text-danger-700 mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-slate-900 mb-2">Authentication Required</h2>
    <p class="text-sm text-slate-600 mb-6">
        You need to sign in with an active account to access this page.
    </p>
    <a href="/login" class="inline-block py-2.5 px-6 rounded-lg text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 transition-colors">
        Sign In
    </a>
</div>
