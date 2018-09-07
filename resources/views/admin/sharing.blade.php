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
                Data Sharing
              </li>
            </ul>
            <div class="row">
              {!! Form::open(['method' => 'PUT', 'id' => 'form-share', 'class' => 'form-delete form-horizontal']) !!}
                {!! Form::hidden('action_type', null, ['id' => 'hidden-action-type']) !!}
                {!! Form::hidden('organization', null, ['id' => 'hidden-organization']) !!}
              {!! Form::close() !!}
			        <div class="col-md-6">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light">
                  <div class="portlet-title">
                    <div class="caption">
                      <i class="fa fa-arrow-right font-green-sharp"></i>&nbsp;
                      <span class="caption-subject font-green-sharp bold uppercase">Your Sharing Applications</span>
                    </div>
                  </div>
                  <div class="portlet-body">
                    <div class="table-toolbar">
                      Click&nbsp;&nbsp;
                      <span class="label label-danger">Declined</span>&nbsp;&nbsp;
                      or&nbsp;&nbsp;
                      <span class="label label-default">Available</span>&nbsp;&nbsp;
                      to apply sharing
                    </div>
                    <table class="table table-striped">
                    <thead>
                    <tr>
                      <th>
                         #
                      </th>
                      <th>
                         Organization
                      </th>
                      <th>
                         Date
                      </th>
                      <th>
                         Status
                      </th>
                    </tr>
                    </thead>
                    <tbody>
					
                    @foreach ($organizations as $key => $organization)
                      <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }}">
                        <td>{{ $key + 1 }}</td>
                        <td>
                          {{ $organization->name }}
                        </td>
                        <td>
                          @php $data = '' @endphp
                          
                          @foreach ($sharing_out as $key => $agency)
                            @if ($agency->organization_requestor === $organization->id)
                              @php $data = $agency->status @endphp
                              {{ $agency->updated_at->format('Y-m-d') }}
                              @break
                            @endif
                          @endforeach
                        </td>
                        <td>									
                          @if ($data === 'ACTIVE')
                            <span class="label label-success">Active</span>
                          @elseif ($data === 'PENDING')
                            <span class="label label-info">Pending Approval</span>
                          @elseif ($data === 'DECLINED')
                            <span class="label label-danger hover action-apply" organization="{{ $organization->id }}">Declined</span>
                          @else
                            <span class="label label-default hover action-apply" organization="{{ $organization->id }}">Available</span>
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

              <div class="col-md-6">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light">
                  <div class="portlet-title">
                    <div class="caption">
                      <i class="fa fa-arrow-left font-green-sharp"></i>&nbsp;
                      <span class="caption-subject font-green-sharp bold uppercase">Applications to you</span>
                    </div>
                  </div>
                  <div class="portlet-body">
                    <table class="table table-striped">
                    <thead>
                    <tr>
                      <th>
                         #
                      </th>
                      <th>
                         Organization
                      </th>
                      <th>
                        Date
                      </th>
                      <th></th>
                    </tr>
                    </thead>
                    <tbody>
					
                    @foreach ($sharing_in as $key => $sharing)
                      <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }}">
                        <td>{{ $key + 1 }}</td>
                        <td>
                          {{ $sharing->owner->name }}
                        </td>
                        <td>
                          {{ $sharing->updated_at->format('Y-m-d') }}
                        </td>
                        <td>
                          @if ($sharing->status === 'ACTIVE')
                            <span class="label label-success">Active</span>
                          @elseif ($sharing->status === 'PENDING')
                            <span class="label label-info hover action-approve" organization="{{ $sharing->organization_owner }}">Approve</span>
                            <span class="label label-danger hover action-decline" organization="{{ $sharing->organization_owner }}">Decline</span>
                          @elseif ($sharing->status === 'DECLINED')
                            <span class="label label-danger">Declined</span>
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
  <script type="text/javascript" src="{{ asset('js/admin/index.js') }}"></script>
@endsection