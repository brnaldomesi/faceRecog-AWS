@extends('layouts.master')

@section('extracss')
	<link href="{{ asset('global/plugins/select2/select2.css') }}" rel="stylesheet">
  <link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
  <link href="{{ asset('admin_assets/layout3/css/sharing.css') }}" rel="stylesheet">
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
                      <span class="caption-subject font-green-sharp bold uppercase">Share your mugshots with others</span>
                    </div>
                  </div>
                  <div class="portlet-body">
                    <div class="table-toolbar">
                      Click&nbsp;&nbsp;
                      <span class="label label-info">Apply</span>&nbsp;&nbsp;
                      or&nbsp;&nbsp;
                      <span class="label label-info">ReApply</span>&nbsp;&nbsp;
                      to send your sharing request
                    </div>
                    <div class="table-toolbar">
                      Click&nbsp;&nbsp;
                      <span class="label label-success">Approve</span>&nbsp;&nbsp;
                      or&nbsp;&nbsp;
                      <span class="label label-danger">Decline</span>&nbsp;&nbsp;
                      to reply to the sharing request
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
                    <!-- First of all, show new incoming sharing requests -->
                    @php $key = 1 @endphp
					          @foreach ($sharing_in as $sharing)
                      @if ($sharing->status == 'PENDING')
                      <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }}">
                        <td>{{ $key++ }}</td>
                        <td>
                          {{ $sharing->requestor->name }}
                        </td>
                        <td>
                          {{ $sharing->updated_at->format('Y-m-d') }}
                        </td>
                        <td>                  
                          <span class="label label-success hover action-approve" organization="{{ $sharing->organization_requestor }}">Approve</span>
                          <span class="label label-danger hover action-decline" organization="{{ $sharing->organization_requestor }}">Decline</span>
                        </td>
                      </tr>
                      @endif
                    @endforeach

                    <!-- Next, show outgoing sharing requests that are still in pending status -->
                    @foreach ($sharing_out as $sharing)
                      @if ($sharing->status == 'PENDING')
                      <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }}">
                        <td>{{ $key++ }}</td>
                        <td>
                          {{ $sharing->owner->name }}
                        </td>
                        <td>
                          {{ $sharing->updated_at->format('Y-m-d') }}
                        </td>
                        <td>                  
                          <span class="label label-default">Pending Approval</span>
                        </td>
                      </tr>
                      @endif
                    @endforeach

                    <!-- Next, show outgoing sharing requests that are declined -->
                    @foreach ($sharing_out as $sharing)
                      @if ($sharing->status == 'DECLINED')
                      <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }}">
                        <td>{{ $key++ }}</td>
                        <td>
                          {{ $sharing->owner->name }}
                        </td>
                        <td>
                          {{ $sharing->updated_at->format('Y-m-d') }}
                        </td>
                        <td>                  
                          <span class="label label-warning">Declined</span>
                          <span class="label label-info hover action-apply" organization="{{ $sharing->organization_owner }}">ReApply</span>
                        </td>
                      </tr>
                      @endif
                    @endforeach

                    <!-- Next, show sharing-available organizations -->
                    @foreach ($organizations_sharing_available as $organization)
                      <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }}">
                        <td>{{ $key++ }}</td>
                        <td>
                          {{ $organization->name }}
                        </td>
                        <td>
                        </td>
                        <td>                  
                          <span class="label label-info hover action-apply" organization="{{ $organization->id }}">Apply</span>
                        </td>
                      </tr>
                    @endforeach

                    <!-- Next, show incoming sharing requests that were declined by you -->                    
                    @foreach ($sharing_in as $sharing)
                      @if ($sharing->status == 'DECLINED')
                      <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }}">
                        <td>{{ $key++ }}</td>
                        <td>
                          {{ $sharing->requestor->name }}
                        </td>
                        <td>
                          {{ $sharing->updated_at->format('Y-m-d') }}
                        </td>
                        <td>                  
                          <span class="label label-warning">Declined by you</span>
                          <span class="label label-info hover action-apply" organization="{{ $sharing->organization_requestor }}">Apply</span>
                        </td>
                      </tr>
                      @endif
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
                      <i class="fa fa-share-alt font-green-sharp"></i>&nbsp;
                      <span class="caption-subject font-green-sharp bold uppercase">Partners sharing Mugshots with you</span>
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
					
                    @foreach ($sharing_approved as $key => $sharing)
                      <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }}">
                        <td>{{ $key + 1 }}</td>
                        <td>
                          @if (auth()->user()->organizationId == $sharing->organization_owner)
                            {{ $sharing->requestor->name }}
                          @elseif (auth()->user()->organizationId == $sharing->organization_requestor)
                            {{ $sharing->owner->name }}
                          @endif
                        </td>
                        <td>
                          {{ $sharing->updated_at->format('Y-m-d') }}
                        </td>
                        <td>
                          @if ($sharing->status === 'ACTIVE')
                            @if (auth()->user()->organizationId == $sharing->organization_owner)
                              <span class="label label-success">Requestor</span>
                            @else
                              <span class="label label-success">Owner</span>
                            @endif
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
@endsection