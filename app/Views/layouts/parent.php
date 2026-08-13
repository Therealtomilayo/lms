<?php
/**
 * Parent/Guardian Layout Wrapper
 * Delegates to the unified master app layout
 */
$this->include('layouts/app', array_merge(get_defined_vars(), [
    'role' => 'parent',
    'roleLabel' => 'Guardian Portal',
    'roleBadgeColor' => 'text-pink-400'
]));
?>
