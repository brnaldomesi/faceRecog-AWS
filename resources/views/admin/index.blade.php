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
        <h1>Admin</h1>
      </div>
      <!-- END PAGE TITLE -->
    </div>
  </div>
  <!-- END PAGE HEAD -->
  <div class="page-content">
    <div class="container">

		<ul class="page-breadcrumb breadcrumb">
			<li><a href="{{ route('root') }}">Home</a><i class="fa fa-circle"></i></li>
            <li class="active">Admin</li>
        </ul>
        
		<div class="row">
			<div class="container text-center">
			{!! Form::open(['method' => 'DELETE', 'class' => 'form-delete form-horizontal']) !!}
            {!! Form::close() !!}

				<div class="tiles center-tile">
					<div class="tile bg-blue">
						<a href="{{ route('admin.manageusers.show') }}">
						<div class="tile-body">
							<i class="fa fa-users"></i>
						</div>
						<div class="tile-object">
								<center>Users</center>
						</div>
						</a>
					</div>
					
					<div class="tile bg-blue">
						<a href="{{ route('admin.sharing.show') }}">
						<div class="tile-body">
							<i class="fa fa-sitemap"></i>
						</div>
						<div class="tile-object">
								<center>Data Sharing</center>
						</div>
						</a>
					</div>
					
					<div class="tile bg-blue">
						<a href="{{ route('admin.activity.show') }}">
						<div class="tile-body">
							<i class="fa fa-file"></i>
						</div>
						<div class="tile-object">
								<center>Activity Log</center>
						</div>
						</a>
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
	<script type="text/javascript" src="{{ asset('global/plugins/select2/select2.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/admin/index.js') }}"></script>
@endsection