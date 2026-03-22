@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('assets/css/datatables/tools/css/dataTables.tableTools.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/select/select2.min.css') }}" />

    <style>
        /* Modal: evita que se corten botones y permite scroll */
        .form-modal .modal-dialog { max-width: 900px; }
        .form-modal .modal-body { max-height: calc(100vh - 220px); overflow-y: auto; }
        .error_label { display: block; margin-top: 4px; }
        .select2-container { width: 100% !important; }
    </style>
@endsection

@section('content')
<div class="">
    <div class="page-title">
        <div class="title_left">
            <h3>@lang('users.module_title')</h3>
        </div>
        <div class="title_right">
            <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                <div class="input-group"></div>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <div class="row">
                        <div class="col-md-8 col-sm-8 col-xs-7"><h2>@lang('users.module_subtitle')</h2></div>
                        <div class="col-md-4 col-sm-4 col-xs-5">
                            <button type="button" class="btn btn-primary form-modal-button pull-right">@lang('users.module_add_new')</button>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <table class="table table-striped jambo_table dataTable" id="users-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="check-all" class="flat"></th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Usuario</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Form modal -->
    <div class="modal fade form-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="userModalTitle">Usuario
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </h4>
                </div>

                <div class="modal-body">
                    <form id="users-form" class="form-horizontal form-label-left" method="post" action="{!! route('userscreateorupdate') !!}" autocomplete="off" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                        <input type="hidden" id="id" name="id" value="" />

                        <div id="user_form_alert" class="alert alert-danger" style="display:none;"></div>

                        <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="link_persona">Persona</label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <label style="font-weight: normal;">
                                    <input type="checkbox" id="link_persona" name="link_persona" value="1"> Vincular persona (opcional)
                                </label>

                                <div id="persona_container" style="display:none; margin-top:8px;">
                                    <select id="id_persona" name="id_persona" class="form-control" style="width:100%"></select>
                                    <label class="text-danger error_label" data-error-for="id_persona"></label>
                                    <small class="text-muted">Busca por cédula o nombres.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="name">Name <span class="required">*</span></label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <input type="text" id="name" name="name" class="form-control col-md-7 col-xs-12" />
                                <label class="text-danger error_label" data-error-for="name"></label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="usuario">Usuario (cédula) <span class="required">*</span></label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <input type="text" id="usuario" name="usuario" maxlength="10" class="form-control col-md-7 col-xs-12" />
                                <label class="text-danger error_label" data-error-for="usuario"></label>
                                <small class="text-muted">Debe ser cédula ecuatoriana válida (10 dígitos).</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="email">E-mail</label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <input type="text" id="email" name="email" autocomplete="new-email" class="form-control col-md-7 col-xs-12" />
                                <label class="text-danger error_label" data-error-for="email"></label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password" class="control-label col-md-3 col-sm-3 col-xs-12">Password</label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <input id="password" class="form-control col-md-7 col-xs-12" type="password" name="password" autocomplete="new-password" />
                                <label class="text-danger error_label" data-error-for="password"></label>
                                <small class="text-muted" id="password_help">(En nuevo usuario es obligatorio. En edición es opcional.)</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="send_welcome_email">Send Email</label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <label style="font-weight: normal;">
                                    <input type="checkbox" id="send_welcome_email" name="send_welcome_email" value="1" checked> Send credentials email to the user
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="roles" class="control-label col-md-3 col-sm-3 col-xs-12">Role <span class="required">*</span></label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <select name="roles" id="roles" class="form-control">
                                    <option value="">-- Seleccione --</option>
                                    @foreach(($data['roles'] ?? []) as $role)
                                        <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                    @endforeach
                                </select>
                                <label class="text-danger error_label" data-error-for="roles"></label>
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group">
                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                <button type="button" class="btn btn-primary cancel" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success" id="btnUserSubmit">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
    <script type="text/javascript" src="{{ asset('assets/js/select/select2.full.js') }}"></script>

    <script type="text/javascript">
        var ListTable;
        var currentUserId = '';

        // Bootstrap modal + Select2 (bandbox): permitir escribir en el buscador
        // (Bootstrap 3 tiene enforceFocus que bloquea el input de Select2)
        if ($.fn.modal && $.fn.modal.Constructor) {
            try {
                $.fn.modal.Constructor.prototype.enforceFocus = function () {};
            } catch (e) {}
        }

        function SelectedCheckboxes() {
            return $('input:checkbox:checked.user_record').map(function () { return this.value; }).get();
        }

        function ajaxAction(url, action) {
            $.ajax({
                url: url,
                type: action,
                data: {'_token': "{{ csrf_token() }}", 'selected_rows': SelectedCheckboxes()},
                success: function () {}
            });
        }

        function clearErrors() {
            $('#user_form_alert').hide().text('');
            $('.form-modal .error_label').text('');
        }

        function showErrors(errors, errorMessage) {
            clearErrors();
            if (errorMessage) {
                $('#user_form_alert').text(errorMessage).show();
            }
            if (!errors) return;

            // errors puede venir como MessageBag serializado
            // intentamos normalizar
            try {
                if (errors.errors) errors = errors.errors;
            } catch (e) {}

            Object.keys(errors).forEach(function (k) {
                var msg = errors[k];
                if (Array.isArray(msg)) msg = msg[0];
                $('.form-modal .error_label[data-error-for="' + k + '"]').text(msg);
            });
        }

        
        function hasICheck($el) {
            return !!($.fn.iCheck && ($el.data('iCheck') || $el.parent().hasClass('icheckbox_flat-green') || $el.closest('.icheckbox_flat-green').length));
        }

        function initModalICheck() {
            if (!$.fn.iCheck) return;
            try { $('#link_persona').iCheck('destroy'); } catch (e) {}
            try { $('#link_persona').iCheck({checkboxClass: 'icheckbox_flat-green'}); } catch (e) {}
        }

        function isChecked($el) {
            // Soporta iCheck
            if (hasICheck($el)) {
                return $el.prop('checked');
            }
            return $el.is(':checked');
        }

        function setChecked($el, checked) {
            if (hasICheck($el)) {
                try {
                    $el.iCheck(checked ? 'check' : 'uncheck');
                    return;
                } catch (e) {}
            }
            $el.prop('checked', !!checked);
        }

        function togglePersonaUI(show) {
            if (show) {
                $('#persona_container').show();
                initPersonaSelect2();
            } else {
                $('#persona_container').hide();
                try {
                    $('#id_persona').val(null).trigger('change');
                } catch (e) {}
                $('#email').prop('readonly', false);
                $('#usuario').prop('readonly', false);
            }
        }

        function initPersonaSelect2(preselect) {
            var $sel = $('#id_persona');

            // Re-init limpio
            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.select2('destroy');
            }

            $sel.select2({
                dropdownParent: $('.form-modal')
                , width: '100%'
                , placeholder: 'Buscar persona...'
                , allowClear: true
                , minimumInputLength: 1
                , ajax: {
                    url: "{{ route('users_personas_search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            exclude_user_id: currentUserId
                        };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                }
            });

            $sel.off('select2:select').on('select2:select', function (e) {
                var d = e.params.data || {};
                // Usuario (cédula) desde persona
                if (d.identificacion) {
                    $('#usuario').val(String(d.identificacion).replace(/\D+/g, '').slice(0,10)).prop('readonly', true);
                }
                if (d.email && String(d.email).trim() !== '') {
                    $('#email').val(d.email).prop('readonly', true);
                } else {
                    $('#email').prop('readonly', false);
                }
            });

            $sel.off('select2:clear').on('select2:clear', function () {
                $('#email').prop('readonly', false);
                $('#usuario').prop('readonly', false);
            });

            if (preselect && preselect.id) {
                var option = new Option(preselect.text || preselect.id, preselect.id, true, true);
                $sel.append(option).trigger('change');
                if (preselect.identificacion) {
                    $('#usuario').val(String(preselect.identificacion).replace(/\D+/g, '').slice(0,10)).prop('readonly', true);
                }
                if (preselect.email && String(preselect.email).trim() !== '') {
                    $('#email').val(preselect.email).prop('readonly', true);
                }
            }
        }

        function openCreateModal() {
            currentUserId = '';
            clearErrors();

            initModalICheck();

            $('#userModalTitle').text('Nuevo usuario');
            $('#users-form')[0].reset();
            $('#id').val('');
            $('#email').prop('readonly', false);
            $('#usuario').prop('readonly', false);

            // iCheck reset
            setChecked($('#link_persona'), false);
            togglePersonaUI(false);

            // roles reset
            $('#roles').val('');

            // select2 limpiar
            try { $('#id_persona').val(null).trigger('change'); } catch (e) {}

            // Asegurar modal sobre todo
            $('.form-modal').appendTo('body').modal('show');
        }

        function openEditModal(url) {
            clearErrors();
            $('#users-form')[0].reset();
            $('#password').val('');
            $('#email').prop('readonly', false);
            $('#usuario').prop('readonly', false);

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function (resp) {
                    var u = null;
                    if (resp && resp.data) {
                        u = Array.isArray(resp.data) ? resp.data[0] : resp.data;
                    }
                    if (!u) {
                        showErrors({}, 'No se pudo cargar el usuario para editar.');
                        $('.form-modal').appendTo('body').modal('show');
                        return;
                    }

                    currentUserId = String(u.id || '');

                    initModalICheck();

                    $('#userModalTitle').text('Editar usuario');
                    $('#id').val(u.id || '');
                    $('#name').val(u.name || '');
                    $('#usuario').val(u.usuario || '').prop('readonly', false);
                    $('#email').val(u.email || '').prop('readonly', false);

                    // role
                    var roleId = '';
                    if (u.roles && u.roles.length) {
                        roleId = u.roles[0].id;
                    }
                    $('#roles').val(roleId ? String(roleId) : '');

                    // persona
                    if (u.id_persona) {
                        setChecked($('#link_persona'), true);
                        togglePersonaUI(true);

                        var p = u.persona || null;
                        var pText = '';
                        var pEmail = '';
                        if (p) {
                            var nombre = $.trim([p.nombres, p.apellido1, p.apellido2].filter(Boolean).join(' '));
                            pText = (p.identificacion ? (p.identificacion + ' - ') : '') + nombre;
                            pEmail = p.email || '';
                        } else {
                            pText = u.id_persona;
                        }

                        initPersonaSelect2({id: u.id_persona, text: pText, email: pEmail, identificacion: (p ? p.identificacion : '')});
                    } else {
                        setChecked($('#link_persona'), false);
                        togglePersonaUI(false);
                    }

                    $('.form-modal').appendTo('body').modal('show');
                },
                error: function () {
                    showErrors({}, 'No se pudo cargar el usuario para editar.');
                    $('.form-modal').appendTo('body').modal('show');
                }
            });
        }

        function clientValidate() {
            clearErrors();

            var errors = {};
            var isEdit = $.trim($('#id').val()) !== '';

            var name = $.trim($('#name').val());
            var usuario = $.trim($('#usuario').val());
            var roles = $.trim($('#roles').val());
            var password = $.trim($('#password').val());

            if (name === '') errors.name = ['Debe ingresar el nombre.'];
            if (usuario === '') errors.usuario = ['Debe ingresar el usuario (cédula).'];
            if (roles === '') errors.roles = ['Debe seleccionar un rol.'];
            if (!isEdit && password === '') errors.password = ['Debe ingresar la contraseña.'];

            var linkPersona = isChecked($('#link_persona'));
            if (linkPersona) {
                var idPersona = $.trim($('#id_persona').val() || '');
                if (idPersona === '') errors.id_persona = ['Debe seleccionar una persona.'];
            }

            if (Object.keys(errors).length) {
                showErrors(errors, 'Por favor corrige los campos marcados.');
                return false;
            }

            return true;
        }

        function submitUserForm() {
            if (!clientValidate()) {
                $('.form-modal').appendTo('body').modal('show');
                return;
            }

            var $btn = $('#btnUserSubmit');
            $btn.prop('disabled', true);

            var form = document.getElementById('users-form');
            var fd = new FormData(form);

            // Si no vincula persona, aseguramos que NO se envíe id_persona
            if (!isChecked($('#link_persona'))) {
                fd.set('id_persona', '');
                fd.delete('id_persona');
            }

            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function () {
                    $('.form-modal').modal('hide');
                    if (ListTable) ListTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    var r = xhr.responseJSON || {};
                    var errors = r.errors || {};
                    var msg = r.error_message || 'No se pudo guardar. Revise los campos.';
                    showErrors(errors, msg);
                    $('.form-modal').appendTo('body').modal('show');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        }

        $(document).ready(function () {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
            });

            ListTable = $('#users-table').DataTable({
                dom: '<"row"<"col-sm-7 col-md-8"<"hidden-xs hidden-sm"l>B><"col-sm-5 col-md-4"f>><"row"<"col-sm-12 table-responsive"rt>><"row"<"col-sm-5"i><"col-sm-7"p>>',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print',
                    {
                        text: 'Delete',
                        action: function () {
                            var TrashItem = confirm('Are Your sure you want to Delete this User/s');
                            if (TrashItem) {
                                ajaxAction("{!! route('usersdeletemultiple') !!}", 'DELETE');
                                ListTable.ajax.reload();
                            }
                        }
                    }
                ],
                processing: true,
                serverSide: true,
                ajax: {url: '{!! route('userslist') !!}', data: {'_token': '{{ csrf_token() }}'}},
                columns: [
                    {data: 'Select', name: 'Select', searchable:false, sortable:false},
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'usuario', name: 'usuario'},
                    {data: 'role', name: 'role'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'updated_at', name: 'updated_at'},
                    {data: 'actions', name: 'actions', searchable:false}
                ],
                order: [[1, 'asc']],
                drawCallback: function() {
                    if ($.fn.iCheck) {
                        $('input').iCheck({checkboxClass: 'icheckbox_flat-green'});
                    }
                }
            });

            // Check all
            $('body').on('ifToggled', '#check-all', function () {
                if ($(this).is(':checked')) {
                    $('input.user_record').iCheck('check');
                } else {
                    $('input.user_record').iCheck('uncheck');
                }
            });

            // Abrir modal (nuevo)
            $('body').on('click', '.form-modal-button', function (e) {
                e.preventDefault();
                openCreateModal();
            });

            // Edit
            $('body').on('click', 'a.edit', function (e) {
                e.preventDefault();
                var url = $(this).data('url');
                if (!url) return;
                openEditModal(url);
            });

            // Delete
            $('body').on('click', 'a.delete', function (e) {
                e.preventDefault();
                var url = $(this).data('url');
                if (!url) return;
                if (!confirm('Delete this user?')) return;
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function () { ListTable.ajax.reload(null, false); }
                });
            });

            // Toggle persona (soporta iCheck + change nativo)
            $('body').on('ifChecked ifUnchecked ifChanged change', '#link_persona', function () {
                togglePersonaUI($(this).is(':checked'));
            });

            // Submit
            $('#users-form').on('submit', function (e) {
                e.preventDefault();
                submitUserForm();
            });

            // Al cerrar modal, limpiar
            $('.form-modal').on('hidden.bs.modal', function () {
                clearErrors();
            });
        });
    </script>

    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}" />

    <script src="{{ asset('admin_lte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>

    <script src="{{ asset('admin_lte/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/datatables-buttons/js/buttons.flash.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
@endsection
