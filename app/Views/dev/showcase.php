<?php
/**
 * Developer Component Showcase View
 * Renders interactive examples of all atomic components
 */
$this->layout('layouts/admin', [
    'title' => 'UI Component Showcase — Claret LMS',
    'headerTitle' => 'Component Showcase',
    'headerSubtitle' => 'Interactive catalog of shared UI primitives and layout structures'
]);
?>

<div class="space-y-12 max-w-5xl">
    
    <!-- Intro / Overview Card -->
    <?php 
    ob_start();
    ?>
    <div class="prose prose-slate">
        <p class="text-sm text-slate-600 leading-relaxed">
            Welcome to the Claret LMS Component Showcase. This page demonstrates all shared UI primitives implemented under <strong>Phase UI-0</strong>.
            All controls are data-driven, fully responsive, and styled to meet the accessibility contrast requirements of WCAG 2.2 AA.
        </p>
    </div>
    <?php 
    $introBody = ob_get_clean();
    $this->include('components/card', [
        'title' => 'UI Design System & Primitives',
        'subtitle' => 'Authoritative baseline tokens catalog',
        'body' => $introBody
    ]);
    ?>

    <!-- 1. Buttons Section -->
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">1. Buttons (`components/button`)</h2>
        <?php ob_start(); ?>
        <div class="space-y-6">
            <!-- Normal States -->
            <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Normal Variants</h4>
                <div class="flex flex-wrap gap-4 items-center">
                    <?php $this->include('components/button', ['variant' => 'primary', 'label' => 'Primary Button']); ?>
                    <?php $this->include('components/button', ['variant' => 'secondary', 'label' => 'Secondary Button']); ?>
                    <?php $this->include('components/button', ['variant' => 'quiet', 'label' => 'Quiet Link']); ?>
                    <?php $this->include('components/button', ['variant' => 'danger', 'label' => 'Danger Button']); ?>
                </div>
            </div>

            <!-- With Icons -->
            <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">With Icons</h4>
                <div class="flex flex-wrap gap-4 items-center">
                    <?php 
                    $checkIcon = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                    $plusIcon = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>';
                    $trashIcon = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
                    ?>
                    <?php $this->include('components/button', ['variant' => 'primary', 'label' => 'Save Attendance', 'icon' => $checkIcon]); ?>
                    <?php $this->include('components/button', ['variant' => 'secondary', 'label' => 'Add Row', 'icon' => $plusIcon]); ?>
                    <?php $this->include('components/button', ['variant' => 'danger', 'label' => 'Delete Roster', 'icon' => $trashIcon]); ?>
                </div>
            </div>

            <!-- Disabled State -->
            <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Disabled / Inactive States</h4>
                <div class="flex flex-wrap gap-4 items-center">
                    <?php $this->include('components/button', ['variant' => 'primary', 'label' => 'Primary Disabled', 'disabled' => true]); ?>
                    <?php $this->include('components/button', ['variant' => 'secondary', 'label' => 'Secondary Disabled', 'disabled' => true]); ?>
                    <?php $this->include('components/button', ['variant' => 'danger', 'label' => 'Danger Disabled', 'disabled' => true]); ?>
                </div>
            </div>
        </div>
        <?php 
        $buttonsBody = ob_get_clean();
        $this->include('components/card', ['body' => $buttonsBody]);
        ?>
    </div>

    <!-- 2. Form Controls Section -->
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">2. Form Fields (`components/input`, `select`, `textarea`)</h2>
        <?php ob_start(); ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Text Inputs -->
            <div class="space-y-4">
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inputs (Standard & Error)</h4>
                <?php $this->include('components/input', [
                    'name' => 'student_name',
                    'label' => 'Full Student Name',
                    'placeholder' => 'e.g. John Doe',
                    'required' => true,
                    'helpText' => 'Input the student name exactly as documented on their admission file.'
                ]); ?>

                <?php $this->include('components/input', [
                    'name' => 'email_address',
                    'label' => 'Email Address',
                    'placeholder' => 'invalid-email@claret',
                    'value' => 'invalid-email@claret',
                    'error' => 'Please input a valid email address containing an @ domain segment.'
                ]); ?>
            </div>

            <!-- Selects & Textareas -->
            <div class="space-y-4">
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Selects & Textareas</h4>
                <?php $this->include('components/select', [
                    'name' => 'academic_term',
                    'label' => 'Academic Term',
                    'placeholder' => 'Choose a term...',
                    'options' => [
                        'term1' => 'First Term (Advent)',
                        'term2' => 'Second Term (Lent)',
                        'term3' => 'Third Term (Trinity)'
                    ],
                    'selected' => 'term2'
                ]); ?>

                <?php $this->include('components/textarea', [
                    'name' => 'absence_reason',
                    'label' => 'Reason for Absence',
                    'placeholder' => 'Describe the medical or administrative excuse reason...',
                    'rows' => 3
                ]); ?>
            </div>
        </div>
        <?php 
        $formsBody = ob_get_clean();
        $this->include('components/card', ['body' => $formsBody]);
        ?>
    </div>

    <!-- 3. Feedback and Status Section -->
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">3. Feedback & Status (`components/alert`, `badge`)</h2>
        <?php ob_start(); ?>
        <div class="space-y-6">
            <!-- Alert banners -->
            <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Alert Banners</h4>
                <div class="space-y-3">
                    <?php $this->include('components/alert', ['type' => 'success', 'message' => 'Attendance roster saved successfully. 24 present, 2 absent.', 'dismissible' => true]); ?>
                    <?php $this->include('components/alert', ['type' => 'error', 'message' => 'Validation error: One or more fields are invalid. Please check and retry.', 'dismissible' => true]); ?>
                    <?php $this->include('components/alert', ['type' => 'warning', 'message' => 'Session conflict detected: Another teacher modified this record 5 minutes ago.', 'dismissible' => false]); ?>
                    <?php $this->include('components/alert', ['type' => 'info', 'message' => 'Term enrollment deadline ends in 3 days. Ensure all grades are entered.', 'dismissible' => false]); ?>
                </div>
            </div>

            <!-- Badges -->
            <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Status Badges</h4>
                <div class="flex flex-wrap gap-3">
                    <?php $this->include('components/badge', ['variant' => 'success', 'label' => 'Published']); ?>
                    <?php $this->include('components/badge', ['variant' => 'neutral', 'label' => 'Draft']); ?>
                    <?php $this->include('components/badge', ['variant' => 'warning', 'label' => 'Pending Grading']); ?>
                    <?php $this->include('components/badge', ['variant' => 'danger', 'label' => 'Overdue']); ?>
                    <?php $this->include('components/badge', ['variant' => 'info', 'label' => 'Excused']); ?>
                </div>
            </div>
        </div>
        <?php 
        $feedbackBody = ob_get_clean();
        $this->include('components/card', ['body' => $feedbackBody]);
        ?>
    </div>

    <!-- 4. Cards & Display Patterns Section -->
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">4. Combined Layout Patterns (Stat Cards, Data Tables)</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Stat Card 1 -->
            <?php ob_start(); ?>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Enrolled</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">1,240</p>
                </div>
                <div class="p-2.5 bg-brand-100 rounded-lg text-brand-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <?php 
            $stat1 = ob_get_clean();
            $this->include('components/card', ['body' => $stat1]);
            ?>

            <!-- Stat Card 2 -->
            <?php ob_start(); ?>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Avg. Attendance</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">94.8%</p>
                </div>
                <div class="p-2.5 bg-emerald-100 rounded-lg text-emerald-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <?php 
            $stat2 = ob_get_clean();
            $this->include('components/card', ['body' => $stat2]);
            ?>

            <!-- Stat Card 3 -->
            <?php ob_start(); ?>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Unsubmitted Tasks</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">15</p>
                </div>
                <div class="p-2.5 bg-rose-100 rounded-lg text-rose-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
            <?php 
            $stat3 = ob_get_clean();
            $this->include('components/card', ['body' => $stat3]);
            ?>
        </div>

        <!-- Data Table Example -->
        <?php ob_start(); ?>
        <div class="overflow-x-auto border border-slate-200 rounded-lg">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-700">
                <caption class="sr-only">Student academic results comparison</caption>
                <thead class="bg-slate-50 font-semibold text-slate-900">
                    <tr>
                        <th scope="col" class="px-6 py-3">Student</th>
                        <th scope="col" class="px-6 py-3">Subject</th>
                        <th scope="col" class="px-6 py-3">Assessment (40)</th>
                        <th scope="col" class="px-6 py-3">Exam (60)</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">Adewale Johnson</td>
                        <td class="px-6 py-4">Mathematics</td>
                        <td class="px-6 py-4">34</td>
                        <td class="px-6 py-4">48</td>
                        <td class="px-6 py-4">
                            <?php $this->include('components/badge', ['variant' => 'success', 'label' => 'Published']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">Chinedu Nwachukwu</td>
                        <td class="px-6 py-4">Chemistry</td>
                        <td class="px-6 py-4">28</td>
                        <td class="px-6 py-4">35</td>
                        <td class="px-6 py-4">
                            <?php $this->include('components/badge', ['variant' => 'success', 'label' => 'Published']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">Fatima Bello</td>
                        <td class="px-6 py-4">Physics</td>
                        <td class="px-6 py-4">30</td>
                        <td class="px-6 py-4">--</td>
                        <td class="px-6 py-4">
                            <?php $this->include('components/badge', ['variant' => 'warning', 'label' => 'Pending Grading']); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php 
        $tableBody = ob_get_clean();
        $this->include('components/card', [
            'title' => 'Student Performance Roster',
            'subtitle' => 'Active term performance comparison grid',
            'body' => $tableBody
        ]);
        ?>
    </div>

    <!-- 5. Pagination Section -->
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">5. Pagination (`components/pagination`)</h2>
        <?php ob_start(); ?>
        <?php $this->include('components/pagination', [
            'currentPage' => 2,
            'totalPages' => 5,
            'baseUrl' => '/dev/showcase?filter=active',
            'totalResults' => 124,
            'perPage' => 25
        ]); ?>
        <?php 
        $paginationBody = ob_get_clean();
        $this->include('components/card', ['body' => $paginationBody]);
        ?>
    </div>

    <!-- 6. Empty State Section -->
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">6. Empty State (`components/empty_state`)</h2>
        <?php ob_start(); ?>
        <?php $this->include('components/empty_state', [
            'title' => 'No learning materials uploaded',
            'message' => 'No notes, slides, or syllabus files have been uploaded for this class subject. Instructors can upload material folders here.',
            'actionUrl' => '#',
            'actionLabel' => 'Upload First Material'
        ]); ?>
        <?php 
        $emptyBody = ob_get_clean();
        $this->include('components/card', ['body' => $emptyBody]);
        ?>
    </div>

    <!-- 7. Interactive Modals Section -->
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">7. Accessible Overlay Modals (`components/modal`)</h2>
        <?php ob_start(); ?>
        <div class="space-y-4">
            <p class="text-sm text-slate-600">
                Trigger overlay dialog frames conforming to focus trap regulations. Dismiss by hitting the backdrop, click close [X], or press the Escape key.
            </p>
            <div>
                <?php $this->include('components/button', [
                    'variant' => 'primary',
                    'label' => 'Open Demonstration Modal',
                    'attributes' => 'onclick="window.LMS.showModal(\'demo-modal\')"'
                ]); ?>
            </div>
        </div>
        <?php 
        $modalTriggerBody = ob_get_clean();
        $this->include('components/card', ['body' => $modalTriggerBody]);
        ?>
        
        <!-- Showcase Modal Template -->
        <?php 
        ob_start();
        ?>
        <div class="space-y-4">
            <p class="text-sm text-slate-600 leading-normal">
                This is a component-driven popover frame. It is fully accessible, traps tab focus on activation, and returns focus to the trigger button upon dismissal.
            </p>
            <?php $this->include('components/input', [
                'name' => 'modal_test_field',
                'label' => 'Short Feedback Message',
                'placeholder' => 'Enter text...'
            ]); ?>
        </div>
        <?php 
        $modalBody = ob_get_clean();
        
        ob_start();
        ?>
        <div class="flex gap-2">
            <?php $this->include('components/button', ['variant' => 'secondary', 'label' => 'Cancel', 'attributes' => 'onclick="window.LMS.hideModal(\'demo-modal\')"']); ?>
            <?php $this->include('components/button', ['variant' => 'primary', 'label' => 'Save Action', 'attributes' => 'onclick="alert(\'Showcase saved successfully\'); window.LMS.hideModal(\'demo-modal\')"']); ?>
        </div>
        <?php
        $modalFooter = ob_get_clean();
        
        $this->include('components/modal', [
            'id' => 'demo-modal',
            'title' => 'Modal Title Header',
            'body' => $modalBody,
            'footer' => $modalFooter,
            'size' => 'md'
        ]);
        ?>
    </div>

</div>
