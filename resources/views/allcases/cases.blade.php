@extends('layouts.master')

@section('extracss')
	<link href="{{ asset('global/plugins/select2/select2.css') }}" rel="stylesheet">
	<link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
	<link href="{{ asset('global/plugins/jquery-file-upload/blueimp-gallery/blueimp-gallery.min.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('global/plugins/jquery-file-upload/css/jquery.fileupload.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('global/plugins/fancybox/source/jquery.fancybox.css') }}" rel="stylesheet" type="text/css"/>
	<link href="{{ asset('global/plugins/jquery-file-upload/css/jquery.fileupload-ui.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('css/cases.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
  <div class="page-head">
	<div class="container">
	  <!-- BEGIN PAGE TITLE -->
	  <div class="page-title">
		<h1>Case</h1>
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
				<a href="{{ route('allcases.show') }}">All Cases</a><i class="fa fa-circle"></i>
			  </li>
			  <li>
				<a href="{{ route('allcases.org.show', ['id' => $cases->organization]) }}">{{ $cases->organization->name }} Cases</a><i class="fa fa-circle"></i>
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
					{!! Form::model($cases, ['method' => 'GET', 'class' => 'form-horizontal']) !!}
					<div class="form-group">
						<label class="col-md-4 control-label">Organization</label>
						<div class="col-md-6">
							<input type="text" name="organizationName" value="{{ $cases->organization->name }}" class="form-control" disabled="disabled" />
						</div>
					</div>

					<div class="form-group {{ $errors->has('caseNumber') ? ' has-error' : ''}}">
						{!! Form::label('caseNumber', 'Case # ', ['class' => 'col-md-4 control-label']) !!}
						<div class="col-md-6">
						  {!! Form::text('caseNumber', null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
						  {!! $errors->first('caseNumber', '<p class="help-block">:message</p>') !!}
						</div>
					</div>

					<div class="form-group {{ $errors->has('type') ? ' has-error' : ''}}">
						{!! Form::label('type', 'Type ', ['class' => 'col-md-4 control-label']) !!}
						<div class="col-md-6">
						  {!! Form::text('type', null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
						  {!! $errors->first('type', '<p class="help-block">:message</p>') !!}
						</div>
					</div>

					<div class="form-group">
						{!! Form::label('status', 'Status ', ['class' => 'col-md-4 control-label']) !!}
						<div class="col-md-6">
						  {!! Form::select('status', ['ACTIVE' => 'Active', 'SOLVED' => 'Solved', 'CLOSED' => 'Closed'], null,
						  $cases->status == 'ACTIVE' 
						  ? ['class' => 'form-control bs-select', 'required' => 'required', 'disabled' => 'disabled'] 
						  : ['class' => 'form-control bs-select', 'required' => 'required', 'disabled' => 'disabled']) !!}
						</div>
					</div>

					<div class="form-group">
						{!! Form::label('dispo', 'Disposition ', ['class' => 'col-md-4 control-label']) !!}
						<div class="col-md-6">
						  {!! Form::textarea('dispo', null, ['class' => 'form-control autosize', 'disabled' => 'disabled']) !!}
						</div>
					</div>

					<div class="form-group">
						{!! Form::label(null, 'Creator ', ['class' => 'col-md-4 control-label']) !!}
						<div class="col-md-6">
						  {!! Form::text(null, $cases->user->name, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
						</div>
					</div>
					<div class="form-group">
						{!! Form::label(null, 'Created Date ', ['class' => 'col-md-4 control-label']) !!}
						<div class="col-md-6">
						  {!! Form::text(null, $cases->created_at->format('m/d/Y'), ['class' => 'form-control', 'disabled' => 'disabled']) !!}
						</div>
					</div>

					{!! Form::close() !!}
				  </div>
				  <div class="tab-pane" id="portlet_images">
				  	<input type="hidden" id="hidden-cases-status" value="{{ $cases->status }}">
					<input type="hidden" id="hidden-image-list-url" value="{{ route('allcases.id.image.show', [$cases->organization->id, $cases->id]) }}">
					<input type="hidden" id="hidden-search-url" value="{{ route('search.case') }}">
					<input type="hidden" id="hidden-search-history-url" value="{{ route('search.history') }}">
					<input type="hidden" id="hidden-crimespree-url" value="{{ route('search.crimespree') }}">

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
							{{ Carbon\Carbon::parse($value->searchedOn)->format("m/d/Y H:i") }}
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

</script>

<script type="text/javascript" src="{{ asset('global/plugins/select2/select2.js') }}"></script>
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
	var url_getfacedetailinfo = '{{ route('search.detailfaceinfo') }}';
	var url_getfacecasedetailinfo = '{{ route('search.detailfacecaseinfo') }}';
	var url_getpersongallery = '{{ route('search.persongallery') }}';
</script>
<script type="text/javascript" src="{{ asset('js/Cases/cases.js') }}"></script>
@endsection