<?php
/**
 * Shared Dynamic Sidebar Navigation Component
 * 
 * @var string $role Current user role: admin|teacher|student|parent
 * @var string $roleLabel Nice role display label
 * @var string|null $roleBadgeColor CSS color class for role text badge
 * @var array|null $children Linked children list (optional, parent portal)
 * @var object|null $selectedChild Currently selected child context (optional, parent portal)
 */

$userContextName = $_SESSION['user_name'] ?? 'User';
$userContextEmail = $_SESSION['user_email'] ?? '';
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Build children context variables
$linkedChildren = $children ?? [];
$activeChild = $selectedChild ?? (!empty($linkedChildren) ? $linkedChildren[0] : null);
$activeChildId = $activeChild ? (int)$activeChild->id : 0;

// Helper to check active state
if (!function_exists('is_sidebar_item_active')) {
    function is_sidebar_item_active(string $route, string $currentUri): bool {
        $parsedUri = parse_url($currentUri, PHP_URL_PATH) ?: $currentUri;
        if ($parsedUri === $route) {
            return true;
        }
        if ($route === '/admin/attendance') {
            return $parsedUri === '/admin/attendance' || preg_match('#^/admin/attendance/\d+/#', $parsedUri) === 1;
        }
        if ($route !== '/' && !in_array($route, ['/admin/dashboard', '/teacher/dashboard', '/student/dashboard', '/parent/dashboard'], true)) {
            return str_starts_with($parsedUri, $route);
        }
        return false;
    }
}

// Function to fetch clean, styled SVG path icons
if (!function_exists('get_sidebar_icon')) {
    function get_sidebar_icon(string $name, bool $active = false): string {
        $colorClass = $active ? 'text-brand-500' : 'text-slate-400 group-hover:text-slate-200';
        $svgClass = "w-5 h-5 flex-shrink-0 transition duration-200 {$colorClass}";
        
        switch ($name) {
            case 'home':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>';
            case 'calendar':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
            case 'clock':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            case 'academic':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>';
            case 'users':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
            case 'book':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>';
            case 'clipboard':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>';
            case 'timetable':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
            case 'directory':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
            case 'document-text':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
            case 'users-link':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>';
            case 'upload':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>';
            case 'shield':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
            case 'database':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>';
            case 'audit':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>';
            case 'quiz':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>';
            case 'announcement':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>';
            case 'user-profile':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
            case 'check-circle':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            case 'chart':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>';
            case 'scale':
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>';
            default:
                return '<svg class="' . $svgClass . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>';
        }
    }
}

// Navigation structure
$navConfig = [
    'admin' => [
        ['label' => 'Dashboard', 'route' => '/admin/dashboard', 'icon' => 'home'],
        ['category' => 'Academic Setup'],
        ['label' => 'Sessions', 'route' => '/admin/sessions', 'icon' => 'calendar'],
        ['label' => 'Terms', 'route' => '/admin/terms', 'icon' => 'clock'],
        ['label' => 'Academic Levels', 'route' => '/admin/academic-levels', 'icon' => 'academic'],
        ['label' => 'Classes & Arms', 'route' => '/admin/classes', 'icon' => 'users'],
        ['label' => 'Subjects', 'route' => '/admin/subjects', 'icon' => 'book'],
        ['label' => 'Class Subjects', 'route' => '/admin/class-subjects', 'icon' => 'clipboard'],
        ['label' => 'Timetable', 'route' => '/admin/timetable', 'icon' => 'timetable'],
        ['category' => 'People & Enrollment'],
        ['label' => 'User Directory', 'route' => '/admin/users', 'icon' => 'directory'],
        ['label' => 'Class Enrollments', 'route' => '/admin/enrollments', 'icon' => 'document-text'],
        ['label' => 'Guardian Links', 'route' => '/admin/guardians', 'icon' => 'users-link'],
        ['label' => 'CSV Imports', 'route' => '/admin/imports/users', 'icon' => 'upload'],
        ['category' => 'Attendance & Reports'],
        ['label' => 'Attendance Registers', 'route' => '/admin/attendance', 'icon' => 'check-circle'],
        ['label' => 'Attendance Analytics', 'route' => '/admin/attendance/report', 'icon' => 'chart'],
        ['category' => 'Grading & Results'],
        ['label' => 'Grading Scales', 'route' => '/admin/grading-scales', 'icon' => 'scale'],
        ['label' => 'Assessment Config', 'route' => '/admin/assessment-categories', 'icon' => 'clipboard'],
        ['label' => 'Results Review', 'route' => '/admin/results/review', 'icon' => 'document-text'],
        ['category' => 'Communication'],
        ['label' => 'Announcements', 'route' => '/admin/announcements', 'icon' => 'announcement'],
        ['category' => 'System & Security'],
        ['label' => 'System Health', 'route' => '/admin/health', 'icon' => 'shield'],
        ['label' => 'Database Backups', 'route' => '/admin/backups', 'icon' => 'database'],
        ['label' => 'Audit Trail', 'route' => '/admin/audit-logs', 'icon' => 'audit'],
    ],
    'teacher' => [
        ['label' => 'Dashboard', 'route' => '/teacher/dashboard', 'icon' => 'home'],
        ['label' => 'Learning Materials', 'route' => '/teacher/content', 'icon' => 'book'],
        ['label' => 'Assignments', 'route' => '/teacher/assignments', 'icon' => 'clipboard'],
        ['label' => 'Question Bank', 'route' => '/teacher/question-bank', 'icon' => 'database'],
        ['label' => 'Quiz Management', 'route' => '/teacher/quizzes', 'icon' => 'quiz'],
        ['label' => 'Class Gradebooks', 'route' => '/teacher/gradebook', 'icon' => 'document-text'],
        ['label' => 'Daily Attendance', 'route' => '/teacher/attendance', 'icon' => 'calendar'],
        ['label' => 'Announcements', 'route' => '/teacher/announcements', 'icon' => 'announcement'],
        ['label' => 'My Timetable', 'route' => '/teacher/timetable', 'icon' => 'timetable'],
    ],
    'student' => [
        ['label' => 'Dashboard', 'route' => '/student/dashboard', 'icon' => 'home'],
        ['label' => 'Enrolled Subjects', 'route' => '/student/subjects', 'icon' => 'academic'],
        ['label' => 'Learning Materials', 'route' => '/student/content', 'icon' => 'book'],
        ['label' => 'Assignments', 'route' => '/student/assignments', 'icon' => 'clipboard'],
        ['label' => 'Online Quizzes', 'route' => '/student/quizzes', 'icon' => 'quiz'],
        ['label' => 'Academic Grades', 'route' => '/student/grades', 'icon' => 'document-text'],
        ['label' => 'My Attendance', 'route' => '/student/attendance', 'icon' => 'calendar'],
        ['label' => 'Announcements', 'route' => '/student/announcements', 'icon' => 'announcement'],
        ['label' => 'My Timetable', 'route' => '/student/timetable', 'icon' => 'timetable'],
    ],
    'parent' => [
        ['label' => 'Overview Dashboard', 'route' => '/parent/dashboard', 'icon' => 'home']
    ]
];

// Append child monitoring items if parent portal and a student is active
$menuItems = $navConfig[$role] ?? [];
if ($role === 'parent' && $activeChildId > 0) {
    $menuItems[] = ['category' => 'Student Monitoring'];
    $menuItems[] = ['label' => 'Child Academic Profile', 'route' => "/parent/children/{$activeChildId}", 'icon' => 'user-profile'];
    $menuItems[] = ['label' => 'Grades & Report Cards', 'route' => "/parent/children/{$activeChildId}/grades", 'icon' => 'document-text'];
    $menuItems[] = ['label' => 'Daily Attendance', 'route' => "/parent/children/{$activeChildId}/attendance", 'icon' => 'calendar'];
    $menuItems[] = ['label' => 'Coursework & Tasks', 'route' => "/parent/children/{$activeChildId}/assignments", 'icon' => 'clipboard'];
    $menuItems[] = ['label' => 'Announcements', 'route' => "/parent/children/{$activeChildId}/announcements", 'icon' => 'announcement'];
    $menuItems[] = ['label' => 'Weekly Timetable', 'route' => "/parent/children/{$activeChildId}/timetable", 'icon' => 'timetable'];
}
?>

<!-- Sidebar Container -->
<aside id="sidebar-navigation" 
       class="hidden md:flex w-64 bg-slate-900 text-slate-200 flex-col flex-shrink-0 min-h-screen border-r border-slate-800 fixed md:sticky inset-y-0 left-0 z-30 transition-transform duration-200">
    
    <!-- Header/Branding -->
    <div class="p-5 border-b border-slate-800 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg overflow-hidden bg-white flex items-center justify-center p-1 flex-shrink-0 shadow-xs">
            <img src="/assets/img/logo.png" alt="Claret Academy Logo" class="w-full h-full object-contain" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-slate-900 text-sm\'>CL</span>'">
        </div>
        <div>
            <h2 class="font-bold text-white leading-tight">Claret Academy</h2>
            <span class="text-xs font-semibold tracking-wide uppercase <?= e($roleBadgeColor ?? 'text-brand-400') ?>">
                <?= e($roleLabel) ?>
            </span>
        </div>
    </div>

    <!-- Multi-Child Context Switcher (Parent Portal Only) -->
    <?php if ($role === 'parent' && !empty($linkedChildren)): ?>
        <div class="p-4 border-b border-slate-800 bg-slate-950/60">
            <label for="sidebar-child-select" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">
                Viewing Student Context:
            </label>
            <form action="/parent/children/<?= $activeChildId ?>/select" method="POST" id="sidebar-child-form">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= e($currentUri) ?>">
                <div class="relative">
                    <select id="sidebar-child-select" name="student_id" 
                            onchange="this.form.action='/parent/children/' + this.value + '/select'; this.form.submit();"
                            class="w-full bg-slate-800 border border-slate-700 text-white text-xs rounded-lg px-3 py-2.5 appearance-none focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 cursor-pointer pr-8 font-medium">
                        <?php foreach ($linkedChildren as $child): ?>
                            <option value="<?= (int)$child->id ?>" <?= $child->id === $activeChildId ? 'selected' : '' ?>>
                                <?= e($child->name) ?> (<?= e($child->className ?: $child->admissionNumber) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </form>
            <?php if ($activeChild): ?>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-400 px-1">
                    <span>Adm: <strong class="text-slate-200"><?= e($activeChild->admissionNumber) ?></strong></span>
                    <span>Class: <strong class="text-brand-600"><?= e($activeChild->className ?: 'Assigned') ?></strong></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Navigation items list -->
    <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto" aria-label="Main Navigation">
        <?php foreach ($menuItems as $item): ?>
            <?php if (isset($item['category'])): ?>
                <div class="pt-4 pb-1 px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    <?= e($item['category']) ?>
                </div>
            <?php else: ?>
                <?php 
                $isActive = is_sidebar_item_active($item['route'], $currentUri);
                $linkClasses = $isActive 
                    ? 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold text-white bg-slate-800 border-l-4 border-brand-600 transition' 
                    : 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition';
                ?>
                <a href="<?= e($item['route']) ?>" class="<?= $linkClasses ?>">
                    <?= get_sidebar_icon($item['icon'], $isActive) ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Sidebar Footer / Logout -->
    <div class="p-4 border-t border-slate-800 bg-slate-950 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-8 h-8 rounded-full bg-brand-700 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                <?= e(substr($userContextName, 0, 1)) ?>
            </div>
            <div class="truncate">
                <p class="text-xs font-semibold text-white truncate"><?= e($userContextName) ?></p>
                <p class="text-[10px] text-slate-400 truncate"><?= e($userContextEmail) ?></p>
            </div>
        </div>
        <form action="/logout" method="POST" class="inline">
            <?= csrf_field() ?>
            <button type="submit" title="Sign Out" class="p-1.5 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition min-w-[44px] min-h-[44px] flex items-center justify-center cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </button>
        </form>
    </div>
</aside>
