@extends('layouts.master')

@section('extracss')
	<link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
@endsection

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
              <li class="active">
                Organizations
              </li>
            </ul>
            <div class="row">
              <div class="col-md-12">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light">
                  <div class="portlet-title">
                    <div class="caption col-sm-6">
                      <i class="fa fa-building font-green-sharp"></i>
                      <span class="caption-subject font-green-sharp bold uppercase">Organization List</span>
                    </div>
                    <div class="col-sm-6" style="text-align: right; padding-right: 0px;">
                      <div class="btn-group">
                        <a href="{{ route('organization.new') }}" class="btn green">
                        Add New
                        <i class="fa fa-plus"></i>
                        </a>
                      </div>
                    </div>
<!--                     <div class="tools">
                      <a href="javascript:;" class="collapse">
                      </a>
                      <a href="#portlet-config" data-toggle="modal" class="config">
                      </a>
                      <a href="javascript:;" class="reload">
                      </a>
                      <a href="javascript:;" class="remove">
                      </a>
                    </div> -->
                  </div>
                  <div class="portlet-body">
                    <div class="table-toolbar">
                      <div class="row">
                        <!-- <div class="col-md-6">
                          <div class="btn-group pull-right">
                            <button class="btn dropdown-toggle" data-toggle="dropdown">Tools <i class="fa fa-angle-down"></i>
                            </button>
                            <ul class="dropdown-menu pull-right">
                              <li>
                                <a href="javascript:;">
                                Print </a>
                              </li>
                              <li>
                                <a href="javascript:;">
                                Save as PDF </a>
                              </li>
                              <li>
                                <a href="javascript:;">
                                Export to Excel </a>
                              </li>
                            </ul>
                          </div>
                        </div> -->
                      </div>
                    </div>
                    <table class="table table-striped table-hover" id="table-organization-list">
                      <thead>
                        <tr>
                          <th class="table-checkbox hidden">
                            <input type="checkbox" class="group-checkable" data-set="#table-organization-list .checkboxes"/>
                          </th>
                          <th>
                            Organization
                          </th>
                          <th>
                            Contact Name
                          </th>
                          <th>
                            Contact Email
                          </th>
                          <th>
                            Created
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                      @foreach ($organizations as $key => $o)
                        <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }} gradeX tname">
                          <td class="hidden">
                            <input type="checkbox" class="checkboxes" value="1"/>
                          </td>
                          <td>
                            {{ $o->name }}
                          </td>
                          <td>
                            {{ $admins[$key]->name }}
                          </td>
                          <td>
                            {{ $admins[$key]->email }}
                          </td>
                          <td class="center">
                            {{ $o->created_at->format('m/d/Y') }}
							
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
	<script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/organization/list.js') }}"></script>
  <script src="{{ asset('global/plugins/bootbox/bootbox.min.js') }}" type="text/javascript"></script>
  
  <script>
    @if (\Session::has('isOrgCreated'))
      bootbox.alert('New organization has been created');
    @endif
  </script>
@endsection