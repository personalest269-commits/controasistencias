<?php
    $licenseConfigured = \App\Models\PgConfiguracion::valor('LICENCIA_SERVIDOR_URL', '') !== ''
        && \App\Models\PgConfiguracion::valor('LICENCIA_CLAVE', '') !== ''
        && \App\Models\PgConfiguracion::valor('LICENCIA_PRODUCTO_CODIGO', '') !== '';
    $licenseOk = \App\Models\PgConfiguracion::bool('LICENCIA_ULTIMA_VALIDACION_OK', false);
    $licenseActivated = \App\Models\PgConfiguracion::bool('LICENCIA_ACTIVADA', false);
    $licenseStatus = (string) \App\Models\PgConfiguracion::valor('LICENCIA_ULTIMO_ESTADO', '');
    $licenseMessage = session('license_warning') ?: (string) \App\Models\PgConfiguracion::valor('LICENCIA_ULTIMO_MENSAJE', '');
    $updateState = (string) \App\Models\PgConfiguracion::valor('LICENCIA_UPDATE_ULTIMO_ESTADO', '');
    $updateVersion = (string) \App\Models\PgConfiguracion::valor('LICENCIA_UPDATE_ULTIMA_VERSION', '');
    $updateMessage = (string) \App\Models\PgConfiguracion::valor('LICENCIA_UPDATE_ULTIMO_MENSAJE', '');
?>

<?php if(!$licenseConfigured || !$licenseOk || !$licenseActivated): ?>
<div class="alert alert-warning" style="border-radius:10px; margin-bottom:16px;">
    <h4 style="margin-top:0; margin-bottom:8px;"><i class="fa fa-shield-alt"></i> Licencia del sistema pendiente</h4>
    <p style="margin-bottom:8px;">
        <?php if(!$licenseConfigured): ?>
            Debes configurar la URL del servidor, la clave y el producto antes de usar el sistema.
        <?php elseif(!$licenseActivated): ?>
            La licencia todavía no está activada para esta instalación. Debes validar y activar la licencia.
        <?php else: ?>
            La licencia no está validada correctamente. <?php echo e($licenseMessage !== '' ? $licenseMessage : 'Revisa la configuración y vuelve a validar.'); ?>

        <?php endif; ?>
    </p>
    <p style="margin-bottom:0;">
        <a href="<?php echo e(route('license-client.index')); ?>" class="btn btn-sm btn-primary">
            <i class="fa fa-key"></i> Ir a licencia del sistema
        </a>
    </p>
</div>
<?php endif; ?>

<?php if(in_array($updateState, ['update_available', 'available', 'downloaded'], true)): ?>
<div class="alert alert-info" style="border-radius:10px; margin-bottom:16px;">
    <strong><i class="fa fa-download"></i> Actualización detectada</strong>
    <div style="margin-top:6px;">
        <?php echo e($updateMessage !== '' ? $updateMessage : 'Hay una actualización disponible del sistema.'); ?>

        <?php if($updateVersion !== ''): ?>
            <strong>Versión: <?php echo e($updateVersion); ?></strong>
        <?php endif; ?>
    </div>
    <div style="margin-top:8px;">
        <a href="<?php echo e(route('license-client.index')); ?>" class="btn btn-sm btn-info">Ver actualizaciones</a>
    </div>
</div>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/partials/license_dashboard_alert.blade.php ENDPATH**/ ?>