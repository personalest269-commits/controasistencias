@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="page-title">
        <div class="title_left">
            <h3>Email Settings</h3>
        </div>
    </div>
    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Email Settings</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <p class="text-muted">This SMTP will be used for system-level email sending. Additionally, if a company user does not set their SMTP, then this SMTP will be used for sending emails.</p>

                    <form id="email-settings-form" class="form-horizontal form-label-left" method="post" action="{{ route('email-settings.update') }}" autocomplete="off">
                        <input type="hidden" name="_token" value="{{ csrf_token()}}" />

                        <div class="row">
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label>Mail Driver</label>
                                    <input class="form-control" name="mail_driver" value="{{ $data['settings']->mail_driver }}" placeholder="smtp" />
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label>Mail Host</label>
                                    <input class="form-control" name="mail_host" value="{{ $data['settings']->mail_host }}" placeholder="mail.example.com" />
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label>Mail Port</label>
                                    <input class="form-control" name="mail_port" value="{{ $data['settings']->mail_port }}" placeholder="587" />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label>Mail Username</label>
                                    <input class="form-control" name="mail_username" value="{{ $data['settings']->mail_username }}" autocomplete="new-email" />
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label>Mail Password</label>
                                    <input class="form-control" type="password" name="mail_password" value="{{ $data['settings']->mail_password }}" autocomplete="new-password" />
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label>Mail Encryption</label>
                                    <input class="form-control" name="mail_encryption" value="{{ $data['settings']->mail_encryption }}" placeholder="tls" />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label>Mail From Address</label>
                                    <input class="form-control" name="mail_from_address" value="{{ $data['settings']->mail_from_address }}" placeholder="no-reply@example.com" />
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label>Mail From Name</label>
                                    <input class="form-control" name="mail_from_name" value="{{ $data['settings']->mail_from_name }}" placeholder="{{ config('app.name') }}" />
                                </div>
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group">
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn btn-primary" id="btn-test-mail">Send Test Mail</button>
                                <button type="submit" class="btn btn-success">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test mail modal -->
<div class="modal fade" id="testMailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Send Test Mail</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="test-mail-form" method="post" action="{{ route('email-settings.test') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token()}}" />
                    <div class="form-group">
                        <label>E-Mail Address</label>
                        <input class="form-control" name="email" placeholder="Enter email" />
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-send-test">Send</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('footer')
<script>
    $(function(){
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN':'{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        $('#email-settings-form').on('submit', function(e){
            e.preventDefault();
            var $form = $(this);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function(resp){
                    new PNotify({title: 'Success', text: resp.success_message || 'Saved', type: 'success'});
                },
                error: function(xhr){
                    var msg = (xhr.responseJSON && xhr.responseJSON.error_message) ? xhr.responseJSON.error_message : 'Could not save';
                    new PNotify({title: 'Error', text: msg, type: 'error'});
                }
            });
        });

        $('#btn-test-mail').on('click', function(){
            $('#testMailModal').modal('show');
        });

        $('#btn-send-test').on('click', function(){
            var $form = $('#test-mail-form');
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function(resp){
                    $('#testMailModal').modal('hide');
                    new PNotify({title: 'Success', text: resp.success_message || 'Sent', type: 'success'});
                },
                error: function(xhr){
                    var msg = (xhr.responseJSON && xhr.responseJSON.error_message) ? xhr.responseJSON.error_message : 'Could not send';
                    new PNotify({title: 'Error', text: msg, type: 'error'});
                }
            });
        });
    });
</script>
@stop
