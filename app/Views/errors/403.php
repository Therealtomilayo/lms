<?php $this->layout('layouts/auth', ['title' => '403 - Access Denied']); ?>

<div class="text-center py-4">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-danger-100 text-danger-700 mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-slate-900 mb-2">Access Denied</h2>
    <p class="text-sm text-slate-600 mb-6">
        You do not have permission to view this resource or perform this action.
    </p>
    <a href="/login" class="inline-block py-2.5 px-6 rounded-lg text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 transition-colors">
        Return Home
    </a>
</div>
