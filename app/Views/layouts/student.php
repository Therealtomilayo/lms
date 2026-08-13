<?php
/**
 * Student Layout Wrapper
 * Delegates to the unified master app layout
 */
$this->include('layouts/app', array_merge(get_defined_vars(), [
    'role' => 'student',
    'roleLabel' => 'Student Portal',
    'roleBadgeColor' => 'text-sky-400'
]));
?>
