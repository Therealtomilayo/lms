<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Edit Student Dossier — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Edit Student Dossier'
]);

$dob = $student['date_of_birth'] ?? '';
$gender = strtolower((string)($student['gender'] ?? 'male'));
$house = $student['house'] ?? 'Blue';
$currentClassId = (int)($student['current_class_id'] ?? 0);
?>

<div class="space-y-6 max-w-4xl mx-auto pb-12">

    <!-- Navigation Breadcrumb -->
    <div>
        <a href="/admin/students/<?= (int)$student['id'] ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Student Profile</span>
        </a>
    </div>

    <!-- Edit Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-brand-900 to-brand-800 text-white flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-white">Edit Student Academic & Demographic Dossier</h2>
                <p class="text-xs text-brand-200 mt-0.5">Manage official profile parameters, institutional House allocation, and academic placement.</p>
            </div>
            <span class="px-2.5 py-1 rounded-md bg-brand-700/80 text-brand-100 font-mono text-xs font-bold">
                <?= e($student['admission_number']) ?>
            </span>
        </div>

        <form method="POST" action="/admin/students/<?= (int)$student['id'] ?>" class="p-6 sm:p-8 space-y-6">
            <?= csrf_field() ?>

            <!-- Basic Identification -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100">
                    Basic Identification & Cohort
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Student Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="name" value="<?= e($student['user_name']) ?>" required
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-semibold text-slate-900">
                    </div>

                    <div>
                        <label for="admission_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Admission Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="admission_number" id="admission_number" value="<?= e($student['admission_number']) ?>" required
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-mono font-bold text-slate-900">
                    </div>

                    <div>
                        <label for="current_class_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Assigned Class Arm</label>
                        <select name="current_class_id" id="current_class_id"
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-medium text-slate-800">
                            <option value="">— Select Class —</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int)$c->id ?>" <?= $currentClassId === (int)$c->id ? 'selected' : '' ?>>
                                    <?= e($c->name . ($c->sectionArm ? ' (' . $c->sectionArm . ')' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="gender" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Gender <span class="text-rose-500">*</span></label>
                        <select name="gender" id="gender" required
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-medium text-slate-800">
                            <option value="male" <?= $gender === 'male' || $gender === 'boy' ? 'selected' : '' ?>>Male (Master)</option>
                            <option value="female" <?= $gender === 'female' || $gender === 'girl' ? 'selected' : '' ?>>Female (Lady)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- High School & Institutional Allocation -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100">
                    Institutional House & Demographics
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="house" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">School House <span class="text-rose-500">*</span></label>
                        <select name="house" id="house" required
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-bold text-slate-900">
                            <option value="Blue" <?= $house === 'Blue' ? 'selected' : '' ?>>Blue House</option>
                            <option value="Green" <?= $house === 'Green' ? 'selected' : '' ?>>Green House</option>
                            <option value="Red" <?= $house === 'Red' ? 'selected' : '' ?>>Red House</option>
                            <option value="Yellow" <?= $house === 'Yellow' ? 'selected' : '' ?>>Yellow House</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Appears on High School Report Sheets.</p>
                    </div>

                    <div>
                        <label for="date_of_birth" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" value="<?= e($dob) ?>"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-medium text-slate-800">
                        <p class="text-[11px] text-slate-400 mt-1">Automatically calculates student age on report cards.</p>
                    </div>

                    <div>
                        <label for="nationality" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nationality</label>
                        <input type="text" name="nationality" id="nationality" value="<?= e($student['nationality'] ?? 'Nigerian') ?>"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-medium text-slate-800">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="state_of_origin" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">State of Origin</label>
                        <input type="text" name="state_of_origin" id="state_of_origin" value="<?= e($student['state_of_origin'] ?? '') ?>"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-medium text-slate-800">
                    </div>

                    <div>
                        <label for="lga" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Local Govt. Area (L.G.A)</label>
                        <input type="text" name="lga" id="lga" value="<?= e($student['lga'] ?? '') ?>"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-medium text-slate-800">
                    </div>

                    <div>
                        <label for="religion" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Religion</label>
                        <input type="text" name="religion" id="religion" value="<?= e($student['religion'] ?? 'Christianity') ?>"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-medium text-slate-800">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="pt-6 border-t border-slate-200 flex items-center justify-end gap-3">
                <a href="/admin/students/<?= (int)$student['id'] ?>"
                   class="px-5 py-2.5 rounded-xl text-xs font-bold bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 transition shadow-xs">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl text-xs font-bold bg-brand-700 hover:bg-brand-800 text-white transition flex items-center gap-2 shadow-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Save Student Changes</span>
                </button>
            </div>
        </form>
    </div>

</div>
