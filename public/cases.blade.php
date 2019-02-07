@extends('layouts.master')

@section('extracss')
	<link href="{{ asset('global/plugins/select2/select2.css') }}" rel="stylesheet">
	<link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
	<link href="{{ asset('global/plugins/jquery-file-upload/blueimp-gallery/blueimp-gallery.min.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('global/plugins/jquery-file-upload/css/jquery.fileupload.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('global/plugins/fancybox/source/jquery.fancybox.css') }}" rel="stylesheet" type="text/css"/>
	<link href="{{ asset('global/plugins/jquery-file-upload/css/jquery.fileupload-ui.css') }}" rel="stylesheet" type="text/css" />

@endsection

@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
  <div class="page-head">
	<div class="container">
	  <!-- BEGIN PAGE TITLE -->
	  <div class="page-title">
		<h1>Cases</h1>
	  </div>
	  <!-- END PAGE TITLE -->
	</div>
  </div>
  <!-- END PAGE HEAD -->
  <div class="page-content">
	<div class="container">
	  <!-- <div class="row justify-content-center">
		<div class="col-md-12">
		  <div class="card">
			<div class="card-header">Enroll</div>

			<div class="card-body"> -->
			<ul class="page-breadcrumb breadcrumb">
			  <li>
				<a href="{{ route('root') }}">Home</a><i class="fa fa-circle"></i>
			  </li>
			  <li>
				<a href="{{ route('cases.show') }}">Cases</a><i class="fa fa-circle"></i>
			  </li>
			  <li class="active">
				{{ $cases->caseNumber }}
			  </li>
			</ul>

		  <div class="col-md-6">
			<div class="portlet light">
				<div class="portlet-title tabbable-line">
					<div class="caption">
						<span class="caption-subject font-green-sharp bold uppercase">Case Details</span>
					</div>
					<ul class="nav nav-tabs">
						<li class="active">
							<a href="#portlet_primary" data-toggle="tab"> Primary </a>
						</li>
						<li>
							<a href="#portlet_images" data-toggle="tab"> Images </a>
						</li>
					</ul>
				</div>
				<div class="portlet-body">
				<div class="tab-content">
				  <div class="tab-pane active" id="portlet_primary">
					{!! Form::model($cases, ['route' => ['cases.id.update', $cases->id], 'method' => 'PUT', 'class' => 'form-horizontal']) !!}
						
					 <div class="form-group {{ $errors->has('caseNumber') ? ' has-error' : ''}}">
						 {!! Form::label('caseNumber', 'Case # ', ['class' => 'col-md-4 control-label']) !!}
						  <div class="col-md-6">
							  {!! Form::text('caseNumber', null, ['class' => 'form-control', 'required' => 'required']) !!}
							  {!! $errors->first('caseNumber', '<p class="help-block">:message</p>') !!}
						  </div>
					  </div>

					  <div class="form-group {{ $errors->has('type') ? ' has-error' : ''}}">
						  {!! Form::label('type', 'Type ', ['class' => 'col-md-4 control-label']) !!}
						  <div class="col-md-6">
							  {!! Form::text('type', null, ['class' => 'form-control', 'required' => 'required']) !!}
							  {!! $errors->first('type', '<p class="help-block">:message</p>') !!}
						  </div>
					  </div>

					  <div class="form-group">
						  {!! Form::label('status', 'Status ', ['class' => 'col-md-4 control-label']) !!}
						  <div class="col-md-6">
							  {!! Form::select('status', ['ACTIVE' => 'Active', 'SOLVED' => 'Solved', 'CLOSED' => 'Closed'], null,
							  $cases->status == 'ACTIVE' ? ['class' => 'form-control bs-select', 'required' => 'required']
							  							 : ['class' => 'form-control bs-select', 'required' => 'required', 'disabled' => 'disabled']) !!}
						  </div>
					  </div>

					  <div class="form-group {{ $cases->status != 'CLOSED' ? 'hidden' : '' }}">
						  {!! Form::label('dispo', 'Disposition ', ['class' => 'col-md-4 control-label']) !!}
						  <div class="col-md-6">
							  {!! Form::textarea('dispo', null, ['class' => 'form-control autosize', 'required' => 'required', 'disabled' => 'disabled']) !!}
						  </div>
					  </div>

					  <div class="form-group">
						  {!! Form::label(null, 'Creator Name ', ['class' => 'col-md-4 control-label']) !!}
						  <div class="col-md-6">
							  {!! Form::text(null, Auth::user()->name, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
						  </div>
					  </div>
					  <div class="form-group">
						  {!! Form::label(null, 'Create Date ', ['class' => 'col-md-4 control-label']) !!}
						  <div class="col-md-6">
							  {!! Form::text(null, $cases->created_at, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
						  </div>
					  </div>

					  <hr>
					  <div class="form-group">
						  <div class="col-md-offset-4 col-md-4">
							  {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
							  <a href="{{ route('cases.show') }}" class="btn btn-default">
								  Cancel
							  </a>
						  </div>
					  </div>
					{!! Form::close() !!}
				  </div>
				  <div class="tab-pane" id="portlet_images">
				  	<input type="hidden" id="hidden-cases-status" value="{{ $cases->status }}">

				  @if ($cases->status == 'ACTIVE')
				  	<form id="enrollForm" action="{{ route('cases.id.image.add', $cases->id) }}" method="POST" class="form-horizontal" >
						{{ csrf_field() }}
                        <!-- The fileupload-button bar contains buttons to add/delete files and start/cancel the upload -->
                        <div class="row fileupload-buttonbar">
                            <div class="col-lg-12 text-center">
                                <!-- The fileinput-button span is used to style the file input field as button -->
                                <span class="btn green fileinput-button">
                                    <i class="fa fa-plus"></i>
                                    <span> Add files... </span>
                                    <input type="file" name="files[]" multiple=""> </span>
                                <button type="submit" id="id_btn_start_upload1" class="btn blue start">
                                    <i class="fa fa-upload"></i>
                                    <span> Start upload </span>
                                </button>
                                <button type="reset" class="btn warning cancel">
                                    <i class="fa fa-ban-circle"></i>
                                    <span> Cancel upload </span>
                                </button>
                                <button type="reset" class="btn yellow clean">
                                    <i class="fa fa-check"></i>
                                    <span> Clean </span>
                                </button>
                                <!-- The global file processing state -->
                                <span class="fileupload-process"> </span>
                            </div>
                            <!-- The global progress information -->
                            <div class="col-lg-12 fileupload-progress fade">
                                <!-- The global progress bar -->
                                <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar progress-bar-success" style="width:0%;"> </div>
                                </div>
                                <!-- The extended global progress information -->
                                <div class="progress-extended"> &nbsp; </div>
                            </div>
                        </div>
                        <!-- The table listing the files available for upload/download -->
                        <table role="presentation" class="table table-striped clearfix">
                            <tbody class="files"> </tbody>
                        </table>
                    </form>
					<hr>
				@endif
					<input type="hidden" id="hidden-image-list-url" value="{{ route('cases.id.image.show', $cases->id) }}">
					<input type="hidden" id="hidden-search-url" value="{{ route('search.case') }}">
					<input type="hidden" id="hidden-search-history-url" value="{{ route('search.history') }}">

					<table class="table table-striped table-hover" id="table-image-list">
						<thead>
						<tr>
							<th>
								 #
							</th>
							<th>
								 Image
							</th>
							<th>
								 Last Searched
							</th>
							<th>
								 Action
							</th>
						</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

		  </div>


	  <div class="col-md-6">
		<div class="portlet light">
		  <div class="portlet-title">
			<div class="caption">
			  <span class="caption-subject font-green-sharp bold uppercase">Search History</span>
			</div>
		  </div>
		  <div class="portlet-body">
	  		<table class="table table-striped table-hover" id="table-search-history">
				<thead>
				<tr>
					<th>
						 #
					</th>
					<th>
						 Image
					</th>
					<th>
						 Date Searched
					</th>
					<th>
						 Results
					</th>
				</tr>
				</thead>
				<tbody>
		@foreach ($search_history as $key => $value)
					<tr history-no="{{ $value->id }}">
						<td>
							{{ $key + 1 }}
						</td>
						<td>
							<a href="{{ asset($value->image->file_url) }}" class="fancybox-button" data-rel="fancybox-button">
								<img src="{{ asset($value->image->thumbnail_url) }}" style="width:96px"/>
								{{-- <div>{{ $value->image->filename }}</div> --}}
							</a>
						</td>
						<td>
							{{ $value->searchedOn }}
						</td>
						<td>
							{{count($value->results['data_list'])}}
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
</div>
@endsection

@section('extrajs')

<!-- The blueimp Gallery widget -->
<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" data-filter=":even">
	<div class="slides">
	</div>
	<h3 class="title"></h3>
	<a class="prev">
	‹ </a>
	<a class="next">
	› </a>
	<a class="close white">
	</a>
	<a class="play-pause">
	</a>
	<ol class="indicator">
	</ol>
</div>
<!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td>
            <span class="preview"></span>
        </td>
        <td>
            <p class="name">{%=file.name%}</p>
		<select name="gender" id="gender">
			<option value="MALE">Male</option>
			<option value="FEMALE">Female</option>
		</select>
            <strong class="error text-danger label label-danger"></strong>
        </td>
        <td>
            <p class="size">Processing...</p>
            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="progress-bar progress-bar-success" style="width:0%;"></div>
            </div>
        </td>
        <td>
            {% if (!i && !o.options.autoUpload) { %}
                <button class="btn blue start" disabled>
                    <i class="fa fa-upload"></i>
                    <span>Start</span>
                </button>
            {% } %}
            {% if (!i) { %}
                <button class="btn red cancel">
                    <i class="fa fa-ban"></i>
                    <span>Cancel</span>
                </button>
            {% } %}
        </td>
    </tr>
{% } %}
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
        {% for (var i=0, file; file=o.files[i]; i++) { %}
            <tr class="template-download fade">
                <td>
                    <span class="preview">
                        {% if (file.thumbnailUrl) { %}
                            <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
                        {% } %}
                    </span>
                </td>
                <td>
                    <p class="name">
                        {% if (file.url) { %}
                            <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
                        {% } else { %}
                            <span>{%=file.name%}</span>
                        {% } %}
                    </p>
                    {% if (file.error) { %}
                        <div><span class="label label-danger">Error</span> {%=file.error%}</div>
                    {% } %}
                </td>
                <td>
                    <span class="size">{%=o.formatFileSize(file.size)%}</span>
                </td>
                <td>
                    {% if (file.deleteUrl) { %}
                        <button class="btn red delete btn-sm" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
                            <i class="fa fa-trash-o"></i>
                            <span>Delete</span>
                        </button>
                        <input type="checkbox" name="delete" value="1" class="toggle">
                    {% } else { %}
                        <button class="btn yellow cancel btn-sm">
                            <i class="fa fa-ban"></i>
                            <span>Cancel</span>
                        </button>
                    {% } %}
                </td>
            </tr>
        {% } %}

        var cases_id = {{$cases->id}};
    </script>


	<script type="text/javascript" src="{{ asset('global/plugins/select2/select2.min.js') }}"></script>
  	<script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
  	<script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
  	<script type="text/javascript" src="{{ asset('admin_assets/pages/scripts/table-managed.js') }}"></script>

<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/jquery.ui.widget.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/tmpl.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/load-image.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/canvas-to-blob.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/blueimp-gallery/jquery.blueimp-gallery.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.iframe-transport.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-process.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-image.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-audio.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-video.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-validate.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-ui.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/fancybox/source/jquery.fancybox.pack.js') }}" type="text/javascript"></script>


<script src="{{ asset('admin_assets/pages/scripts/form-fileupload.js') }}" type="text/javascript"></script>
<script>
	var url_getfacedetailinfo = '{{route('search.detailfaceinfo')}}';
</script>
<script type="text/javascript" src="{{ asset('js/Cases/cases.js') }}"></script>
@endsection