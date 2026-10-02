

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginala4852ffc7847c5c74ca5491b9a641fe5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala4852ffc7847c5c74ca5491b9a641fe5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pdf-extractor','data' => ['products' => $products]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pdf-extractor'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['products' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($products)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala4852ffc7847c5c74ca5491b9a641fe5)): ?>
<?php $attributes = $__attributesOriginala4852ffc7847c5c74ca5491b9a641fe5; ?>
<?php unset($__attributesOriginala4852ffc7847c5c74ca5491b9a641fe5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala4852ffc7847c5c74ca5491b9a641fe5)): ?>
<?php $component = $__componentOriginala4852ffc7847c5c74ca5491b9a641fe5; ?>
<?php unset($__componentOriginala4852ffc7847c5c74ca5491b9a641fe5); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH G:\projects\laravel\gen_pdf\gen_pdf\resources\views/products/index.blade.php ENDPATH**/ ?>