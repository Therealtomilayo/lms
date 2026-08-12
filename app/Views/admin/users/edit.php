<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Edit User: <?= e($user->name) ?></h2>
            <p class="text-sm text-slate-500">Update contact info, role allocations, or reset security credentials.</p>
        </div>
        <a href="/admin/users" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
            Back to Directory
        </a>
    </div>

    <!-- Update Form -->
    <form method="POST" action="/admin/users/<?= e($user->id) ?>" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
        <?= csrf_field() ?>

        <div class="border-b border-slate-200 pb-5">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Account Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name *</label>
                    <input type="text" name="name" required value="<?= e($user->name) ?>"
                           class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address *</label>
                    <input type="email" name="email" required value="<?= e($user->email) ?>"
                           class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone Number</label>
                    <input type="text" name="phone" value="<?= e($user->phone ?? '') ?>"
                           class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Account Status</label>
                    <select name="status" class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        <option value="active" <?= $user->status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user->status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= $user->status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="border-b border-slate-200 pb-5">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Role Allocations</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?php if ($actor->hasRole('super_admin')): ?>
                    <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                        <input type="checkbox" name="roles[]" value="super_admin" <?= $user->hasRole('super_admin') ? 'checked' : '' ?> class="rounded text-brand-600 focus:ring-brand-500">
                        <span class="text-sm font-medium text-slate-900">Super Admin</span>
                    </label>
                <?php endif; ?>
                <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="admin" <?= $user->hasRole('admin') ? 'checked' : '' ?> class="rounded text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-900">Admin</span>
                </label>
                <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="teacher" <?= $user->hasRole('teacher') ? 'checked' : '' ?> class="rounded text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-900">Teacher</span>
                </label>
                <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="student" <?= $user->hasRole('student') ? 'checked' : '' ?> class="rounded text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-900">Student</span>
                </label>
                <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="parent" <?= $user->hasRole('parent') ? 'checked' : '' ?> class="rounded text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-900">Parent / Guardian</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="/admin/users" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 shadow-sm transition">
                Save Changes
            </button>
        </div>
    </form>

    <!-- Administrative Password Reset -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="text-base font-semibold text-slate-900">Administrative Password Reset</h3>
        <p class="text-xs text-slate-500">Assign a new password and immediately revoke all active browser sessions for this user.</p>
        <form method="POST" action="/admin/users/<?= e($user->id) ?>/reset-password" class="flex flex-col sm:flex-row items-center gap-3">
            <?= csrf_field() ?>
            <input type="password" name="password" required minlength="8" placeholder="New Temporary Password"
                   class="w-full sm:w-80 px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-slate-800 text-white text-sm font-semibold rounded-lg hover:bg-slate-900 transition">
                Reset Password & Revoke Sessions
            </button>
        </form>
    </div>
</div>
