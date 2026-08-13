<?php
/**
 * Reusable Form Input Component
 * 
 * @var string $name Field name
 * @var string $label Label text
 * @var string|null $type Input type: text|email|password|number|date (default: text)
 * @var mixed|null $value Field value (default: empty)
 * @var string|null $placeholder Placeholder text (default: empty)
 * @var bool|null $required Whether the field is required (default: false)
 * @var string|null $error Error message for this field (default: empty)
 * @var string|null $id HTML id (default: matches name)
 * @var string|null $class Custom CSS classes for the input element (default: empty)
 * @var string|null $helpText Additional helper description under the input (default: empty)
 * @var string|null $attributes Custom raw HTML attributes (default: empty)
 */

$type = $type ?? 'text';
$value = $value ?? '';
$placeholder = $placeholder ?? '';
$required = $required ?? false;
$id = $id ?? $name;
$error = $error ?? '';
$helpText = $helpText ?? '';
$rawAttributes = $attributes ?? '';

// Generate aria-describedby IDs
$describedBy = [];
if (!empty($error)) {
    $describedBy[] = $id . '-error';
}
if (!empty($helpText)) {
    $describedBy[] = $id . '-description';
}
$describedByAttr = !empty($describedBy) ? 'aria-describedby="' . implode(' ', $describedBy) . '"' : '';
$invalidAttr = !empty($error) ? 'aria-invalid="true"' : 'aria-invalid="false"';
$requiredAttr = $required ? 'required aria-required="true"' : '';

// Class bindings based on error state
$baseInputStyles = 'block w-full min-h-[44px] px-3.5 py-2.5 rounded-lg text-base text-slate-800 placeholder-slate-400 bg-white border shadow-xs transition duration-200 focus:outline-none focus:ring-2';
$normalStyles = 'border-slate-300 focus:ring-brand-500 focus:border-brand-500';
$errorStyles = 'border-danger-700 text-danger-700 focus:ring-danger-500 focus:border-danger-500 bg-danger-100/10';

$inputClasses = $baseInputStyles . ' ' . (!empty($error) ? $errorStyles : $normalStyles) . ' ' . ($class ?? '');
?>

<div class="form-group flex flex-col gap-1.5 w-full">
    <div class="flex justify-between items-baseline">
        <label for="<?= e($id) ?>" class="text-sm font-semibold text-slate-700">
            <?= e($label) ?>
            <?php if ($required): ?>
                <span class="text-xs font-normal text-danger-700 ml-1">(Required)</span>
            <?php endif; ?>
        </label>
    </div>

    <input 
        type="<?= e($type) ?>" 
        id="<?= e($id) ?>" 
        name="<?= e($name) ?>" 
        value="<?= e((string)$value) ?>" 
        placeholder="<?= e($placeholder) ?>" 
        class="<?= e($inputClasses) ?>"
        <?= $requiredAttr ?>
        <?= $invalidAttr ?>
        <?= $describedByAttr ?>
        <?= $rawAttributes ?>
    >

    <?php if (!empty($helpText)): ?>
        <p id="<?= e($id) ?>-description" class="text-xs text-slate-500 leading-normal">
            <?= e($helpText) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p id="<?= e($id) ?>-error" role="alert" class="text-xs font-semibold text-danger-700 flex items-center gap-1.5 mt-0.5">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <?= e($error) ?>
        </p>
    <?php endif; ?>
</div>
