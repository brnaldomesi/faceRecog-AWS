@extends('layouts.master')

@section('extracss')
	<link href="{{ asset('global/plugins/select2/select2.css') }}" rel="stylesheet">
  <link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
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
                Create
              </li>
            </ul>

            <div class="portlet light col-md-6 col-md-offset-3">
					    <div class="portlet-title">
					      <div class="caption">
					        <span class="caption-subject font-green-sharp bold uppercase">Create New Case</span>
					      </div>
					    </div>
					    <div class="portlet-body">
		            {!! Form::open(['route' => 'cases.show', 'class' => 'form-horizontal']) !!}
		            
		            @include ('cases.form')
		            
		            {!! Form::close() !!}
		          </div>
            </div>
      
    </div>
  </div>
</div>
@endsection

@section('extrajs')
	<script type="text/javascript" src="{{ asset('global/plugins/select2/select2.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
  <script type="text/javascript" src="{{ asset('admin_assets/pages/scripts/table-managed.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/cases/cases.js') }}"></script>
@endsection