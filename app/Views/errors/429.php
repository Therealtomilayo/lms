<?php $this->layout('layouts/auth', ['title' => '429 - Too Many Requests']); ?>

<div class="text-center py-4">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 text-amber-700 mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-slate-900 mb-2">Too Many Requests</h2>
    <p class="text-sm text-slate-600 mb-6">
        You have sent too many requests in a short period. Please wait <?php echo htmlspecialchars((string)($retryAfter ?? 60), ENT_QUOTES, 'UTF-8'); ?> seconds before trying again.
    </p>
    <a href="/login" class="inline-block py-2.5 px-6 rounded-lg text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 transition-colors">
        Return Home
    </a>
</div>
