<?php $this->layout('layouts/auth', ['title' => '500 - Server Error']); ?>

<div class="text-center py-4">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-danger-100 text-danger-700 mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-slate-900 mb-2">Something Went Wrong</h2>
    <p class="text-sm text-slate-600 mb-6">
        An unexpected error occurred while processing your request. Our technical team has been notified.
    </p>
    <a href="/login" class="inline-block py-2.5 px-6 rounded-lg text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 transition-colors">
        Return to Safety
    </a>
</div>
