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
      <!-- <div class="row justify-content-center">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">Enroll</div>

            <div class="card-body"> -->
            <ul class="page-breadcrumb breadcrumb">
              <li>
                <a href="{{ route('root') }}">Home</a><i class="fa fa-circle"></i>
              </li>
			  <li><a href="{{ route('admin') }}">Admin</a><i class="fa fa-circle"></i></li>
              <li class="active">
                User Management
              </li>
            </ul>
            <div class="row">
              {!! Form::open(['method' => 'DELETE', 'class' => 'form-delete form-horizontal']) !!}
              {!! Form::close() !!}
			  <div class="col-md-12">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light">
                  <div class="portlet-title">
                    <div class="caption">
                      <i class="fa fa-cogs font-green-sharp"></i>
                      <span class="caption-subject font-green-sharp bold uppercase">User Management</span>
                    </div>
                  </div>
                  <div class="portlet-body">
                    <div class="table-toolbar">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="btn-group">
                            <a href="{{ route('admin.create.show') }}" class="btn green">
                            Add New User
                            <i class="fa fa-plus"></i>
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                    <table class="table table-striped table-hover" id="table-user">
                    <thead>
                    <tr>
                      <th>
                         #
                      </th>
                      <th>
                         User name
                      </th>
                      <th>
                         E-mail
                      </th>
                      <th>
                         Login Count
                      </th>
                      <th>
                         Last Login
                      </th>
                      <th>
                         Action
                      </th>
                    </tr>
                    </thead>
                    <tbody>
					
						@foreach ($users as $key => $user)
							<tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }}" onClick="javascript:location.href='{{ route('admin.id.show', ['id' => $user->id]) }}';">
								<td>{{ $key + 1 }}</td>
              					<td>
									{{ $user->name }}
              					</td>
              					<td>
									{{ $user->email }}
              					</td>
              					<td>
									{{ $user->loginCount }}
              					</td>
              					<td>
                  {{ Carbon\Carbon::parse($user->lastlogin)->format("m/d/Y H:i:s") }}
              					</td>
								<td>
									@if (Auth::user()->id != $user->id)
										<button class="btn btn-danger delete" url="{{ route('admin.id.delete', $user) }}"><i class="fa fa-trash"></i> &nbsp;Delete</button>
									@endif
								</td>
							</tr>
						@endforeach
                    </tbody>
                    </table>
                  </div>
                </div>
                <!-- END EXAMPLE TABLE PORTLET-->
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
  <script type="text/javascript" src="{{ asset('js/Admin/index.js') }}"></script>
  <script src="{{ asset('global/plugins/bootbox/bootbox.min.js') }}" type="text/javascript"></script>

  <script>
    @if (\Session::has('isUserCreated'))
      bootbox.alert('New user has been created');
    @endif
    @if (\Session::has('isUserSaved'))
      bootbox.alert('Updated user information successfully');
    @endif
  </script>
@endsection



