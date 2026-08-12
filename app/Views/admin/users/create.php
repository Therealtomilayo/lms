<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Create New User Account</h2>
            <p class="text-sm text-slate-500">Provide account credentials, profile details, and role assignments.</p>
        </div>
        <a href="/admin/users" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
            Back to Directory
        </a>
    </div>

    <form method="POST" action="/admin/users" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
        <?= csrf_field() ?>

        <div class="border-b border-slate-200 pb-5">
            <h3 class="text-base font-semibold text-slate-900 mb-4">1. Primary Account Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. John Doe"
                           class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address *</label>
                    <input type="email" name="email" required placeholder="e.g. jdoe@claret.edu.ng"
                           class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone Number</label>
                    <input type="text" name="phone" placeholder="e.g. +234 801 234 5678"
                           class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Initial Password *</label>
                    <input type="password" name="password" required minlength="8" value="Password123!"
                           class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <p class="text-xs text-slate-500 mt-1">Minimum 8 characters. Defaults to Password123!</p>
                </div>
            </div>
        </div>

        <div class="border-b border-slate-200 pb-5">
            <h3 class="text-base font-semibold text-slate-900 mb-4">2. Role Allocations *</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?php if ($actor->hasRole('super_admin')): ?>
                    <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                        <input type="checkbox" name="roles[]" value="super_admin" class="rounded text-brand-600 focus:ring-brand-500">
                        <span class="text-sm font-medium text-slate-900">Super Admin</span>
                    </label>
                <?php endif; ?>
                <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="admin" class="rounded text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-900">Admin</span>
                </label>
                <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="teacher" class="rounded text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-900">Teacher</span>
                </label>
                <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="student" checked class="rounded text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-900">Student</span>
                </label>
                <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="parent" class="rounded text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-900">Parent / Guardian</span>
                </label>
            </div>
        </div>

        <div class="border-b border-slate-200 pb-5">
            <h3 class="text-base font-semibold text-slate-900 mb-4">3. Specialized Profile Metadata (Optional)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Student Admission No.</label>
                    <input type="text" name="admission_number" placeholder="e.g. STD-2026-001"
                           class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Teacher Staff ID</label>
                    <input type="text" name="staff_id" placeholder="e.g. TCH-0042"
                           class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Current Class (Students)</label>
                    <select name="current_class_id" class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        <option value="">-- No Class Selected --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= e($c->id) ?>"><?= e($c->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="/admin/users" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 shadow-sm transition">
                Create User Account
            </button>
        </div>
    </form>
</div>
