@extends('layouts.master')

@section('extracss')
  <link href="{{ asset('global/plugins/select2/select2.css') }}" rel="stylesheet">
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
        <a href="{{ route('admin') }}">Admin</a><i class="fa fa-circle"></i>
        </li>
		<li>
        <a href="{{ route('admin.sharing.show') }}">Data Sharing</a><i class="fa fa-circle"></i>
        </li>
		<li>
        </li>
        <li class="active">
        {{ isset($user) ? $user->name : 'Edit'}}
        </li>
      </ul>

      <div class="col-md-6 col-md-offset-3">
      <div class="portlet light">
        <div class="portlet-title">
          <div class="caption">
		  
            <span class="caption-subject font-green-sharp bold uppercase">{{ $organization[0]->name }}</span>

          </div>
        </div>
        <div class="portlet-body">
  @if (isset($user))
          {!! Form::model($user, ['route' => ['admin.id.update', $user->id], 'method' => 'PUT', 'class' => 'form-horizontal']) !!}
  @else
          {!! Form::open(['route' => ['admin.create'], 'method' => 'POST', 'class' => 'form-horizontal']) !!}
  @endif
            
           <div class="form-group {{ $errors->has('email') ? ' has-error' : ''}}">
             {!! Form::label('email', 'E-Mail ', ['class' => 'col-md-4 control-label']) !!}
              <div class="col-md-6">
                {!! Form::email('email', null, ['class' => 'form-control', 'required' => 'required']) !!}
                {!! $errors->first('email', '<p class="help-block">:message</p>') !!}
              </div>
            </div>

            <div class="form-group {{ $errors->has('name') ? ' has-error' : ''}}">
              {!! Form::label('name', 'Name ', ['class' => 'col-md-4 control-label']) !!}
              <div class="col-md-6">
                {!! Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) !!}
                {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
              </div>
            </div>

            <div class="form-group {{ $errors->has('password') ? ' has-error' : ''}}">
              {!! Form::label('password', 'Password ', ['class' => 'col-md-4 control-label']) !!}
              <div class="col-md-6">
  @if (isset($user))
                {!! Form::password('password', ['class' => 'form-control', 'placeholder' => 'Blank would leave it unchanged']) !!}
  @else
                {!! Form::password('password', ['class' => 'form-control', 'placeholder' => 'Blank would set it to "123456789"']) !!}
  @endif
                {!! $errors->first('password', '<p class="help-block">:message</p>') !!}
              </div>
            </div>

            <div class="form-group">
              {!! Form::label('password_confirmation', 'Confirm ', ['class' => 'col-md-4 control-label']) !!}
              <div class="col-md-6">
                {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
              </div>
            </div>

            <div class="form-group">
              {!! Form::label('organization', 'Organization ', ['class' => 'col-md-4 control-label']) !!}
              <div class="col-md-6">
                {!! Form::text('organization', $organization, ['class' => 'form-control bs-select', 'disabled' => 'disabled']) !!}
              </div>
            </div>
  @if (isset($user))
            <div class="form-group">
              {!! Form::label('loginCount', 'Login Count ', ['class' => 'col-md-4 control-label']) !!}
              <div class="col-md-6">
                {!! Form::text('loginCount', null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
              </div>
            </div>

            <div class="form-group">
              {!! Form::label('lastlogin', 'Last Joined ', ['class' => 'col-md-4 control-label']) !!}
              <div class="col-md-6">
                {!! Form::text('lastlogin', null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
              </div>
            </div>
  @endif
            <hr>
            <div class="form-group">
              <div class="col-md-offset-3 col-md-6" style="text-align: center;">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('admin') }}" class="btn btn-default">
                  Cancel
                </a>
              </div>
            </div>
          {!! Form::close() !!}
          </div>
        </div>
      </div>

  </div>
  </div>
</div>
@endsection

@section('extrajs')



  <script type="text/javascript" src="{{ asset('global/plugins/select2/select2.min.js') }}"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/jquery.ui.widget.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/load-image.min.js') }}" type="text/javascript"></script>

@endsection