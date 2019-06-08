@extends('layouts.master')

@section('extracss')
	<link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
  <link href="{{ asset('global/plugins/select2/select2.css') }}" rel="stylesheet">
  <link href="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.css') }}" rel="stylesheet">
  <link href="{{ asset('css/faces.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
  <div class="page-head">
    <div class="container">
      <!-- BEGIN PAGE TITLE -->
      <div class="page-title">
        <h1>Add</h1>
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
              <li class="active">
                Face Management
              </li>
            </ul>
            <div class="row">
              <!-- Enroll Panel -->
              <div class="col-md-6">
                <div class="portlet light">
                  <div class="portlet-title tabbable-line">
                    <div class="caption">
                      <i class="fa fa-user-plus font-green-sharp"></i>
                      <span class="caption-subject font-green-sharp bold uppercase">Enroll</span>
                    </div>
                    <ul class="nav nav-tabs">
                      <li class="active">
                        <a href="#portlet_importcsv" data-toggle="tab"> Import CSV </a>
                      </li>
                      <li>
                        <a href="#portlet_enrollphoto" data-toggle="tab"> Enroll Photo </a>
                      </li>
                    </ul>
                  </div>

                  <div class="portlet-body">
                    <div class="tab-content">
                      <input type="hidden" id="route-face-index" value="{{ route('faces.index') }}" />
				              <div class="tab-pane active" id="portlet_importcsv">
                        {!! Form::open(['id' => 'csvForm', 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
                          @include ('faces.importcsv')
                        {!! Form::close() !!}
                      </div>
                      <div class="tab-pane" id="portlet_enrollphoto">
                        {!! Form::open(['id' => 'photoForm', 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
                          @include ('faces.enrollphoto')
                        {!! Form::close() !!}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Review Panel -->
              <div class="col-md-6">
                <div class="portlet light">
                  <div class="portlet-title tabbable-line">
                    <div class="caption">
                      <i class="fa fa-check-square font-green-sharp"></i>
                      <span class="caption-subject font-green-sharp bold uppercase">Review</span>
                    </div>
                    <ul class="nav nav-tabs">
                      <li class="active">
                        <a href="#portlet_manualreview" data-toggle="tab"> Manual Review </a>
                      </li>
                      <li>
                        <a href="#portlet_ticketreview" data-toggle="tab"> Ticket Review </a>
                      </li>
                    </ul>
                  </div>
                  <div class="portlet-body">
                    <div class="tab-content">
                      <div class="tab-pane active" id="portlet_manualreview">
                        {!! Form::open(['id' => 'manualForm', 'class' => 'form-horizontal']) !!}
                          @include ('faces.manualreview')
                        {!! Form::close() !!}
                      </div>
                      <div class="tab-pane" id="portlet_ticketreview">
                        {!! Form::open(['id' => 'ticketForm', 'class' => 'form-horizontal']) !!}
                          @include ('faces.ticketreview')
                        {!! Form::close() !!}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
            <!-- END PAGE BREADCRUMB -->
            <!-- BEGIN PAGE CONTENT INNER -->
            <!-- END PAGE CONTENT INNER -->
            <!-- </div>
          </div>
        </div>
      </div> -->
    </div>
  </div>
</div>
@endsection

@section('extrajs')
  <script type="text/javascript" src="{{ asset('global/plugins/bootbox/bootbox.min.js') }}"></script>
	<script type="text/javascript" src="{{ asset('global/plugins/select2/select2.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/fileValidation.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/faces/faces.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
  <script>
    $(document).ready(function () {
      $(this).find("#organizationCSV").select2({
        placeholder: "Select Organization",
        allowClear: true,
        minimumResultsForSearch: -1
      });
      $(this).find("#organizationPhoto").select2({
        placeholder: "Select Organization",
        allowClear: true,
        minimumResultsForSearch: -1
      });
      $(this).find("#gender").select2({
        placeholder: "Select Gender",
        allowClear: true,
        minimumResultsForSearch: -1
      });
    });
  </script>
@endsection