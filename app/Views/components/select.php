<?php
/**
 * Reusable Select Dropdown Component
 * 
 * @var string $name Field name
 * @var string $label Label text
 * @var array $options Options list (associative value => label or list of arrays with value/label keys)
 * @var mixed|null $selected Selected value (default: empty)
 * @var string|null $placeholder Default empty option label (e.g. Select an option) (default: empty)
 * @var bool|null $required Whether the field is required (default: false)
 * @var string|null $error Error message for this field (default: empty)
 * @var string|null $id HTML id (default: matches name)
 * @var string|null $class Custom CSS classes for select tag (default: empty)
 * @var string|null $helpText Additional helper description under the input (default: empty)
 * @var string|null $attributes Custom raw HTML attributes (default: empty)
 */

$selected = $selected ?? null;
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

$baseSelectStyles = 'block w-full min-h-[44px] px-3.5 py-2.5 rounded-lg text-base text-slate-800 bg-white border shadow-xs transition duration-200 focus:outline-none focus:ring-2 cursor-pointer pr-10 appearance-none bg-[url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22none%22%3E%3Cpath%20d%3D%22M7%209l3%203%203-3%22%20stroke%3D%22%2364748B%22%20stroke-width%3D%221.5%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E")] bg-[position:right_0.5rem_center] bg-[size:1.5em_1.5em] bg-no-repeat';
$normalStyles = 'border-slate-300 focus:ring-brand-500 focus:border-brand-500';
$errorStyles = 'border-danger-700 text-danger-700 focus:ring-danger-500 focus:border-danger-500 bg-danger-100/10';

$selectClasses = $baseSelectStyles . ' ' . (!empty($error) ? $errorStyles : $normalStyles) . ' ' . ($class ?? '');
?>

<div class="form-group flex flex-col gap-1.5 w-full">
    <label for="<?= e($id) ?>" class="text-sm font-semibold text-slate-700">
        <?= e($label) ?>
        <?php if ($required): ?>
            <span class="text-xs font-normal text-danger-700 ml-1">(Required)</span>
        <?php endif; ?>
    </label>

    <div class="relative">
        <select 
            id="<?= e($id) ?>" 
            name="<?= e($name) ?>" 
            class="<?= e($selectClasses) ?>"
            <?= $requiredAttr ?>
            <?= $invalidAttr ?>
            <?= $describedByAttr ?>
            <?= $rawAttributes ?>
        >
            <?php if (!empty($placeholder)): ?>
                <option value="" <?= $selected === null || $selected === '' ? 'selected' : '' ?>><?= e($placeholder) ?></option>
            <?php endif; ?>

            <?php foreach ($options as $key => $val): ?>
                <?php
                // Handle both associative values and structured array options
                $optVal = is_array($val) && isset($val['value']) ? $val['value'] : $key;
                $optLabel = is_array($val) && isset($val['label']) ? $val['label'] : $val;
                
                // Strict or loose selection checks based on type
                $isSelected = false;
                if (is_array($selected)) {
                    $isSelected = in_array($optVal, $selected);
                } else {
                    $isSelected = (string)$optVal === (string)$selected;
                }
                ?>
                <option value="<?= e((string)$optVal) ?>" <?= $isSelected ? 'selected' : '' ?>>
                    <?= e($optLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

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
