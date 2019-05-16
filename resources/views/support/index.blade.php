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
        <h1>Help & Support</h1>
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
                Help & Support
              </li>
            </ul>
            <div class="row">
              <div class="col-md-12">
			  
				<h3>For technical support, please email us at <a href="mailto:support@afrengine.com?subject=Support Question">support@afrengine.com</a></h3>
				<br>
				
				<h2>Tutorial Videos</h2>
				<br>
				
				<h3>How to crop an image for your cases</h3>
				<br>
				<iframe src="https://player.vimeo.com/video/336662042" width="640" height="349" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>

				<h3>How to create a new case and add an image</h3>
				<br>
				<iframe src="https://player.vimeo.com/video/336670409" width="640" height="349" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
				
				<h3>How to perform a case search</h3>
				<br>
				<iframe src="https://player.vimeo.com/video/336675379" width="640" height="349" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
				
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
	<script type="text/javascript" src="{{ asset('global/plugins/select2/select2.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
@endsection