<?php
/**
 * Admin Layout Wrapper
 * Delegates to the unified master app layout
 */
$this->include('layouts/app', array_merge(get_defined_vars(), [
    'role' => 'admin',
    'roleLabel' => 'Admin Portal',
    'roleBadgeColor' => 'text-brand-400'
]));
?>
