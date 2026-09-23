<?php
/**
 * Claret International School Report Card Dossier — Page 2 (Academic & Behavioral Record)
 * Enhanced replica supporting both Terminal Progress Report (DOCX) and Cumulative Dossier (PDF)
 */

$studentUser = $student->user ?? null;
$studentFullName = strtoupper($studentUser?->name ?? $student->name ?? 'STUDENT');
$admissionNo = $student->admissionNumber ?? 'N/A';
$dob = !empty($student->dateOfBirth) ? date('d/m/Y', strtotime($student->dateOfBirth)) : 'N/A';
$admissionDate = !empty($student->admissionDate) ? date('d/m/Y', strtotime($student->admissionDate)) : (!empty($student->enrollmentDate) ? date('d/m/Y', strtotime($student->enrollmentDate)) : 'N/A');
$state = $student->stateOfOrigin ?? 'N/A';
$lga = $student->lga ?? 'N/A';
$nationality = $student->nationality ?? 'Nigerian';
$religion = !empty($student->religion) ? $student->religion : ($religion ?? 'Christianity');
$resolvedClass = $class_full_name ?? ($class ? (method_exists($class, 'getFullName') ? $class->getFullName() : ($class->name . (!empty($class->sectionArm) ? ' (' . $class->sectionArm . ')' : ''))) : 'Class Assigned');
$homeroom = $resolvedClass;
$resolvedTeacher = $formTeacher ?? $form_teacher ?? null;
$homeroomTeacher = !empty($resolvedTeacher?->name) ? $resolvedTeacher->name : 'Class Teacher';
$resumptionDate = $next_resumption_date ?? '7th SEPTEMBER, 2026';
$isFinalTerm = !empty($is_final_term);

$demographics = $demographics ?? ['boys' => 7, 'girls' => 6, 'total' => 13];
$attendanceStats = $attendance_stats ?? ['opened' => 120, 'present' => 116, 'absent' => 4];

// Default psychomotor & affective traits matching sample PDF
$defaultPsychomotor = [
    'Handwriting' => 5,
    'Games' => 4,
    'Sports' => 5,
    'Handling Tools' => 4,
    'Drawing & Painting' => 5,
    'Musical Skills' => 4,
];

$defaultAffective = [
    'Punctuality' => 5,
    'Neatness' => 5,
    'Politeness' => 5,
    'Honesty' => 5,
    'Cooperation' => 4,
    'Leadership' => 5,
    'Helping Stability' => 4,
    'Health' => 5,
    'Attitude to School Work' => 5,
    'Attentiveness' => 5,
    'Speaking' => 5,
    'Diction' => 4,
];

// Merge with database ratings if available
$psychomotorMap = $defaultPsychomotor;
if (!empty($psychomotor_ratings)) {
    foreach ($psychomotor_ratings as $pr) {
        $psychomotorMap[$pr['name']] = (int)($pr['rating'] ?? 5);
    }
}

$affectiveMap = $defaultAffective;
if (!empty($affective_ratings)) {
    foreach ($affective_ratings as $ar) {
        $affectiveMap[$ar['name']] = (int)($ar['rating'] ?? 5);
    }
}
?>

<div class="report-page page-2 relative flex flex-col justify-between overflow-hidden bg-white text-slate-800 p-6 print:p-5">
    
    <!-- Watermark Background -->
    <div class="watermark-bg"></div>

    <div class="relative z-10 flex flex-col gap-2.5">
        
        <!-- Top Running Header -->
        <div class="flex items-center justify-between border-b-2 border-[#E28BE2] pb-1.5">
            <div class="flex items-center gap-2">
                <img src="<?= htmlspecialchars(!empty($school['logo']) ? $school['logo'] : '/assets/img/logo.png') ?>" alt="Claret Logo" class="h-8 w-auto object-contain">
                <div>
                    <span class="text-xs font-black tracking-wider text-[#7B3046] uppercase font-sans"><?= htmlspecialchars($school['name'] ?? 'Claret International School') ?></span>
                    <span class="text-[9px] font-semibold text-slate-500 block leading-none">Comprehensive Academic & Domain Performance Dossier</span>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-block bg-[#7B3046] text-white text-[9px] font-black uppercase px-2 py-0.5 rounded tracking-wide">
                    <?= strtoupper($term->name ?? 'Term') ?> &bull; <?= htmlspecialchars($session->name ?? '2025/2026') ?>
                </span>
            </div>
        </div>

        <!-- Student Biographical Information Table -->
        <div class="border border-slate-300 rounded overflow-hidden shadow-xs bg-slate-50/60">
            <table class="w-full text-[8.5px] border-collapse">
                <tbody>
                    <tr class="border-b border-slate-200">
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80 w-[12%]">Name:</td>
                        <td class="py-1 px-2.5 font-black text-slate-900 w-[38%]"><?= $studentFullName ?></td>
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80 w-[15%]">Admission No:</td>
                        <td class="py-1 px-2.5 font-extrabold text-slate-900 w-[35%]"><?= htmlspecialchars($admissionNo) ?></td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80">DOB:</td>
                        <td class="py-1 px-2.5 font-medium text-slate-800"><?= $dob ?></td>
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80">Admission Date:</td>
                        <td class="py-1 px-2.5 font-medium text-slate-800"><?= $admissionDate ?></td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80">State:</td>
                        <td class="py-1 px-2.5 font-medium text-slate-800"><?= htmlspecialchars((string)$state) ?></td>
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80">L.G.A:</td>
                        <td class="py-1 px-2.5 font-medium text-slate-800"><?= htmlspecialchars((string)$lga) ?></td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80">Nationality:</td>
                        <td class="py-1 px-2.5 font-medium text-slate-800"><?= htmlspecialchars((string)$nationality) ?></td>
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80">Religion:</td>
                        <td class="py-1 px-2.5 font-medium text-slate-800"><?= htmlspecialchars((string)$religion) ?></td>
                    </tr>
                    <tr>
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80">Homeroom:</td>
                        <td class="py-1 px-2.5 font-black text-slate-900"><?= htmlspecialchars((string)$homeroom) ?></td>
                        <td class="py-1 px-2.5 font-bold text-slate-500 uppercase bg-slate-100/80">Homeroom Teacher:</td>
                        <td class="py-1 px-2.5 font-extrabold text-slate-900"><?= htmlspecialchars((string)$homeroomTeacher) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Dual Performance Visualizations (Pure Vector SVG) -->
        <div class="grid grid-cols-2 gap-3 items-center">
            <!-- Line Chart: Comparison with Class Extremes -->
            <div class="border border-slate-200 rounded p-2 bg-white/90 shadow-2xs">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[8.5px] font-black text-slate-800 uppercase tracking-tight">Pupil vs Cohort Bounds</span>
                    <span class="text-[7px] text-slate-500">Min / Pupil / Max</span>
                </div>
                <div class="w-full flex items-center justify-center">
                    <?= $chart_svg_line ?? '' ?>
                </div>
            </div>

            <!-- Doughnut Chart: Score Distribution & Mastery -->
            <div class="border border-slate-200 rounded p-2 bg-white/90 shadow-2xs">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[8.5px] font-black text-slate-800 uppercase tracking-tight">Score Distribution & Mastery</span>
                    <span class="text-[7px] text-slate-500">Grades A &bull; B &bull; C &bull; D &bull; E</span>
                </div>
                <div class="w-full flex items-center justify-center">
                    <?= $chart_svg_doughnut ?? '' ?>
                </div>
            </div>
        </div>

        <!-- Cognitive Domain / Subject Assessment Table with Authentic Claret DOCX Palette -->
        <div class="border border-[#E28BE2] rounded overflow-hidden shadow-xs">
            <table class="w-full text-center border-collapse text-[7.5px] leading-tight">
                <thead>
                    <tr class="bg-[#FFABFF] text-[#2A0845] font-extrabold uppercase">
                        <th class="py-1 px-1 text-center border-r border-[#E28BE2] w-6">S/N</th>
                        <th class="py-1 px-1.5 text-left border-r border-[#E28BE2] min-w-[90px]">Subjects</th>
                        <th class="py-1 px-0.5 border-r border-[#E28BE2]">Class Act.<br><span class="text-[6.5px] font-normal">(20%)</span></th>
                        <th class="py-1 px-0.5 border-r border-[#E28BE2]">Unit Test<br><span class="text-[6.5px] font-normal">(20%)</span></th>
                        <th class="py-1 px-0.5 border-r border-[#E28BE2]">SPAT<br><span class="text-[6.5px] font-normal">(10%)</span></th>
                        <th class="py-1 px-0.5 border-r border-[#E28BE2]">Home Fun<br><span class="text-[6.5px] font-normal">(10%)</span></th>
                        <th class="py-1 px-0.5 border-r border-[#E28BE2]">Term Test<br><span class="text-[6.5px] font-normal">(40%)</span></th>
                        <th class="py-1 px-0.5 border-r border-[#385D8A] bg-[#4F81BD] text-white font-black">Total<br><span class="text-[6.5px] font-normal">(100%)</span></th>
                        <th class="py-1 px-0.5 border-r border-[#E28BE2]">Class<br>Total</th>
                        <th class="py-1 px-0.5 border-r border-[#D97D33] bg-[#F79646] text-white font-extrabold">Class<br>Min</th>
                        <th class="py-1 px-0.5 border-r border-[#7F9B43] bg-[#9BBB59] text-white font-extrabold">Class<br>Max</th>
                        <th class="py-1 px-0.5 border-r border-[#E28BE2]">Pupil Class<br>Average</th>
                        <?php if ($isFinalTerm): ?>
                            <th class="py-1 px-0.5 border-r border-[#E28BE2]">Cum.<br>Total</th>
                            <th class="py-1 px-0.5 border-r border-[#E28BE2]">Terms</th>
                            <th class="py-1 px-0.5 border-r border-[#385D8A] bg-[#4F81BD] text-white font-black">Cum.<br>Avg</th>
                        <?php endif; ?>
                        <th class="py-1 px-0.5 border-r border-[#E28BE2]">Rating</th>
                        <th class="py-1 px-0.5 border-r border-[#E28BE2]">Position</th>
                        <th class="py-1 px-1 text-left min-w-[65px]">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php 
                    $subCount = 0;
                    $totalPupilScore = 0;
                    $items = $subject_analytics ?? [];
                    foreach ($items as $idx => $row): 
                        $subCount++;
                        $totalPupilScore += (float)$row['pupil_score'];
                        $bgClass = ($idx % 2 === 0) ? 'bg-white' : 'bg-slate-50/70';
                        $ca = $row['ca_scores'] ?? [];
                    ?>
                        <tr class="<?= $bgClass ?> hover:bg-pink-50/40">
                            <td class="py-1 px-1 text-center font-bold text-slate-600 border-r border-slate-200"><?= $subCount ?></td>
                            <td class="py-1 px-1.5 text-left font-bold text-slate-900 border-r border-slate-200"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-mono"><?= number_format((float)($ca['activity'] ?? 0), 1) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-mono"><?= number_format((float)($ca['unit_test'] ?? 0), 1) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-mono"><?= number_format((float)($ca['spat'] ?? 0), 1) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-mono"><?= number_format((float)($ca['home_fun'] ?? 0), 1) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-mono"><?= number_format((float)($ca['term_test'] ?? 0), 1) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-black text-[#2B4B75] bg-[#EDF2F8] font-mono"><?= number_format((float)$row['pupil_score'], 1) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-mono text-slate-600"><?= number_format((float)$row['class_total'], 1) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-mono text-[#D9531E] font-bold"><?= number_format((float)$row['class_min'], 1) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-mono text-[#3C763D] font-bold"><?= number_format((float)$row['class_max'], 1) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-mono text-slate-700"><?= number_format((float)$row['class_avg'], 1) ?></td>
                            <?php if ($isFinalTerm): ?>
                                <td class="py-1 px-0.5 border-r border-slate-200 font-mono"><?= number_format((float)$row['cum_total'], 1) ?></td>
                                <td class="py-1 px-0.5 border-r border-slate-200 font-mono"><?= (int)$row['cum_terms'] ?></td>
                                <td class="py-1 px-0.5 border-r border-slate-200 font-black text-[#2B4B75] bg-[#EDF2F8] font-mono"><?= number_format((float)$row['cum_avg'], 1) ?></td>
                            <?php endif; ?>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-bold text-slate-800"><?= htmlspecialchars($row['grade']) ?></td>
                            <td class="py-1 px-0.5 border-r border-slate-200 font-bold text-slate-800"><?= htmlspecialchars($row['position']) ?></td>
                            <td class="py-1 px-1 text-left font-semibold text-slate-700 uppercase text-[7px] truncate max-w-[85px]"><?= htmlspecialchars($row['remark']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Terminal Performance Benchmark Summary Banner (Authentic to Claret DOCX Reference) -->
        <div class="flex items-center justify-between bg-[#FFABFF]/25 border border-[#E28BE2] rounded px-4 py-1.5 text-[8.5px] font-black text-[#2A0845] shadow-2xs">
            <span>Expected Score: <strong class="font-mono text-slate-900 text-[9px]"><?= $expected_score ?? ($subCount * 100) ?></strong></span>
            <span>Pupil Average: <strong class="font-mono text-slate-900 text-[9px]"><?= number_format($pupil_average ?? ($subCount > 0 ? $totalPupilScore / $subCount : 0), 1) ?>%</strong></span>
            <span>Total Score Obtained: <strong class="font-mono text-slate-900 text-[9px]"><?= number_format($total_obtained ?? $totalPupilScore, 1) ?></strong></span>
        </div>

        <!-- Four Sub-Tables Grid (Attendance, Class Demographics, Grading Scale, Behavioral Domain) -->
        <div class="grid grid-cols-12 gap-2.5 pt-0.5">
            
            <!-- Column 1: Attendance & Class Population & Grading System (5 cols) -->
            <div class="col-span-5 flex flex-col gap-2">
                
                <!-- Attendance Table -->
                <div class="border border-slate-300 rounded overflow-hidden">
                    <div class="bg-[#FFABFF] text-[#2A0845] font-black text-[8px] uppercase px-2 py-0.5 tracking-wider border-b border-[#E28BE2]">
                        Attendance Record
                    </div>
                    <table class="w-full text-center border-collapse text-[7.5px]">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                <th class="py-0.5 px-1 border-r border-slate-200">Days School Opened</th>
                                <th class="py-0.5 px-1 border-r border-slate-200">Days Present</th>
                                <th class="py-0.5 px-1">Days Absent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="font-extrabold text-slate-900 bg-white">
                                <td class="py-1 px-1 border-r border-slate-200 font-mono"><?= $attendanceStats['opened'] ?></td>
                                <td class="py-1 px-1 border-r border-slate-200 font-mono text-emerald-700"><?= $attendanceStats['present'] ?></td>
                                <td class="py-1 px-1 font-mono text-rose-600"><?= sprintf('%02d', $attendanceStats['absent']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Class Population Demographics -->
                <div class="border border-slate-300 rounded overflow-hidden">
                    <div class="bg-[#FFABFF] text-[#2A0845] font-black text-[8px] uppercase px-2 py-0.5 tracking-wider border-b border-[#E28BE2]">
                        Class Population
                    </div>
                    <table class="w-full text-center border-collapse text-[7.5px]">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                <th class="py-0.5 px-1 border-r border-slate-200">Boys</th>
                                <th class="py-0.5 px-1 border-r border-slate-200">Girls</th>
                                <th class="py-0.5 px-1">Total in Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="font-extrabold text-slate-900 bg-white">
                                <td class="py-1 px-1 border-r border-slate-200 font-mono"><?= $demographics['boys'] ?></td>
                                <td class="py-1 px-1 border-r border-slate-200 font-mono"><?= $demographics['girls'] ?></td>
                                <td class="py-1 px-1 font-mono text-sky-800"><?= $demographics['total'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Grading Scale Key -->
                <div class="border border-slate-300 rounded overflow-hidden">
                    <div class="bg-[#FFABFF] text-[#2A0845] font-black text-[8px] uppercase px-2 py-0.5 tracking-wider border-b border-[#E28BE2]">
                        Grading Scale Interpretation
                    </div>
                    <table class="w-full text-center border-collapse text-[7.5px]">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                <th class="py-0.5 px-1 border-r border-slate-200">Grade</th>
                                <th class="py-0.5 px-1 border-r border-slate-200">Score Range</th>
                                <th class="py-0.5 px-1">Interpretation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr>
                                <td class="py-0.5 px-1 font-black text-emerald-700 border-r border-slate-200">A</td>
                                <td class="py-0.5 px-1 font-mono border-r border-slate-200">85 &ndash; 100%</td>
                                <td class="py-0.5 px-1 font-semibold text-slate-700">Distinction / Excellent</td>
                            </tr>
                            <tr>
                                <td class="py-0.5 px-1 font-black text-sky-700 border-r border-slate-200">B</td>
                                <td class="py-0.5 px-1 font-mono border-r border-slate-200">75 &ndash; 84%</td>
                                <td class="py-0.5 px-1 font-semibold text-slate-700">Very Good</td>
                            </tr>
                            <tr>
                                <td class="py-0.5 px-1 font-black text-amber-700 border-r border-slate-200">C</td>
                                <td class="py-0.5 px-1 font-mono border-r border-slate-200">60 &ndash; 74%</td>
                                <td class="py-0.5 px-1 font-semibold text-slate-700">Credit / Good</td>
                            </tr>
                            <tr>
                                <td class="py-0.5 px-1 font-black text-orange-700 border-r border-slate-200">D</td>
                                <td class="py-0.5 px-1 font-mono border-r border-slate-200">40 &ndash; 59%</td>
                                <td class="py-0.5 px-1 font-semibold text-slate-700">Pass / Fair</td>
                            </tr>
                            <tr>
                                <td class="py-0.5 px-1 font-black text-rose-700 border-r border-slate-200">E</td>
                                <td class="py-0.5 px-1 font-mono border-r border-slate-200">0 &ndash; 39%</td>
                                <td class="py-0.5 px-1 font-semibold text-slate-700">Fail / Unsatisfactory</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Column 2: Psychomotor Skills (3 cols) -->
            <div class="col-span-3 border border-slate-300 rounded overflow-hidden">
                <div class="bg-[#FFABFF] text-[#2A0845] font-black text-[8px] uppercase px-2 py-0.5 tracking-wider text-center border-b border-[#E28BE2]">
                    Psychomotor Skills
                </div>
                <table class="w-full text-center border-collapse text-[7px]">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                            <th class="py-0.5 px-1 text-left border-r border-slate-200">Skill</th>
                            <th class="py-0.5 px-0.5 w-4 border-r border-slate-200">5</th>
                            <th class="py-0.5 px-0.5 w-4 border-r border-slate-200">4</th>
                            <th class="py-0.5 px-0.5 w-4 border-r border-slate-200">3</th>
                            <th class="py-0.5 px-0.5 w-4 border-r border-slate-200">2</th>
                            <th class="py-0.5 px-0.5 w-4">1</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <?php foreach ($psychomotorMap as $skill => $rating): ?>
                            <tr>
                                <td class="py-0.5 px-1 text-left font-medium text-slate-800 border-r border-slate-200"><?= htmlspecialchars($skill) ?></td>
                                <?php for ($r = 5; $r >= 1; $r--): ?>
                                    <td class="py-0.5 px-0.5 <?= $r > 1 ? 'border-r border-slate-200' : '' ?>">
                                        <?= ($rating === $r) ? '<span class="font-black text-sky-700 text-[9px] leading-none">&#10003;</span>' : '' ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Column 3: Affective Domain Traits (4 cols) -->
            <div class="col-span-4 border border-slate-300 rounded overflow-hidden">
                <div class="bg-[#FFABFF] text-[#2A0845] font-black text-[8px] uppercase px-2 py-0.5 tracking-wider text-center border-b border-[#E28BE2]">
                    Affective Traits
                </div>
                <table class="w-full text-center border-collapse text-[7px]">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                            <th class="py-0.5 px-1 text-left border-r border-slate-200">Observed Trait</th>
                            <th class="py-0.5 px-0.5 w-4 border-r border-slate-200">5</th>
                            <th class="py-0.5 px-0.5 w-4 border-r border-slate-200">4</th>
                            <th class="py-0.5 px-0.5 w-4 border-r border-slate-200">3</th>
                            <th class="py-0.5 px-0.5 w-4 border-r border-slate-200">2</th>
                            <th class="py-0.5 px-0.5 w-4">1</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <?php foreach ($affectiveMap as $trait => $rating): ?>
                            <tr>
                                <td class="py-0.5 px-1 text-left font-medium text-slate-800 border-r border-slate-200"><?= htmlspecialchars($trait) ?></td>
                                <?php for ($r = 5; $r >= 1; $r--): ?>
                                    <td class="py-0.5 px-0.5 <?= $r > 1 ? 'border-r border-slate-200' : '' ?>">
                                        <?= ($rating === $r) ? '<span class="font-black text-sky-700 text-[9px] leading-none">&#10003;</span>' : '' ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Footer Page Marker -->
        <div class="flex items-center justify-between text-[7.5px] text-slate-400 border-t border-slate-200 pt-1">
            <span><?= htmlspecialchars($school['name'] ?? 'Claret International School') ?> &bull; Continuous Assessment & Terminal Record</span>
            <span class="font-mono">Page 2 of 3</span>
        </div>

    </div>

</div>
