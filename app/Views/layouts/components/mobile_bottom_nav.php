<?php
/**
 * Mobile Bottom Navigation Bar & Student Academic Hub Sheet
 * 
 * Optimized for touch devices following UI-UX Pro Max standards:
 * - 48x48px touch targets
 * - Smooth cubic-bezier spring bottom sheet modal
 * - Safe area inset compliance (iOS home bar & Android gesture bar)
 * - WCAG AA contrast & screen-reader accessibility
 * 
 * @var string $role User role: student|parent|teacher
 * @var array|null $children Linked children (parent portal)
 * @var object|null $selectedChild Active child context (parent portal)
 */

if (!in_array($role, ['student', 'parent', 'teacher'], true)) {
    return;
}

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$currentPath = parse_url($currentUri, PHP_URL_PATH) ?: $currentUri;

// Resolve Parent Active Child ID
$activeChildId = 0;
if ($role === 'parent') {
    $activeChild = $selectedChild ?? (!empty($children) ? $children[0] : null);
    if ($activeChild) {
        $activeChildId = (int)$activeChild->id;
    } else {
        $activeChildId = (int)($_SESSION['_selected_child_id'] ?? ($_SESSION['selected_child_id'] ?? 0));
        if ($activeChildId <= 0) {
            try {
                $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
                if ($sessionUserId > 0) {
                    $parentRepo = new \App\Repositories\ParentRepository();
                    $parentModel = $parentRepo->findByUserId($sessionUserId);
                    if ($parentModel) {
                        $linked = $parentRepo->getLinkedStudents($parentModel->id);
                        if (!empty($linked)) {
                            $activeChildId = (int)$linked[0]->id;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $activeChildId = 0;
            }
        }
    }
}

// Student Tab 3 (Academics) is active when visiting any learning page
$isStudentAcademicActive = (
    str_starts_with($currentPath, '/student/subjects') ||
    str_starts_with($currentPath, '/student/content') ||
    str_starts_with($currentPath, '/student/live-classes') ||
    str_starts_with($currentPath, '/student/discussions')
);

// Helpers for active status
$isRouteActive = function(string $route) use ($currentPath): bool {
    if ($route === $currentPath) return true;
    if ($route !== '/' && !in_array($route, ['/student/dashboard', '/parent/dashboard', '/teacher/dashboard'], true)) {
        return str_starts_with($currentPath, $route);
    }
    return false;
};
?>

<!-- Mobile Fixed Bottom Navigation Bar -->
<nav id="mobile-bottom-nav" 
     class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] print:hidden no-print transition-transform duration-200"
     aria-label="Mobile Bottom Navigation">
    <div class="max-w-md mx-auto px-2 pt-1.5 pb-[calc(0.5rem+env(safe-area-inset-bottom))] flex items-center justify-around">

        <?php if ($role === 'student'): ?>
            <!-- 1. Student Dashboard -->
            <?php $isActive = ($currentPath === '/student/dashboard' || $currentPath === '/student'); ?>
            <a href="/student/dashboard" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Home</span>
            </a>

            <!-- 2. Student Timetable -->
            <?php $isActive = str_starts_with($currentPath, '/student/timetable'); ?>
            <a href="/student/timetable" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Timetable</span>
            </a>

            <!-- 3. Student Academic Hub (Animates Modal from Bottom) -->
            <button type="button" 
                    id="btn-open-academic-sheet"
                    onclick="openAcademicModal()"
                    class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] cursor-pointer <?= $isStudentAcademicActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>"
                    aria-label="Open Academic Learning Hub"
                    aria-haspopup="dialog">
                <div class="relative flex items-center justify-center w-7 h-7 rounded-xl <?= $isStudentAcademicActive ? 'bg-[#FDF2F4] text-[#7B3046]' : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200/80' ?> transition">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isStudentAcademicActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <?php if ($isStudentAcademicActive): ?>
                        <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-[#7B3046] ring-2 ring-white"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 flex items-center gap-0.5 <?= $isStudentAcademicActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">
                    <span>Academics</span>
                    <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                </span>
            </button>

            <!-- 4. Student Tasks / Quizzes -->
            <?php $isActive = (str_starts_with($currentPath, '/student/assignments') || str_starts_with($currentPath, '/student/quizzes')); ?>
            <a href="/student/assignments" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Tasks</span>
            </a>

            <!-- 5. Student Grades -->
            <?php $isActive = str_starts_with($currentPath, '/student/grades'); ?>
            <a href="/student/grades" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Grades</span>
            </a>

        <?php elseif ($role === 'parent'): ?>
            <!-- 1. Parent Dashboard -->
            <?php $isActive = ($currentPath === '/parent/dashboard' || $currentPath === '/parent'); ?>
            <a href="/parent/dashboard" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Home</span>
            </a>

            <!-- 2. Parent Report Cards -->
            <?php 
            $gradesUrl = $activeChildId > 0 ? "/parent/children/{$activeChildId}/grades" : "/parent/dashboard";
            $isActive = str_contains($currentPath, '/grades');
            ?>
            <a href="<?= e($gradesUrl) ?>" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Results</span>
            </a>

            <!-- 3. Parent School Fees -->
            <?php $isActive = str_starts_with($currentPath, '/parent/fees'); ?>
            <a href="/parent/fees" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Fees</span>
            </a>

            <!-- 4. Parent Attendance -->
            <?php 
            $attendanceUrl = $activeChildId > 0 ? "/parent/children/{$activeChildId}/attendance" : "/parent/dashboard";
            $isActive = str_contains($currentPath, '/attendance');
            ?>
            <a href="<?= e($attendanceUrl) ?>" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Attendance</span>
            </a>

            <!-- 5. Parent Menu Drawer Toggle -->
            <button type="button" 
                    onclick="window.LMS ? window.LMS.toggleSidebar(true) : (document.getElementById('sidebar-navigation').classList.remove('hidden'), document.getElementById('sidebar-backdrop')?.classList.remove('hidden'))" 
                    class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] text-slate-500 hover:text-slate-900 cursor-pointer"
                    aria-label="Open Full Navigation Menu">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 group-hover:scale-105" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                </div>
                <span class="text-[10px] font-medium text-slate-500 tracking-tight mt-1">Menu</span>
            </button>

        <?php elseif ($role === 'teacher'): ?>
            <!-- 1. Teacher Dashboard -->
            <?php $isActive = ($currentPath === '/teacher/dashboard' || $currentPath === '/teacher'); ?>
            <a href="/teacher/dashboard" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Home</span>
            </a>

            <!-- 2. Teacher Attendance -->
            <?php $isActive = (str_starts_with($currentPath, '/teacher/attendance') || str_starts_with($currentPath, '/teacher/staff-attendance')); ?>
            <a href="/teacher/attendance" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Attendance</span>
            </a>

            <!-- 3. Teacher My Classes -->
            <?php $isActive = str_starts_with($currentPath, '/teacher/classes'); ?>
            <a href="/teacher/classes" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Classes</span>
            </a>

            <!-- 4. Teacher Timetable -->
            <?php $isActive = str_starts_with($currentPath, '/teacher/timetable'); ?>
            <a href="/teacher/timetable" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Timetable</span>
            </a>

            <!-- 5. Teacher Gradebook -->
            <?php $isActive = (str_starts_with($currentPath, '/teacher/gradebook') || str_starts_with($currentPath, '/teacher/results')); ?>
            <a href="/teacher/gradebook" 
               class="flex-1 flex flex-col items-center justify-center py-1 px-1 rounded-xl transition duration-150 group min-w-[48px] min-h-[48px] <?= $isActive ? 'text-[#7B3046]' : 'text-slate-500 hover:text-slate-900' ?>">
                <div class="relative flex items-center justify-center w-6 h-6">
                    <svg class="w-5 h-5 transition-transform duration-150 <?= $isActive ? 'scale-110' : 'group-hover:scale-105' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $isActive ? '2.4' : '2' ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <?php if ($isActive): ?>
                        <span class="absolute -bottom-1.5 w-1 h-1 rounded-full bg-[#7B3046]"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] tracking-tight mt-1 <?= $isActive ? 'font-bold text-[#7B3046]' : 'font-medium text-slate-500' ?>">Gradebook</span>
            </a>
        <?php endif; ?>

    </div>
</nav>

<?php if ($role === 'student'): ?>
<!-- Student Academic Hub Bottom Sheet Modal -->
<div id="academic-modal-container" 
     class="hidden fixed inset-0 z-50 print:hidden no-print"
     role="dialog"
     aria-modal="true"
     aria-labelledby="academic-hub-title">

    <!-- Backdrop with Subtle Blur -->
    <div id="academic-modal-backdrop" 
         onclick="closeAcademicModal()"
         class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity duration-300 opacity-0 cursor-pointer"
         aria-hidden="true"></div>

    <!-- Sheet Panel (Animates from bottom with spring-like cubic bezier) -->
    <div class="fixed inset-x-0 bottom-0 max-w-lg mx-auto pointer-events-none">
        <div id="academic-modal-sheet" 
             class="pointer-events-auto bg-white rounded-t-3xl shadow-2xl border-t border-slate-200/90 p-5 sm:p-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] transform transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] translate-y-full flex flex-col max-h-[85vh]">
            
            <!-- Tactile Top Drag Indicator Bar -->
            <div class="w-12 h-1.5 bg-slate-300 hover:bg-slate-400 rounded-full mx-auto -mt-1 mb-3.5 cursor-pointer transition flex-shrink-0" 
                 onclick="closeAcademicModal()"
                 title="Drag down or tap to close"></div>

            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-3 mb-4 flex-shrink-0">
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#FDF2F4] text-[#7B3046] border border-[#F5C5CF] uppercase tracking-wider mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#7B3046]"></span>
                        Academic Hub
                    </span>
                    <h3 id="academic-hub-title" class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight leading-tight">
                        Courses & Learning Materials
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Select what you want to study or collaborate on today
                    </p>
                </div>

                <!-- Dismiss Button -->
                <button type="button" 
                        onclick="closeAcademicModal()"
                        class="p-2 -mr-1.5 -mt-1 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 min-w-[44px] min-h-[44px] flex items-center justify-center cursor-pointer transition flex-shrink-0"
                        aria-label="Close Academic Hub Modal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Action Cards (High Touch Affordance, 48px+ targets, WCAG AA) -->
            <div class="space-y-2.5 overflow-y-auto pr-0.5 -mr-0.5">

                <!-- 1. Enrolled Subjects -->
                <?php $isSubjActive = str_starts_with($currentPath, '/student/subjects'); ?>
                <a href="/student/subjects" 
                   onclick="closeAcademicModal()"
                   class="flex items-center justify-between p-3.5 rounded-2xl border <?= $isSubjActive ? 'border-sky-300 bg-sky-50/60 ring-1 ring-sky-300' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/70' ?> active:scale-[0.98] transition group shadow-2xs">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-11 h-11 rounded-xl bg-sky-100/90 text-sky-700 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5"/>
                            </svg>
                        </div>
                        <div class="truncate">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-bold text-slate-900 group-hover:text-sky-700 transition">Enrolled Subjects</h4>
                                <?php if ($isSubjActive): ?>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-100 text-sky-800">Current</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-500 truncate mt-0.5">Syllabus, topics, curriculum & assigned teachers</p>
                        </div>
                    </div>
                    <div class="text-slate-400 group-hover:text-sky-600 group-hover:translate-x-0.5 transition flex-shrink-0 ml-2">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

                <!-- 2. Learning Materials -->
                <?php $isContentActive = str_starts_with($currentPath, '/student/content'); ?>
                <a href="/student/content" 
                   onclick="closeAcademicModal()"
                   class="flex items-center justify-between p-3.5 rounded-2xl border <?= $isContentActive ? 'border-[#F5C5CF] bg-[#FDF2F4]/80 ring-1 ring-[#F5C5CF]' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/70' ?> active:scale-[0.98] transition group shadow-2xs">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-11 h-11 rounded-xl bg-[#FBE8EC] text-[#7B3046] flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div class="truncate">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-bold text-slate-900 group-hover:text-[#7B3046] transition">Learning Materials</h4>
                                <?php if ($isContentActive): ?>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-[#FBE8EC] text-[#7B3046]">Current</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-500 truncate mt-0.5">E-books, lesson PDFs, Word documents & notes</p>
                        </div>
                    </div>
                    <div class="text-slate-400 group-hover:text-[#7B3046] group-hover:translate-x-0.5 transition flex-shrink-0 ml-2">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

                <!-- 3. Live Online Classes -->
                <?php $isLiveActive = str_starts_with($currentPath, '/student/live-classes'); ?>
                <a href="/student/live-classes" 
                   onclick="closeAcademicModal()"
                   class="flex items-center justify-between p-3.5 rounded-2xl border <?= $isLiveActive ? 'border-emerald-300 bg-emerald-50/60 ring-1 ring-emerald-300' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/70' ?> active:scale-[0.98] transition group shadow-2xs">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="truncate">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-bold text-slate-900 group-hover:text-emerald-700 transition">Live Classrooms</h4>
                                <?php if ($isLiveActive): ?>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">Current</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-500 truncate mt-0.5">Join scheduled video lectures with subject teachers</p>
                        </div>
                    </div>
                    <div class="text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition flex-shrink-0 ml-2">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

                <!-- 4. Class Discussions -->
                <?php $isDiscActive = str_starts_with($currentPath, '/student/discussions'); ?>
                <a href="/student/discussions" 
                   onclick="closeAcademicModal()"
                   class="flex items-center justify-between p-3.5 rounded-2xl border <?= $isDiscActive ? 'border-indigo-300 bg-indigo-50/60 ring-1 ring-indigo-300' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/70' ?> active:scale-[0.98] transition group shadow-2xs">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <div class="truncate">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-bold text-slate-900 group-hover:text-indigo-700 transition">Class Discussions</h4>
                                <?php if ($isDiscActive): ?>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-100 text-indigo-800">Current</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-500 truncate mt-0.5">Ask questions and collaborate on class topics</p>
                        </div>
                    </div>
                    <div class="text-slate-400 group-hover:text-indigo-600 group-hover:translate-x-0.5 transition flex-shrink-0 ml-2">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

            </div>

            <!-- Modal Quick Dismiss Footer -->
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                <span>Swipe down or tap outside to close</span>
                <button type="button" 
                        onclick="closeAcademicModal()" 
                        class="text-xs font-semibold text-slate-600 hover:text-slate-900 py-1 px-2.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    Done
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    function openAcademicModal() {
        const container = document.getElementById('academic-modal-container');
        const backdrop = document.getElementById('academic-modal-backdrop');
        const sheet = document.getElementById('academic-modal-sheet');
        if (!container || !backdrop || !sheet) return;

        container.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Trigger reflow for smooth animation
        void container.offsetWidth;

        backdrop.classList.remove('opacity-0');
        backdrop.classList.add('opacity-100');

        sheet.classList.remove('translate-y-full');
        sheet.classList.add('translate-y-0');
    }

    function closeAcademicModal() {
        const container = document.getElementById('academic-modal-container');
        const backdrop = document.getElementById('academic-modal-backdrop');
        const sheet = document.getElementById('academic-modal-sheet');
        if (!container || !backdrop || !sheet) return;

        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0');

        sheet.classList.remove('translate-y-0');
        sheet.classList.add('translate-y-full');

        setTimeout(function() {
            container.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 280);
    }

    // Dismiss on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const container = document.getElementById('academic-modal-container');
            if (container && !container.classList.contains('hidden')) {
                closeAcademicModal();
            }
        }
    });

    // Touch swipe down to dismiss gesture on the modal sheet
    (function() {
        const sheet = document.getElementById('academic-modal-sheet');
        if (!sheet) return;
        let startY = 0;
        let currentY = 0;
        let isDragging = false;

        sheet.addEventListener('touchstart', function(e) {
            // Only initiate swipe if sheet is scrolled to top
            if (sheet.scrollTop <= 0) {
                startY = e.touches[0].clientY;
                isDragging = true;
            }
        }, { passive: true });

        sheet.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            currentY = e.touches[0].clientY;
            const deltaY = currentY - startY;
            if (deltaY > 0) {
                // Drag down with slight resistance
                sheet.style.transform = 'translateY(' + deltaY + 'px)';
            }
        }, { passive: true });

        sheet.addEventListener('touchend', function() {
            if (!isDragging) return;
            isDragging = false;
            const deltaY = currentY - startY;
            sheet.style.transform = '';
            if (deltaY > 90) {
                closeAcademicModal();
            }
        }, { passive: true });
    })();
</script>
<?php endif; ?>

