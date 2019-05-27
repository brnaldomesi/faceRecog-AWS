@extends('layouts.master')
@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
  <div class="page-head">
    <div class="container">
      <!-- BEGIN PAGE TITLE -->
      <div class="page-title">
        <h1>Organizations</h1>
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
                <a href="{{ route('organization') }}">Organizations</a><i class="fa fa-circle"></i>
              </li>
              <li class="active">
                Create
              </li>
            </ul>

            <div class="portlet light col-md-6 col-md-offset-3">
					    <div class="portlet-title">
					      <div class="caption">
					        <span class="caption-subject font-green-sharp bold uppercase">Create New Organization</span>
					      </div>
					    </div>
					    <div class="portlet-body">
		            {!! Form::open(['route' => 'organization.create', 'class' => 'form-horizontal']) !!}
		            
		            @include ('organization.form')
		            
		            {!! Form::close() !!}
		          </div>
            </div>
      
    </div>
  </div>
</div>
@endsection