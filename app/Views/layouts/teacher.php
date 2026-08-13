<?php
/**
 * Teacher Layout Wrapper
 * Delegates to the unified master app layout
 */
$this->include('layouts/app', array_merge(get_defined_vars(), [
    'role' => 'teacher',
    'roleLabel' => 'Teacher Portal',
    'roleBadgeColor' => 'text-emerald-400'
]));
?>
