<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['product']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['product']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="p-4">
    <h3 class="font-semibold text-lg mb-2"><?php echo e($product['description'] ?? ''); ?></h3>

    <div class="space-y-1 text-sm text-gray-600">
        <?php if(isset($product['size']) && $product['size']): ?>
            <p><strong>المقاس:</strong> <?php echo e($product['size']); ?></p>
        <?php endif; ?>

        <?php if(isset($product['unit']) && $product['unit']): ?>
            <p><strong>الوحدة:</strong> <?php echo e($product['unit']); ?></p>
        <?php endif; ?>

        <?php if(isset($product['price']) && $product['price']): ?>
            <p><strong>السعر:</strong> <?php echo e(number_format((float) $product['price'], 2)); ?> جنيه</p>
        <?php endif; ?>
    </div>
</div><?php /**PATH /home/scorpion/Desktop/gen pdf file/resources/views/components/product-details.blade.php ENDPATH**/ ?>