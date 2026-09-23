<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
                <a href="/admin/dashboard" class="text-slate-400 hover:text-brand-600 transition">Administration</a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700">Settings</span>
            </nav>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Institutional Identity &amp; Report Card Settings</h1>
            <p class="text-xs text-slate-500 mt-1">
                Configure school profile, institutional address, contact channels, and official report card endorsement credentials (signatures, seals, and stamp).
            </p>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/settings" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <!-- 1. School Information Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#0C9DD5]"></span>
                    1. School Institutional Profile
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Used across official documents, report cards, portal footers, and invoices.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">School Name *</label>
                    <input type="text" name="school_name" required value="<?= htmlspecialchars($settings['school_name']) ?>"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 font-semibold text-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Motto</label>
                    <input type="text" name="school_motto" value="<?= htmlspecialchars($settings['school_motto']) ?>"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-slate-800">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Campus Physical Address *</label>
                    <input type="text" name="school_address" required value="<?= htmlspecialchars($settings['school_address']) ?>"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-slate-800">
                    <p class="text-[11px] text-slate-500 mt-1">E.g. Plot 700 Gitto Street, After Zeus Paradise Hotel & Mall, Mabushi, Abuja, Nigeria.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Official Telephone *</label>
                    <input type="text" name="school_phone" required value="<?= htmlspecialchars($settings['school_phone']) ?>"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Official Email *</label>
                    <input type="email" name="school_email" required value="<?= htmlspecialchars($settings['school_email']) ?>"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Website Domain</label>
                    <input type="text" name="school_website" value="<?= htmlspecialchars($settings['school_website']) ?>"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-slate-800">
                </div>

                <div class="sm:col-span-2 p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <span class="block text-xs font-bold uppercase tracking-wider text-slate-700">Official School Crest / Emblem</span>
                    
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="h-20 w-20 border border-slate-300 bg-white rounded-xl p-2 flex items-center justify-center shrink-0 shadow-2xs">
                            <?php if (!empty($settings['school_logo_url'])): ?>
                                <img src="<?= htmlspecialchars($settings['school_logo_url']) ?>" alt="School Crest" class="max-h-full max-w-full object-contain" onerror="this.onerror=null; this.src='/favicon.ico';">
                            <?php else: ?>
                                <span class="text-[10px] text-slate-400 italic text-center">No Crest</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 space-y-2">
                            <div>
                                <label class="inline-block text-xs font-semibold text-slate-600">Upload New School Crest (PNG / JPG / SVG / WebP)</label>
                                <input type="file" name="logo_file" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 cursor-pointer">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-500 mb-0.5">Or Direct Asset URL / File Path</label>
                                <input type="text" name="school_logo_url" value="<?= htmlspecialchars($settings['school_logo_url']) ?>"
                                       placeholder="/assets/img/logo.png"
                                       class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-sky-500 text-slate-800">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Report Card Attestation & Endorsement Credentials Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    2. Official Report Card Attestation &amp; Endorsement
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Defines the executive sign-off authority and digital stamp overlays rendered on terminal report cards.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Head Teacher / Principal Name *</label>
                    <input type="text" name="head_teacher_name" required value="<?= htmlspecialchars($settings['head_teacher_name']) ?>"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 font-bold text-slate-800">
                    <p class="text-[11px] text-slate-500 mt-1">E.g. Mrs. N. Okon or Rev. Sr. Administrator</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Official Executive Title *</label>
                    <input type="text" name="head_teacher_title" required value="<?= htmlspecialchars($settings['head_teacher_title']) ?>"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-slate-800">
                    <p class="text-[11px] text-slate-500 mt-1">E.g. Head of School, Principal, or Director of Studies</p>
                </div>

                <!-- Head Teacher Signature -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <span class="block text-xs font-bold uppercase tracking-wider text-slate-700">Head Teacher Signature</span>
                    
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-32 border border-slate-300 bg-white rounded-lg p-2 flex items-center justify-center">
                            <?php if (!empty($settings['head_teacher_signature_url'])): ?>
                                <img src="<?= htmlspecialchars($settings['head_teacher_signature_url']) ?>" alt="Signature" class="max-h-full max-w-full object-contain">
                            <?php else: ?>
                                <span class="text-[10px] text-slate-400 italic">No signature</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <label class="inline-block text-xs font-semibold text-slate-600">Upload New Signature</label>
                            <input type="file" name="signature_file" accept="image/*" class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 cursor-pointer">
                            <input type="hidden" name="head_teacher_signature_url" value="<?= htmlspecialchars($settings['head_teacher_signature_url']) ?>">
                        </div>
                    </div>
                </div>

                <!-- Official Circular Seal Stamp -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <span class="block text-xs font-bold uppercase tracking-wider text-slate-700">Official Circular Seal Stamp</span>
                    
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 border border-slate-300 bg-white rounded-lg p-1.5 flex items-center justify-center">
                            <?php if (!empty($settings['school_stamp_url'])): ?>
                                <img src="<?= htmlspecialchars($settings['school_stamp_url']) ?>" alt="Stamp" class="max-h-full max-w-full object-contain">
                            <?php else: ?>
                                <span class="text-[10px] text-slate-400 italic">No stamp</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <label class="inline-block text-xs font-semibold text-slate-600">Upload New Seal Stamp</label>
                            <input type="file" name="stamp_file" accept="image/*" class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 cursor-pointer">
                            <input type="hidden" name="school_stamp_url" value="<?= htmlspecialchars($settings['school_stamp_url']) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Submission Button -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="submit" 
                    class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider bg-[#0C9DD5] hover:bg-sky-500 text-white shadow-md shadow-sky-500/20 transition cursor-pointer">
                Save Institutional Settings
            </button>
        </div>
    </form>
</div>
