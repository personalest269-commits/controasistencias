@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="page-title">
        <div class="title_left">
            <h3>Email Template</h3>
        </div>
    </div>
    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>{{ $data['template']->name }} <small><code>{{ $data['template']->slug }}</code></small></h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">

                    <form id="email-template-form" method="post" action="{{ route('email-templates.update', $data['template']->slug) }}">
                        <input type="hidden" name="_token" value="{{ csrf_token()}}" />

                        <div class="row" style="margin-bottom:15px;">
                            <div class="col-md-6 col-sm-12">
                                <label>Name</label>
                                <input class="form-control" name="name" value="{{ $data['template']->name }}" />
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <label>From</label>
                                <input class="form-control" name="from_name" value="{{ $data['template']->from_name }}" placeholder="Support" />
                            </div>
                        </div>

                        <div class="row" style="margin-bottom:15px;">
                            <div class="col-md-12">
                                <div class="well" style="margin-bottom:0;">
                                    <strong>Variables</strong>
                                    <div class="row" style="margin-top:10px;">
                                        @php($vars = $data['template']->variables ?? [])
                                        @foreach($vars as $v)
                                            <div class="col-md-3 col-sm-6 col-xs-12" style="margin-bottom:6px;">
                                                <span class="label label-default">{{ '{'.$v.'}' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <div class="list-group" id="locale-list">
                                    @foreach($data['locales'] as $locale => $label)
                                        <a href="#" class="list-group-item locale-item @if($loop->first) active @endif" data-locale="{{ $locale }}">{{ $label }}</a>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-9 col-sm-8 col-xs-12">
                                @foreach($data['locales'] as $locale => $label)
                                    @php($tr = $data['translations'][$locale] ?? ['subject'=>'','body'=>''])
                                    <div class="locale-pane" id="pane-{{ $locale }}" style="display:@if($loop->first) block @else none @endif;">
                                        <div class="form-group">
                                            <label>Subject</label>
                                            <input class="form-control" name="subject[{{ $locale }}]" value="{{ $tr['subject'] }}" />
                                        </div>

                                        <div class="form-group">
                                            <label>Email Message</label>
                                            <textarea class="form-control ckeditor" rows="12" name="body[{{ $locale }}]" id="body-{{ $locale }}">{!! $tr['body'] !!}</textarea>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="text-right">
                                    <a href="{{ route('email-templates') }}" class="btn btn-default">Back</a>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
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

        // Locale switching
        $('#locale-list').on('click', '.locale-item', function(e){
            e.preventDefault();
            var locale = $(this).data('locale');
            $('.locale-item').removeClass('active');
            $(this).addClass('active');
            $('.locale-pane').hide();
            $('#pane-' + locale).show();
        });

        // Initialize CKEditor (loaded in master)
        if (typeof CKEDITOR !== 'undefined') {
            $('.ckeditor').each(function(){
                var id = $(this).attr('id');
                if (id && !CKEDITOR.instances[id]) {
                    CKEDITOR.replace(id);
                }
            });
        }

        $('#email-template-form').on('submit', function(e){
            e.preventDefault();

            // Sync CKEditor -> textarea values
            if (typeof CKEDITOR !== 'undefined') {
                for (var instance in CKEDITOR.instances) {
                    CKEDITOR.instances[instance].updateElement();
                }
            }

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
    });
</script>
@stop
