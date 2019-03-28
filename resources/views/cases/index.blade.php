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
        <h1>Cases</h1>
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
                Cases
              </li>
            </ul>
            <div class="row">
              <div class="col-md-12">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light">
                  <div class="portlet-title">
                    <div class="caption">
                      <i class="fa fa-cogs font-green-sharp"></i>
                      <span class="caption-subject font-green-sharp bold uppercase">Case List</span>
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
                        <div class="col-md-6">
                          <div class="btn-group">
                            <a href="{{ route('cases.create.show') }}" class="btn green">
                            Add New
                            <i class="fa fa-plus"></i>
                            </a>
                          </div>
                        </div>
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
                    <table class="table table-striped table-hover" id="table-case-list">
                    <thead>
                    <tr>
                      <th class="table-checkbox hidden">
                        <input type="checkbox" class="group-checkable" data-set="#table-case-list .checkboxes"/>
                      </th>
                      <th>
                         Case #
                      </th>
                      <th>
                         Type
                      </th>
                      <th>
                         Status
                      </th>
                      <th>
                         Created
                      </th>

                    </tr>
                    </thead>
                    <tbody>
@foreach ($cases as $key => $c)
					  <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }} gradeX tname" onClick="javascript:location.href='{{ route('cases.id.show', ['id' => $c->id]) }}';">
						<td class="hidden">
						  <input type="checkbox" class="checkboxes" value="1"/>
						</td>
						<td>
						  {{ $c->caseNumber }}
						</td>
						<td>
						  {{ $c->type }}
						</td>
						<td>
@switch ($c->status)
	  @case ('ACTIVE')
						   	<span class="label label-sm label-danger">
							  Active
							</span>
		@break

	  @case ('CLOSED')
							<span class="label label-sm label-warning">
							  CLOSED
							</span>
		@break

	  @case ('SOLVED')
  							<span class="label label-sm label-success">
							  SOLVED
							</span>
		@break
@endswitch

						</td>
						<td class="center">
						   {{ $c->created_at }}
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
  <script type="text/javascript" src="{{ asset('js/Cases/caselist.js') }}"></script>
@endsection