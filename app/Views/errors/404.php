<?php $this->layout('layouts/auth', ['title' => '404 - Page Not Found']); ?>

<div class="text-center py-4">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-600 mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-slate-900 mb-2">Page Not Found</h2>
    <p class="text-sm text-slate-600 mb-6">
        The requested page does not exist or has been moved.
    </p>
    <a href="/login" class="inline-block py-2.5 px-6 rounded-lg text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 transition-colors">
        Return Home
    </a>
</div>
