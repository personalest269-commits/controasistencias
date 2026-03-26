<?php $__env->startSection('head'); ?>
<link rel="stylesheet" type="text/css" media="screen" href="<?php echo asset('assets/css/datatables/tools/css/dataTables.tableTools.css'); ?>" />
<script type="text/javascript" src="<?php echo asset('assets/js/ng-form-plugin.js'); ?>"></script>
<script src="<?php echo e(asset('assets/js/angular.js')); ?>" ></script>
<script stype="text/javascript">
    var ngProfileApp = angular.module('ngProfileApp', [], function($interpolateProvider)
    {$interpolateProvider.startSymbol('<%'); $interpolateProvider.endSymbol('%>'); });
    ngProfileApp.controller('ngProfileController', function($scope) {
    $scope.user = <?php echo $data['user']; ?>;
    });</script>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<?php
    $__u = $data['user'] ?? null;
    $__cacheKey = $__u && !empty($__u->updated_at) ? strtotime($__u->updated_at) : time();
    $__avatarUrl = asset('photos/img.jpg');
    if ($__u) {
        if (!empty($__u->id_archivo)) {
            try {
                $__avatarUrl = route('ArchivosDigitalesVer', ['id' => $__u->id_archivo]) . '?v=' . $__cacheKey;
            } catch (\Throwable $e) {
                $__avatarUrl = asset('photos/img.jpg');
            }
        } elseif (!empty($__u->image)) {
            $__avatarUrl = asset($__u->image) . '?v=' . $__cacheKey;
        }
    }
?>
<div class="">
    <div class="page-title">
        <div class="title_left">
            <h3><?php echo app('translator')->get('user_profile.module_title'); ?></h3>
        </div>
        <div class="title_right">
            <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                <div class="input-group">
                    
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">

        
        <?php if(session('success_message')): ?>
            <div class="alert alert-success">
                <?php echo e(session('success_message')); ?>

            </div>
        <?php endif; ?>

        <?php if(session('error_message')): ?>
            <div class="alert alert-danger">
                <?php echo e(session('error_message')); ?>

            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul style="margin-bottom:0;">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2><?php echo app('translator')->get('user_profile.module_form_title'); ?></h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <form  ng-app="ngProfileApp" ng-controller="ngProfileController" id="users-form" enctype="multipart/form-data" class="form-horizontal form-label-left" method="post" action='<?php echo route("userprofileupdate"); ?>' autocomplete="off">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>" />
                        <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="name"><?php echo app('translator')->get('user_profile.name'); ?><span class="required">*</span>
                            </label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <input ng-model='user.name' type="text" id="name" name='name' required="required" class="form-control col-md-7 col-xs-12" ><ul class="parsley-errors-list" ></ul>
                                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <label class="text-danger"><?php echo e($message); ?></label>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="email"><?php echo app('translator')->get('user_profile.email'); ?><span class="required">*</span>
                            </label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <input ng-model='user.email' type="text" id="email" name="email"  autocomplete="new-email" required="required" class="form-control col-md-7 col-xs-12" ><ul class="parsley-errors-list" ></ul>
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <label class="text-danger"><?php echo e($message); ?></label>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="password" class="control-label col-md-3 col-sm-3 col-xs-12"><?php echo app('translator')->get('user_profile.password'); ?></label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <input id="password" class="form-control col-md-7 col-xs-12" type="password" name="password" autocomplete="new-password" ><ul class="parsley-errors-list" ></ul>
                                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <label class="text-danger"><?php echo e($message); ?></label>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="image" class="control-label col-md-3 col-sm-3 col-xs-12"><?php echo app('translator')->get('user_profile.profile_picture'); ?></label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="mb-2" style="margin-bottom:10px;">
                                    <img id="profileAvatarPreview" src="<?php echo e($__avatarUrl); ?>" alt="Foto de perfil" style="width:120px;height:120px;object-fit:cover;border-radius:50%;border:1px solid #ddd;">
                                    <div style="font-size:12px;color:#777;margin-top:6px;">Vista previa (foto actual)</div>
                                </div>
<input type="file" name="image" />
                                <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <label class="text-danger"><?php echo e($message); ?></label>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <input ng-model='user.id' type="text" id="id" name="id" style="display: none" />
                        <div class="ln_solid"></div>
                        <div class="form-group">
                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                <button type="reset" class="btn btn-primary cancel"><?php echo app('translator')->get('user_profile.cancel'); ?></button>
                                <button type="submit" class="btn btn-success"><?php echo app('translator')->get('user_profile.submit'); ?></button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('footer'); ?>

<script>
    (function () {
        var input = document.querySelector('input[type="file"][name="image"]');
        var img = document.getElementById('profileAvatarPreview');
        if (!input || !img) return;

        input.addEventListener('change', function () {
            try {
                if (!this.files || !this.files[0]) return;
                var url = URL.createObjectURL(this.files[0]);
                img.src = url;
            } catch (err) {}
        });
    })();
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make("templates.".config("sysconfig.theme").".master", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/users/profile.blade.php ENDPATH**/ ?>