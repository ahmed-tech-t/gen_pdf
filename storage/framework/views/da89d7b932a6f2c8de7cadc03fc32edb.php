<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['product' => [], 'image' => null]));

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

foreach (array_filter((['product' => [], 'image' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
    <div class="aspect-w-16 aspect-h-9 bg-gray-100">
        <?php if($image): ?>
            <img src="<?php echo e($image); ?>" alt="<?php echo e($product['description'] ?? ''); ?>"
                class="w-full h-48 object-contain">
        <?php else: ?>
            <div class="product-image-placeholder">
                <span>Product Image</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($component)) { $__componentOriginal1d33f0fed124ae2126044267eb7691bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1d33f0fed124ae2126044267eb7691bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-details','data' => ['product' => $product]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-details'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['product' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($product)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1d33f0fed124ae2126044267eb7691bc)): ?>
<?php $attributes = $__attributesOriginal1d33f0fed124ae2126044267eb7691bc; ?>
<?php unset($__attributesOriginal1d33f0fed124ae2126044267eb7691bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1d33f0fed124ae2126044267eb7691bc)): ?>
<?php $component = $__componentOriginal1d33f0fed124ae2126044267eb7691bc; ?>
<?php unset($__componentOriginal1d33f0fed124ae2126044267eb7691bc); ?>
<?php endif; ?>
</div><?php /**PATH /home/scorpion/Desktop/gen pdf file/resources/views/components/product-card.blade.php ENDPATH**/ ?>