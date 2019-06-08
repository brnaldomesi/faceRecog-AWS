@extends('layouts.master')
{{--
@section('sidebar')
<nav id="sidebar-nav">
    <ul class="nav nav-pills nav-stacked d-block">
        <li><a href="#">&nbsp;&nbsp;Local</a></li>
        <li><a href="#">&nbsp;&nbsp;URL</a></li>
        <li><a href="#">&nbsp;&nbsp;CSV</a></li>
    </ul>
</nav>
@endsection
--}}
@section('extracss')
    <!-- <link href="{{ asset('global/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/> -->
    <link href="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.css') }}" rel="stylesheet">
    <link href="{{ asset('global/plugins/bootstrap-datepicker/css/datepicker3.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
  <div class="page-head">
    <div class="container">
      <!-- BEGIN PAGE TITLE -->
      <div class="page-title">
        <h1>Enroll</h1>
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
                <a href="{{ url('/') }}">Home</a><i class="fa fa-circle"></i>
              </li>
              <li class="active">
                Enroll
              </li>
            </ul>
            <!-- END PAGE BREADCRUMB -->
            <!-- BEGIN PAGE CONTENT INNER -->
            @include ('portraits.form')
            <!-- </div>
          </div>
        </div>
      </div> -->
    </div>
  </div>
</div>
@endsection
@section('extrajs')
    <script type="text/javascript" src="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.js') }}"></script>
    <script type="text/javascript" src="{{ asset('global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <script type="text/javascript" src="{{ asset('admin_assets/pages/scripts/components-pickers.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/portraits.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/fileValidation.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/portraits/create.js') }}"></script>
@endsection