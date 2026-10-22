<?php
/**
 * Claret High School Official Terminal & Cumulative Report Card Dossier
 * Standard A4 Portrait (210mm x 297mm) 2-Page Format
 * Strictly follows dynamic institutional parameters and school settings
 */

$rawStudentName = $student->user?->name ?? $student->name ?? 'Student';
$gender = strtolower(trim((string)($student->gender ?? '')));
$isFemale = ($gender === 'female' || $gender === 'girl');
$salutation = !empty($salutation) ? $salutation : ($isFemale ? 'Lady' : 'Master');
$studentSalutedName = !empty($student_saluted_name) ? strtoupper($student_saluted_name) : strtoupper($salutation . ' ' . $rawStudentName);
$studentAdm = htmlspecialchars($student->admissionNumber ?? 'N/A');

$resolvedClass = $class ? (method_exists($class, 'getFullName') ? $class->getFullName() : ($class->name . (!empty($class->sectionArm) ? ' (' . $class->sectionArm . ')' : ''))) : 'Class Assigned';
$className = strtoupper($class?->name ?? 'YEAR 7');
$sessionName = strtoupper($session?->name ?? '2025/2026');
$termName = strtoupper($term?->name ?? 'SPRING');
$isCumulativeTerm = !empty($is_final_term) || stripos($termName, '3RD') !== false || stripos($termName, 'SUMMER') !== false;

// Dynamic School Details strictly from DB system_settings
$schoolName = !empty($school['name']) ? strtoupper($school['name']) : 'CLARET INTERNATIONAL SCHOOL';
$schoolAddress = $school['address'] ?? 'Plot 700, Gitto Street, Mabushi, Abuja, Nigeria';
$schoolMotto = $school['motto'] ?? 'Discipline, Integrity & Ardour';
$schoolLogo = !empty($school['logo']) ? $school['logo'] : '/assets/img/logo.png';

$subject_analytics = $subject_analytics ?? [];
$attendance_stats = $attendance_stats ?? [];
$class_stats = $class_stats ?? [];
$school = $school ?? [];
$demographics = $demographics ?? [];
$promotion_data = $promotion_data ?? [];

// Student Benchmark Metrics
$totalPupilScore = 0.0;
foreach ($subject_analytics as $sa) {
    $totalPupilScore += (float)($sa['pupil_score'] ?? 0);
}
$subCount = count($subject_analytics);
$pupilAvg = $pupil_average ?? ($subCount > 0 ? round($totalPupilScore / $subCount, 1) : 0.0);
$totalScoreFormatted = number_format($total_obtained ?? $totalPupilScore, 1);
if (substr($totalScoreFormatted, -2) === '.0') {
    $totalScoreFormatted = substr($totalScoreFormatted, 0, -2);
}
$expectedScoreFormatted = number_format($expected_score ?? ($subCount * 100));

$studentGrade = !empty($final_grade) ? strtoupper($final_grade) : 'DISTINCTION';
$studentHouse = !empty($house) ? strtoupper($house) : 'BLUE';
$studentAgeStr = !empty($student_age) ? strtoupper($student_age) : '11 YEARS OLD';

$openedDays = $attendance_stats['opened'] ?? 60;
$presentDays = $attendance_stats['present'] ?? 60;
$absentDays = $attendance_stats['absent'] ?? 0;
$resumptionDateStr = $next_resumption_date ?? '20/04/2026';

// Cohort Benchmarks
$totalStudentsInClass = $class_stats['total_students'] ?? ($demographics['total'] ?? 8);
if ($totalStudentsInClass <= 0) $totalStudentsInClass = 8;
$cohortClassAvg = number_format((float)($class_stats['class_avg'] ?? 85.5), 1);
$cohortHighestAvg = number_format((float)($class_stats['highest_avg'] ?? max(94.5, $pupilAvg)), 1);
$cohortLowestAvg = number_format((float)($class_stats['lowest_avg'] ?? 74.0), 1);

// Teacher & Principal Info
$resolvedTeacher = $form_teacher ?? $formTeacher ?? null;
$teacherName = !empty($resolvedTeacher?->name) ? $resolvedTeacher->name : 'Form Teacher';
$teacherSigUrl = $resolvedTeacher?->signatureUrl ?? '';

$principalName = $school['principal_name'] ?? 'Mr. Anthony Echefu';
$principalTitle = $school['principal_title'] ?? 'Principal';
$principalSigUrl = !empty($school['principal_signature_url']) ? $school['principal_signature_url'] : ($school['head_teacher_signature_url'] ?? '');
$schoolStampUrl = $school['school_stamp_url'] ?? '';

$teacherComment = !empty($summary?->classTeacherRemark)
    ? $summary->classTeacherRemark
    : (!empty($summary?->teacherComment) 
        ? $summary->teacherComment 
        : "{$studentSalutedName} is intelligent, confident, and displays commendable organizational skills. Approaches academic tasks with maturity and diligence. Encouraged to sustain this excellent standard.");

$principalComment = !empty($summary?->principalRemark)
    ? $summary->principalRemark
    : (!empty($summary?->principalComment) 
        ? $summary->principalComment 
        : "A competent and exceptional student. {$studentSalutedName} is advised to sustain strong study habits and remain focused in order to uphold an enviable academic record.");

if ($isCumulativeTerm) {
    $promoBadge = $promotion_data['badge_text'] ?? 'PROMOTED';
    $principalComment .= " Congratulations as you proceed to next class. {$promoBadge}!";
}

// Dynamic Grading Scale Legend from DB
$gradeLegends = [];
if (!empty($grading_scale?->boundaries)) {
    $bounds = $grading_scale->boundaries;
    usort($bounds, fn($a, $b) => $b->minScore <=> $a->minScore);
    foreach ($bounds as $b) {
        $remStr = !empty($b->remark) ? '(' . strtoupper($b->remark) . ')' : '';
        $minS = rtrim(rtrim(number_format($b->minScore, 1), '0'), '.');
        $maxS = rtrim(rtrim(number_format($b->maxScore, 1), '0'), '.');
        $gradeLegends[] = htmlspecialchars($b->letter . $remStr . ' = ' . $minS . ' – ' . $maxS);
    }
}
$gradeLegendText = !empty($gradeLegends)
    ? implode(', ', $gradeLegends)
    : 'A+(DISTINCTION) = 90 – 100, A(EXCELLENT) = 80-89, B(VERY GOOD) = 70-79, C(GOOD) = 60 – 69, D(FAIR) = 50 – 59, P(PASS) = 40 – 49, F(FAIL) = 0 – 39';

// Standard Claret High School Affective Traits & Psychomotor Skills
$standardAffective = [
    'DISCIPLINE',
    'PUNCTUALITY',
    'NEATNESS',
    'RESPONSIBILITY',
    'POLITENESS',
    'HONESTY',
    'ATTENTIVENESS',
    'LEADERSHIP',
    'SPIRIT OF COOPERATION',
    'RELATIONSHIP WITH OTHERS'
];

$standardPsychomotor = [
    'HANDWRITING',
    'CRAFT',
    'GAMES',
    'SPORTS',
    'HANDLING TOOLS',
    'DRAWING AND PAINTING',
    'MUSICAL SKILLS'
];

$affectiveMap = [];
if (!empty($affective_ratings)) {
    foreach ($affective_ratings as $ar) {
        $affectiveMap[strtoupper(trim($ar['name']))] = (int)$ar['rating'];
    }
}

$psychomotorMap = [];
if (!empty($psychomotor_ratings)) {
    foreach ($psychomotor_ratings as $pr) {
        $psychomotorMap[strtoupper(trim($pr['name']))] = (int)$pr['rating'];
    }
}
?>

<!-- ========================================================================= -->
<!-- HIGH SCHOOL DOSSIER — PAGE 1: ACADEMIC PERFORMANCE (A4 PORTRAIT)           -->
<!-- ========================================================================= -->
<div class="report-page page-1 relative flex flex-col justify-between overflow-hidden bg-white text-slate-800 p-8 print:p-6 shadow-lg print:shadow-none border border-slate-200 print:border-none">
    
    <!-- Watermark Background -->
    <div class="watermark-bg"></div>

    <div class="relative z-10 flex flex-col gap-3">
        
        <!-- Institutional Header Banner -->
        <div class="flex items-center justify-between border-b-2 border-[#7B3046] pb-2.5">
            <div class="flex items-center gap-3.5">
                <img src="<?= htmlspecialchars($schoolLogo) ?>" 
                     alt="School Logo" 
                     class="h-16 w-auto object-contain">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#7B3046] uppercase font-sans leading-none">
                        <?= htmlspecialchars($schoolName) ?>
                    </h1>
                    <p class="text-[9.5px] font-black text-slate-600 uppercase tracking-wider mt-1">
                        High School Department &bull; <?= htmlspecialchars($schoolAddress) ?>
                    </p>
                    <p class="text-[8.5px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">
                        <?= htmlspecialchars($schoolMotto) ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <span class="inline-block px-3 py-1 bg-[#7B3046] text-white font-black text-xs uppercase tracking-widest rounded shadow-xs">
                        REPORT SHEET
                    </span>
                    <div class="text-[8.5px] font-bold text-slate-500 uppercase mt-1">
                        <?= $termName ?> TERM &bull; <?= $sessionName ?>
                    </div>
                </div>
                <!-- Student Passport Photo Card -->
                <div class="w-16 h-20 bg-slate-100 border border-slate-300 rounded overflow-hidden shadow-2xs flex items-center justify-center flex-shrink-0">
                    <?php 
                        $passportPhoto = $passport_url ?? $student?->user?->avatarUrl ?? (!empty($student?->avatarUrl) ? $student->avatarUrl : null);
                    ?>
                    <?php if (!empty($passportPhoto)): ?>
                        <img src="<?= htmlspecialchars($passportPhoto) ?>" alt="<?= htmlspecialchars($studentSalutedName) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center text-slate-400 p-1 text-center">
                            <span class="text-xl">🎓</span>
                            <span class="text-[7px] font-bold uppercase tracking-wider mt-0.5 leading-none">Passport</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Student Demographic & Cohort Benchmark Summary Box (3 Columns) -->
        <div class="grid grid-cols-3 gap-2.5 border border-slate-300 rounded-lg bg-slate-50/70 p-2.5 text-[8.5px] leading-snug">
            <!-- Col 1 -->
            <div class="space-y-0.5">
                <div><span class="font-bold text-slate-600">Name:</span> <strong class="text-slate-900"><?= $studentSalutedName ?></strong></div>
                <div><span class="font-bold text-slate-600">Class:</span> <strong class="text-slate-900"><?= $className ?></strong></div>
                <div><span class="font-bold text-slate-600">Admission No:</span> <strong class="font-mono text-slate-900"><?= $studentAdm ?></strong></div>
                <div><span class="font-bold text-slate-600">Session:</span> <strong class="text-slate-900"><?= $sessionName ?></strong></div>
                <div><span class="font-bold text-slate-600">Term:</span> <strong class="text-slate-900"><?= $termName ?></strong></div>
                <div><span class="font-bold text-slate-600">House:</span> <strong class="text-slate-900"><?= $studentHouse ?></strong></div>
            </div>
            <!-- Col 2 -->
            <div class="space-y-0.5 border-l border-slate-200 pl-2.5">
                <div><span class="font-bold text-slate-600">Total Score:</span> <strong class="font-mono text-slate-900"><?= $totalScoreFormatted ?></strong></div>
                <div><span class="font-bold text-slate-600">Final Average:</span> <strong class="font-mono text-slate-900"><?= $pupilAvg ?>%</strong></div>
                <div><span class="font-bold text-slate-600">Class Average:</span> <strong class="font-mono text-slate-900"><?= $cohortClassAvg ?>%</strong></div>
                <div><span class="font-bold text-slate-600">Class Highest Av:</span> <strong class="font-mono text-slate-900"><?= $cohortHighestAvg ?>%</strong></div>
                <div><span class="font-bold text-slate-600">Class Lowest Av:</span> <strong class="font-mono text-slate-900"><?= $cohortLowestAvg ?>%</strong></div>
                <div><span class="font-bold text-slate-600">No. In Class:</span> <strong class="font-mono text-slate-900"><?= $totalStudentsInClass ?></strong></div>
            </div>
            <!-- Col 3 -->
            <div class="space-y-0.5 border-l border-slate-200 pl-2.5">
                <div><span class="font-bold text-slate-600">Final Grade:</span> <strong class="text-[#7B3046]"><?= $studentGrade ?></strong></div>
                <div><span class="font-bold text-slate-600">No. of days in a term:</span> <strong class="font-mono text-slate-900"><?= $openedDays ?></strong></div>
                <div><span class="font-bold text-slate-600">Day(s) Present:</span> <strong class="font-mono text-slate-900"><?= $presentDays ?></strong></div>
                <div><span class="font-bold text-slate-600">Day(s) Absent:</span> <strong class="font-mono text-slate-900"><?= $absentDays ?></strong></div>
                <div><span class="font-bold text-slate-600">Next Resumption Date:</span> <strong class="text-slate-900"><?= $resumptionDateStr ?></strong></div>
                <div><span class="font-bold text-slate-600">Age:</span> <strong class="text-slate-900"><?= $studentAgeStr ?></strong></div>
            </div>
        </div>

        <!-- Academic Performance Table (Standard A4 Portrait Format) -->
        <div class="border border-slate-300 rounded-lg overflow-hidden shadow-2xs">
            <table class="w-full text-center border-collapse text-[7.5px] leading-tight">
                <thead>
                    <?php if (!$isCumulativeTerm): ?>
                        <!-- Normal Terms (Term 1 & Term 2) Header -->
                        <tr class="bg-[#7B3046] text-white font-bold uppercase text-[7px]">
                            <th rowspan="2" class="py-1 px-1.5 text-left border-r border-rose-800 w-[24%]">Subject</th>
                            <th colspan="5" class="py-0.5 px-0.5 border-r border-rose-800">Continuous Assessment</th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[7%]">ETA<br><span class="text-[6px] opacity-80">(60%)</span></th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[7%]">Total<br><span class="text-[6px] opacity-80">(100%)</span></th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[8%]">Class<br>Avg</th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[5%]">Grade</th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[6%]">Pos</th>
                            <th rowspan="2" class="py-1 px-1 w-[13%]">Remark</th>
                        </tr>
                        <tr class="bg-rose-900 text-white font-semibold text-[6.5px] border-t border-rose-800">
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[6%]">EUA<br>(20)</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[5%]">TEST<br>(5)</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[6%]">SPAT<br>(5)</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[5%]">NOTE<br>(5)</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[6%]">ASGN<br>(5)</th>
                        </tr>
                    <?php else: ?>
                        <!-- Cumulative Term (Term 3) Header -->
                        <tr class="bg-[#7B3046] text-white font-bold uppercase text-[6.5px]">
                            <th rowspan="2" class="py-1 px-1.5 text-left border-r border-rose-800 w-[18%]">Subject</th>
                            <th colspan="5" class="py-0.5 px-0.5 border-r border-rose-800">Continuous Assessment</th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[5.5%]">ESA<br>(60)</th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[5.5%]">Total<br>(100)</th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[5.5%]">Class<br>Avg</th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[4%]">Grd</th>
                            <th rowspan="2" class="py-1 px-0.5 border-r border-rose-800 w-[4%]">Pos</th>
                            <th colspan="5" class="py-0.5 px-0.5 border-r border-rose-800">Annual Cumulative</th>
                            <th rowspan="2" class="py-1 px-1 w-[10%]">Remark</th>
                        </tr>
                        <tr class="bg-rose-900 text-white font-semibold text-[6px] border-t border-rose-800">
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[4.5%]">EUA</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[4%]">TST</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[4.5%]">SPT</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[4%]">NOT</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[4.5%]">ASN</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[5%]">Total</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[3.5%]">Tms</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[5%]">Avg</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[3.5%]">Grd</th>
                            <th class="py-0.5 px-0.5 border-r border-rose-800 w-[4%]">Pos</th>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php 
                    $formatScore = function($val) {
                        if ($val === null || $val === '') return '—';
                        $num = (float)$val;
                        return (floor($num) == $num) ? (string)(int)$num : number_format($num, 1);
                    };

                    foreach ($subject_analytics as $sub): 
                        $ca = $sub['ca_scores'] ?? [];
                        $eua = $ca['eua'] ?? 0;
                        $test = $ca['test'] ?? 0;
                        $spat = $ca['spat'] ?? 0;
                        $note = $ca['note'] ?? 0;
                        $assign = $ca['assignment'] ?? 0;
                        $exam = $ca['exam'] ?? max(0, (float)$sub['pupil_score'] - ($eua + $test + $spat + $note + $assign));
                    ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-0.5 px-1.5 text-left font-bold text-slate-800 border-r border-slate-200 uppercase truncate">
                                <?= htmlspecialchars($sub['name']) ?>
                            </td>
                            <!-- CA columns -->
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono text-slate-700"><?= $formatScore($eua) ?></td>
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono text-slate-700"><?= $formatScore($test) ?></td>
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono text-slate-700"><?= $formatScore($spat) ?></td>
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono text-slate-700"><?= $formatScore($note) ?></td>
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono text-slate-700"><?= $formatScore($assign) ?></td>
                            <!-- Exam & Total -->
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono font-semibold text-slate-800"><?= $formatScore($exam) ?></td>
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono font-black text-slate-900 bg-slate-50/80"><?= $formatScore($sub['pupil_score']) ?></td>
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono text-slate-700"><?= $formatScore($sub['class_avg']) ?></td>
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-black text-[#7B3046]"><?= htmlspecialchars($sub['grade']) ?></td>
                            <td class="py-0.5 px-0.5 border-r border-slate-200 font-semibold text-slate-700 uppercase"><?= htmlspecialchars($sub['position']) ?></td>
                            
                            <?php if ($isCumulativeTerm): ?>
                                <!-- Cumulative Metrics for Term 3 -->
                                <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono text-slate-700"><?= $formatScore($sub['cum_total']) ?></td>
                                <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono text-slate-700"><?= (int)($sub['cum_terms'] ?? 3) ?></td>
                                <td class="py-0.5 px-0.5 border-r border-slate-200 font-mono font-bold text-slate-900"><?= $formatScore($sub['cum_avg']) ?></td>
                                <td class="py-0.5 px-0.5 border-r border-slate-200 font-bold text-[#7B3046]"><?= htmlspecialchars($sub['cum_grade']) ?></td>
                                <td class="py-0.5 px-0.5 border-r border-slate-200 font-semibold text-slate-700"><?= htmlspecialchars($sub['cum_position']) ?></td>
                            <?php endif; ?>

                            <td class="py-0.5 px-1 font-bold text-slate-700 uppercase text-[7px] truncate">
                                <?= htmlspecialchars($sub['remark']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-rose-50/70 border-t-2 border-[#7B3046] font-bold text-[#7B3046] text-[8px]">
                        <td colspan="<?= $isCumulativeTerm ? 17 : 12 ?>" class="py-1 px-3 text-left">
                            <div class="flex items-center justify-between">
                                <span>TOTAL SCORE EXPECTED: <strong class="font-mono text-slate-900"><?= $expectedScoreFormatted ?></strong></span>
                                <span>FINAL AVERAGE: <strong class="font-mono text-slate-900"><?= $pupilAvg ?>%</strong></span>
                                <span>TOTAL OBTAINED: <strong class="font-mono text-slate-900"><?= $totalScoreFormatted ?></strong></span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Dynamic Grading Scale & Ratings Legend Banner -->
        <div class="border border-slate-200 rounded-lg p-2 bg-slate-50/80 text-[7px] leading-relaxed text-slate-700 flex flex-col gap-0.5">
            <div>
                <strong class="text-slate-900 uppercase">GRADES:</strong>
                <?= $gradeLegendText ?>
            </div>
            <div>
                <strong class="text-slate-900 uppercase">RATINGS (Degrees of Observable Traits):</strong>
                5 = Excellent &bull; 4 = Very Good &bull; 3 = Good &bull; 2 = Fair &bull; 1 = No Observable Trait
            </div>
        </div>

    </div>

    <!-- Page 1 Bottom Footer -->
    <div class="relative z-10 flex items-center justify-between border-t border-slate-300 pt-2 text-[7.5px] text-slate-500">
        <span><?= htmlspecialchars($schoolName) ?> &bull; Certified Official Academic Transcript</span>
        <span class="font-mono">Page 1 of 2</span>
    </div>

</div>


<!-- ========================================================================= -->
<!-- HIGH SCHOOL DOSSIER — PAGE 2: DOMAINS & ATTESTATIONS (A4 PORTRAIT)        -->
<!-- ========================================================================= -->
<div class="report-page page-2 relative flex flex-col justify-between overflow-hidden bg-white text-slate-800 p-8 print:p-6 shadow-lg print:shadow-none border border-slate-200 print:border-none">
    
    <!-- Watermark Background -->
    <div class="watermark-bg"></div>

    <div class="relative z-10 flex flex-col gap-3.5">
        
        <!-- Top Institutional Reference Header -->
        <div class="flex items-center justify-between border-b-2 border-[#7B3046] pb-2">
            <div class="flex items-center gap-3">
                <img src="<?= htmlspecialchars($schoolLogo) ?>" alt="Logo" class="h-10 w-auto object-contain">
                <div>
                    <h2 class="text-sm font-black tracking-tight text-[#7B3046] uppercase font-sans leading-none">
                        <?= htmlspecialchars($schoolName) ?>
                    </h2>
                    <span class="text-[8px] font-bold text-slate-500 uppercase tracking-tight">
                        Behavioral Trait Appraisal, Attestation &amp; Institutional Endorsement
                    </span>
                </div>
            </div>
            <div class="text-right text-[8.5px]">
                <strong class="text-slate-900 uppercase"><?= $studentSalutedName ?></strong>
                <div class="text-slate-500 font-semibold mt-0.5">Adm: <span class="font-mono text-slate-800 font-bold"><?= $studentAdm ?></span> &bull; <?= $className ?></div>
            </div>
        </div>

        <!-- Affective Traits & Psychomotor Domains Matrix Side-by-Side -->
        <div class="grid grid-cols-2 gap-3.5 pt-0.5">
            
            <!-- Affective Domain Table (10 Traits) -->
            <div class="border border-slate-300 rounded-lg overflow-hidden">
                <div class="bg-[#7B3046] text-white font-black text-[8px] uppercase px-2 py-1 tracking-wider text-center">
                    AFFECTIVE TRAITS
                </div>
                <table class="w-full text-center border-collapse text-[7px]">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                            <th class="py-1 px-2 text-left border-r border-slate-200 w-1/2">Observable Trait</th>
                            <th class="py-1 px-1 border-r border-slate-200 w-7">5</th>
                            <th class="py-1 px-1 border-r border-slate-200 w-7">4</th>
                            <th class="py-1 px-1 border-r border-slate-200 w-7">3</th>
                            <th class="py-1 px-1 border-r border-slate-200 w-7">2</th>
                            <th class="py-1 px-1 w-7">1</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <?php foreach ($standardAffective as $trait): 
                            $rVal = $affectiveMap[$trait] ?? 5;
                        ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-0.5 px-2 text-left font-bold text-slate-800 border-r border-slate-200 uppercase">
                                    <?= htmlspecialchars($trait) ?>
                                </td>
                                <?php for ($pt = 5; $pt >= 1; $pt--): ?>
                                    <td class="py-0.5 px-1 border-r border-slate-200 font-black text-slate-900">
                                        <?= ($rVal === $pt) ? '<span class="text-emerald-700 text-[8.5px]">&#10003;</span>' : '' ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Psychomotor Domain Table (7 Skills) -->
            <div class="border border-slate-300 rounded-lg overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="bg-[#7B3046] text-white font-black text-[8px] uppercase px-2 py-1 tracking-wider text-center">
                        PSYCHOMOTOR
                    </div>
                    <table class="w-full text-center border-collapse text-[7px]">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                <th class="py-1 px-2 text-left border-r border-slate-200 w-1/2">Skill / Activity</th>
                                <th class="py-1 px-1 border-r border-slate-200 w-7">5</th>
                                <th class="py-1 px-1 border-r border-slate-200 w-7">4</th>
                                <th class="py-1 px-1 border-r border-slate-200 w-7">3</th>
                                <th class="py-1 px-1 border-r border-slate-200 w-7">2</th>
                                <th class="py-1 px-1 w-7">1</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <?php foreach ($standardPsychomotor as $skill): 
                                $rVal = $psychomotorMap[$skill] ?? 5;
                            ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-0.5 px-2 text-left font-bold text-slate-800 border-r border-slate-200 uppercase">
                                        <?= htmlspecialchars($skill) ?>
                                    </td>
                                    <?php for ($pt = 5; $pt >= 1; $pt--): ?>
                                        <td class="py-0.5 px-1 border-r border-slate-200 font-black text-slate-900">
                                            <?= ($rVal === $pt) ? '<span class="text-emerald-700 text-[8.5px]">&#10003;</span>' : '' ?>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Cumulative Promotion Banner on Term 3 -->
                <?php if ($isCumulativeTerm): ?>
                    <div class="p-2 bg-emerald-50 border-t border-emerald-200 rounded-b flex items-center justify-between">
                        <div>
                            <span class="text-[7.5px] font-bold text-emerald-800 uppercase tracking-widest block">Official Academic Decision</span>
                            <span class="text-[10px] font-black uppercase text-emerald-950">
                                <?= htmlspecialchars($promotion_data['badge_text'] ?? 'PROMOTED') ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[7px] font-bold text-emerald-700 uppercase block">Annual Average</span>
                            <span class="text-xs font-black font-mono text-emerald-900">
                                <?= number_format((float)($promotion_data['annual_average'] ?? $pupilAvg), 1) ?>%
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>

        <!-- Form Teacher's Appraisal Card -->
        <div class="border border-slate-300 rounded-lg overflow-hidden bg-white shadow-2xs">
            <div class="bg-rose-50 text-[#7B3046] font-black text-[8.5px] uppercase px-3 py-1 tracking-wider border-b border-rose-100 flex items-center justify-between">
                <span>Form Teacher's General Remarks &amp; Appraisal</span>
                <span class="text-[7.5px] text-slate-500 font-normal italic">Homeroom Evaluation</span>
            </div>
            <div class="p-3 flex flex-col justify-between min-h-[95px]">
                <div class="handwriting-text text-sm text-blue-950 leading-relaxed italic">
                    &ldquo;<?= htmlspecialchars($teacherComment) ?>&rdquo;
                </div>
                <div class="mt-3 pt-1.5 border-t border-dashed border-slate-200 flex items-end justify-between text-[8px]">
                    <div>
                        <span class="text-slate-500 font-bold uppercase block text-[7px]">Form Teacher:</span>
                        <strong class="text-slate-900"><?= htmlspecialchars($teacherName) ?></strong>
                    </div>
                    <div class="text-center">
                        <?php if (!empty($teacherSigUrl)): ?>
                            <div class="h-9 w-28 mx-auto flex items-end justify-center">
                                <img src="<?= htmlspecialchars($teacherSigUrl) ?>" alt="Teacher Signature" class="max-h-9 max-w-28 object-contain mix-blend-multiply">
                            </div>
                        <?php else: ?>
                            <div class="border-b border-slate-400 w-28 pb-0.5 mx-auto font-serif italic text-xs text-blue-900 font-bold">
                                <?= htmlspecialchars($teacherName) ?>
                            </div>
                        <?php endif; ?>
                        <span class="text-[6.5px] text-slate-500 block">Signature</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500 font-bold uppercase block text-[7px]">Date:</span>
                        <span class="font-mono text-slate-800 font-bold"><?= date('d/m/Y') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Principal's Appraisal & Endorsement Card -->
        <div class="border-2 border-rose-200 rounded-lg overflow-hidden bg-white shadow-2xs relative">
            <div class="bg-[#7B3046] text-white font-black text-[8.5px] uppercase px-3 py-1 tracking-wider flex items-center justify-between">
                <span>Principal's Endorsement &amp; Institutional Seal</span>
                <span class="text-[7.5px] text-rose-200 font-normal italic">Attestation Administrative</span>
            </div>
            <div class="p-3 flex flex-col gap-2.5 relative">
                <div>
                    <div class="text-[7.5px] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Executive Appraisal:</div>
                    <div class="handwriting-text text-sm text-blue-950 leading-relaxed italic">
                        &ldquo;<?= htmlspecialchars($principalComment) ?>&rdquo;
                    </div>
                </div>

                <!-- Next Term Resumption Date Box -->
                <div class="rounded border border-amber-300 bg-amber-50/80 p-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xs">&#128197;</span>
                        <div>
                            <span class="text-[7.5px] font-bold text-amber-800 uppercase tracking-wider block">Next Term Resumption Date</span>
                            <span class="text-[11px] font-black text-amber-950 uppercase tracking-wide">
                                <?= htmlspecialchars($resumptionDateStr) ?>
                            </span>
                        </div>
                    </div>
                    <span class="text-[7.5px] font-bold text-amber-800 uppercase tracking-widest bg-amber-200/70 px-2 py-0.5 rounded">
                        Mandatory Return
                    </span>
                </div>

                <!-- Principal Signature & Official Stamp Overlay -->
                <div class="pt-1.5 border-t border-dashed border-slate-200 relative flex items-end justify-between">
                    <div>
                        <div class="text-[7.5px] font-bold text-slate-500 uppercase">Principal:</div>
                        <div class="text-[9.5px] font-black text-slate-900">
                            <?= htmlspecialchars($principalName) ?> 
                            <?php if (!empty($principalTitle)): ?>
                                &bull; <?= htmlspecialchars($principalTitle) ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-[7px] font-medium text-slate-500">
                            <?= htmlspecialchars($schoolName) ?>
                        </div>
                    </div>

                    <!-- Official Stamp Overlay -->
                    <?php if (!empty($schoolStampUrl)): ?>
                        <div class="absolute left-1/2 -top-5 -translate-x-1/2 pointer-events-none z-20">
                            <img src="<?= htmlspecialchars($schoolStampUrl) ?>" 
                                 alt="Official Seal" 
                                 class="h-24 w-24 object-contain rotate-[-6deg] drop-shadow-md mix-blend-multiply opacity-95">
                        </div>
                    <?php endif; ?>

                    <div class="text-right">
                        <?php if (!empty($principalSigUrl)): ?>
                            <div class="h-9 w-28 mx-auto flex items-end justify-center">
                                <img src="<?= htmlspecialchars($principalSigUrl) ?>" alt="Principal Signature" class="max-h-9 max-w-28 object-contain mix-blend-multiply">
                            </div>
                        <?php else: ?>
                            <div class="border-b border-slate-500 w-28 pb-0.5 text-center font-serif italic text-xs text-blue-900 font-bold">
                                <?= htmlspecialchars($principalName) ?>
                            </div>
                        <?php endif; ?>
                        <div class="border-t border-slate-300 text-[6.5px] font-semibold text-slate-500 pt-0.5 text-center">Principal Signature</div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Page 2 Bottom Footer -->
    <div class="relative z-10 text-center border-t border-slate-300 pt-2 text-[7.5px] text-slate-500">
        <p class="font-bold text-slate-700 uppercase tracking-wider">
            <?= htmlspecialchars($schoolName) ?> &bull; <?= htmlspecialchars($schoolAddress) ?>
        </p>
        <p class="mt-0.5 leading-relaxed text-[7px] text-slate-400">
            This document is a certified official academic dossier issued by <?= htmlspecialchars($schoolName) ?>. Any unauthorized alteration renders this result void.
        </p>
        <div class="flex items-center justify-between mt-1 text-[7px] text-slate-400">
            <span>Generated via Claret Secure Portal</span>
            <span class="font-mono">Page 2 of 2</span>
        </div>
    </div>

</div>
