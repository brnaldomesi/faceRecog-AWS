@extends('layouts.master')

@section('extracss')
    <link href="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.css') }}" rel="stylesheet">
    <link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
  <div class="page-head">
    <div class="container">
      <!-- BEGIN PAGE TITLE -->
      <div class="page-title">
        <h1>Face Compare</h1>
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
          Face Compare
        </li>
      </ul>
      <!-- END PAGE BREADCRUMB -->
      <!-- BEGIN PAGE CONTENT INNER -->
      <div class="row">
        <div class="col-md-6">
          <div class="portlet light">
            <div class="portlet-title">
              <div class="caption">
                <i class="fa fa-user-times font-green-sharp"></i>
                <span class="caption-subject font-green-sharp bold uppercase">Face Compare</span>
              </div>
            </div>
            <div class="portlet-body">
              <input type='hidden' id="route-compare-create" value="{{route('compare.create')}}"></input>
              <input type='hidden' id="route-compare-save" value="{{route('compare.save')}}"></input>
              <input type='hidden' id="compare-similarity-score"></input>
              {!! Form::open(['id' => 'compareCreateForm', 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
                @include ('compare.create')
              {!! Form::close() !!}
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="portlet light">
            <div class="portlet-title">
              <div class="caption">
                <i class="fa fa-history font-green-sharp"></i>
                <span class="caption-subject font-green-sharp bold uppercase">Compare History</span>
              </div>
            </div>
            <div class="portlet-body">
              <input type='hidden' id="route-compare-history" value="{{route('compare.history')}}"></input>
              {!! Form::open(['id' => 'compareHistoryForm', 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
                @include ('compare.history')
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
    <script type="text/javascript" src="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.js') }}"></script>
    <script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
    
    <script type="text/javascript" src="{{ asset('js/fileValidation.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/compare/compare.js') }}"></script>
@endsection