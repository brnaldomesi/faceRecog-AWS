@extends('layouts.master')

@section('extracss')
  <link href="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.css') }}" rel="stylesheet">
  <link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
  <link href="{{ asset('global/plugins/fancybox/source/jquery.fancybox.css') }}" rel="stylesheet" type="text/css"/>
  <link href="{{ asset('css/compare.css') }}" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1,user-scalable=0"/>   
@endsection

@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
  <div class="page-head">
    <div class="container">
      <!-- BEGIN PAGE TITLE -->
      <div class="page-title">
        <h1>Quick Search</h1>
      </div>
      <!-- END PAGE TITLE -->
    </div>
  </div>
  <!-- END PAGE HEAD -->
  <div class="page-content">
    <div class="container">
      <!-- START PAGE BREADCRUMB -->
      <ul class="page-breadcrumb breadcrumb">
        <li>
          <a href="{{ url('/') }}">Home</a><i class="fa fa-circle"></i>
        </li>
        <li class="active">
          Quick Search
        </li>
      </ul>
      <!-- END PAGE BREADCRUMB -->
      <!-- BEGIN PAGE CONTENT INNER -->
      <div class="row">
	  @if (Auth::user()->permission->isSuperAdmin() === false)
        <div class="col-md-6">
          <div class="portlet light">
            <div class="portlet-title">
              <div class="caption">
                <i class="fa fa-user-times font-green-sharp"></i>
                <span class="caption-subject font-green-sharp bold uppercase">Quick Search</span>
              </div>
            </div>
            <div class="portlet-body">
			  <input type="hidden" id="hidden-search-url" value="{{ route('quicksearch.search') }}">
              <input type='hidden' id="route-quicksearch-create" value="{{route('quicksearch.create')}}"></input>
              {!! Form::open(['id' => 'quickSearchForm', 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
                @include ('quicksearch.create')
              {!! Form::close() !!}
            </div>
          </div>
        </div>
	  @endif
	   @if (Auth::user()->permission->isSuperAdmin())
		   <div class="col-md-8">
	   @else
        <div class="col-md-6">
       @endif
          <div class="portlet light">
            <div class="portlet-title">
              <div class="caption">
                <i class="fa fa-history font-green-sharp"></i>
                <span class="caption-subject font-green-sharp bold uppercase">Quick Search History</span>
              </div>
            </div>
            <div class="portlet-body">
			<input type='hidden' id="route-quicksearch-history" value="{{route('quicksearch.history')}}"></input>
              {!! Form::open(['id' => 'quickSearchHistoryForm', 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
              
			  <div class="form-group text-center">
				<div class="col-md-12">
					<table class="table table-striped table-hover" id="table-quicksearch-history">
						<thead>
						<tr>
							<th>
								#
							</th>
							<th>
								Image
							</th>
							<th>
								Reference
							</th>
							<th>
								Searched
							</th>
							@if (Auth::user()->permission->isAdmin())
							<th>
								User
							</th>
							@endif							
						</tr>
						</thead>
						<tbody>
						@foreach ($quicksearch_history as $key => $value)
							<tr history-no="{{ $value->id }}">
								<td>{{ $key + 1 }}</td>
								<td>
									<a href="{{env('AWS_S3_REAL_OBJECT_URL_DOMAIN')}}storage/search/images/{{ $value->filename }}" class="fancybox-button" data-rel="fancybox-button">
									<img src="{{env('AWS_S3_REAL_OBJECT_URL_DOMAIN')}}storage/search/images/{{ $value->filename}}" style="max-width:60px"/>
										{{-- <div>{{ $value->filename }}</div> --}}
									</a>
								</td>
								<td>
								{{ $value->reference }}</td>
								</td>
								<td>{{ Carbon\Carbon::parse($value->created_at)->format("m/d/Y H:i")}}</td>
								@if (Auth::user()->permission->isAdmin())
								<td>
								{{ $value->name }}</td>
								</td>
								@endif
							</tr>
						@endforeach
						</tbody>
					</table>
				</div>
			</div>
			  
              {!! Form::close() !!}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
@section('extrajs')
<script>
	var url_getfacedetailinfo = '{{ route('search.detailfaceinfo') }}';
	var url_getpersongallery = '{{ route('search.persongallery') }}';
</script>
  <script type="text/javascript" src="{{ asset('global/plugins/bootbox/bootbox.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/select2/select2.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/fancybox/source/jquery.fancybox.pack.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/fileValidation.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/quicksearch/quicksearch.js') }}"></script>
@endsection