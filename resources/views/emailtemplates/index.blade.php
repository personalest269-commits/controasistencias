@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="page-title">
        <div class="title_left">
            <h3>Email Templates</h3>
        </div>
    </div>
    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Templates</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['templates'] as $t)
                            <tr>
                                <td>{{ $t->name }}</td>
                                <td><code>{{ $t->slug }}</code></td>
                                <td>
                                    <a class="btn btn-xs btn-primary" href="{{ route('email-templates.edit', $t->slug) }}">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop