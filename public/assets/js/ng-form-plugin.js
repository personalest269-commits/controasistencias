/*
 * Created By Ramy Ramadan
 * Ramy_islam88@yahoo.com
 * 2017
 */
jQuery(document).ready(function () {
    $.fn.Edit = function (options) {
        var settings = $.extend({Type: "GET", Data: "", ModuleName: "", ModuleItemName: "", NgAppName: "", Headers: ""}, options);
        $('body').on('click', '.edit', function () {
            var URL = $(this).attr('data-url');
            $.ajax({
                url: URL,
                type: settings.Type,
                headers: settings.Headers,
                data: settings.Data,
                success: function (Module) {
                    // Reset Form
                    var ScopeModuleName = settings.ModuleName;
                    var ScopeModuleItemName = settings.ModuleItemName;
                    var appElement = document.querySelector('[ng-app=' + settings.NgAppName + ']');
                    var Scope = angular.element(appElement).scope();
                    $('#' + ScopeModuleName + '-form')[0].reset();
                    Scope[ScopeModuleItemName] = [];
                    Scope.$apply();
                    Scope[ScopeModuleItemName] = Module['data'][0];
                    if (typeof settings.callback == 'function') {
                        settings.callback.call();
                    }
                    Scope.$apply();
                    // En algunos templates (AdminLTE) el modal debe estar en <body> para mostrarse correctamente.
                    $('.form-modal').appendTo('body').modal('show');
                }
            });
        });
    };
    $.fn.Delete = function (options) {
        var settings = $.extend({Type: "GET", Data: "", ModuleName: "", ModuleItemName: "", NgAppName: "", Headers: ""}, options);
        $('body').on('click', '.delete', function () {
            Swal.fire({
                title: 'Are you sure you want to delete this item ?',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: `Yes`,
                denyButtonText: `No`,
            }).then((result) => {
                if (result.isConfirmed) {
                    var URL = $(this).attr('data-url');
                    $.ajax({
                        url: URL,
                        type: settings.Type,
                        headers: settings.Headers,
                        data: settings.Data,
                        success: function (Module) {
                            // Reset Form
                            var ScopeModuleName = settings.ModuleName;
                            var ScopeModuleItemName = settings.ModuleItemName;
                            var appElement = document.querySelector('[ng-app=' + settings.NgAppName + ']');
                            var Scope = angular.element(appElement).scope();
                            //$('#' + ScopeModuleName + '-form')[0].reset();
                            //Scope[ScopeModuleItemName] = [];
                            Scope.$apply();
                            Swal.fire('Deleted successfully !', '', 'success');
                            if (typeof settings.callback == 'function') {
                                settings.callback.call();
                            }
                            ListTable.ajax.reload();
                        }
                    });
                }
            });
        });
    };
    $.fn.Submit = function (options) {
        var settings = $.extend({Type: "GET", Data: "", ModuleName: "", ModuleItemName: "", NgAppName: "", Headers: ""}, options);
        var ScopeModuleName = settings.ModuleName;
        var ScopeModuleItemName = settings.ModuleItemName;
        var appElement = document.querySelector('[ng-app=' + settings.NgAppName + ']');
        var Scope = angular.element(appElement).scope();
        $('#' + ScopeModuleName + '-form').ajaxForm({
            url: $(this).attr('action'),
            type: settings.Type,
            headers: settings.Headers,
            beforeSend: function () {
                $('.ajaxLoader').show();
            },
            complete: function () {
                $('.ajaxLoader').hide();
            },
            success: function (data) {
                //$('#' + ScopeModuleName + '-form')[0].reset();
                //Scope[ScopeModuleItemName] = [];
                Scope.$apply();
                if (typeof settings.callback == 'function') {
                    settings.callback.call();
                }
                if (typeof ListTable !== 'undefined') {
                    ListTable.ajax.reload();
                }
                $('.form-modal').modal('hide');
                new PNotify({title: 'Data saved Successfully', text: data.success_message, type: 'success'});
            },
            error: function (moduleerrors) {
                // Puede venir JSON (validación) o HTML (error 500 con debug).
                var resp = moduleerrors ? moduleerrors.responseJSON : null;
                if (!resp) {
                    // intentar parsear responseText como JSON
                    try {
                        if (moduleerrors && moduleerrors.responseText) {
                            resp = JSON.parse(moduleerrors.responseText);
                        }
                    } catch (e) {
                        resp = null;
                    }
                }
                if (!resp) {
                    // Fallback: construir un objeto compatible para mostrar el error en el modal.
                    var raw = (moduleerrors && moduleerrors.responseText) ? String(moduleerrors.responseText) : '';
                    var msgFallback = 'Could not save data';
                    // Extraer SQLSTATE si está presente (típico de errores de BD)
                    var m = raw.match(/SQLSTATE\[[^\]]+\][^<\n\r]*/i);
                    if (m && m[0]) {
                        msgFallback = m[0].trim();
                    }
                    resp = { data: null, success_message: null, errors: {}, error_message: msgFallback };
                }

                Scope['moduleerrors'] = resp;
                try { Scope.$apply(); } catch (e) {}
                // No convertir labels (mensajes en modal) en alerts; solo aplica a contenedores legacy.
                $('.error_label').each(function () {
                    if (!$(this).is('label')) {
                        $(this).addClass('alert alert-danger');
                    }
                });
                // Mantener modal abierto y enfocar el primer campo con error (si aplica)
                try {
                    $('.form-modal').appendTo('body').modal('show');
                    setTimeout(function () {
                        var $firstErr = $('.form-modal .error_label:visible').first();
                        if ($firstErr.length) {
                            var $field = $firstErr.closest('.form-group').find('input,select,textarea').first();
                            if ($field.length) { $field.focus(); }
                        }
                    }, 80);
                } catch (e) {}
                // Mostrar el error real si el backend lo envía.
                var msg = 'Could not save data';
                try {
                    if (resp) {
                        msg = resp.error_message || msg;
                        // Si no hay error_message, intenta construirlo desde errors.
                        if ((!msg || msg === 'Could not save data') && resp.errors) {
                            var errs = resp.errors;
                            // Laravel MessageBag puede venir como objeto con arrays.
                            var firstKey = Object.keys(errs)[0];
                            if (firstKey && errs[firstKey] && errs[firstKey][0]) {
                                msg = errs[firstKey][0];
                            }
                        }
                    }
                } catch (e) {}
                new PNotify({title: 'Could not save data', text: msg || 'Could not save data', type: 'error'});
            }
        });
    }
    $.fn.Add = function (options) {
        var settings = $.extend({Type: "GET", Data: "", ModuleName: "", ModuleItemName: "", NgAppName: "", Headers: ""}, options);
        var ScopeModuleName = settings.ModuleName;
        var ScopeModuleItemName = settings.ModuleItemName;
        var appElement = document.querySelector('[ng-app=' + settings.NgAppName + ']');
        $('.form-modal-button').on('click', function (e) {
            var Scope = angular.element(appElement).scope();
            $('#' + ScopeModuleName + '-form')[0].reset();
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances) {
                for (instance in CKEDITOR.instances) {
                    CKEDITOR.instances[instance].updateElement();
                    CKEDITOR.instances[instance].setData('');
                }
            }
            Scope[ScopeModuleItemName] = [];
            Scope.$apply();
            if (typeof settings.callback == 'function') {
                settings.callback.call();
            }
            // AdminLTE/Bootstrap4: asegurar apertura del modal aunque data-toggle falle.
            try { $('.form-modal').appendTo('body').modal('show'); } catch (err) {}
        });
    }
    $('.datepicker').datetimepicker({format: 'DD-MM-YYYY', showClose: true});
    $('.datetimepicker').datetimepicker({format: 'DD-MM-YYYY HH-mm-ss', showClose: true});
    $('.cancel').on('click', function () {
        $('.form-modal').modal('hide');
    });
});