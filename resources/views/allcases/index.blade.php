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
        <h1>All Cases</h1>
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
                All Cases
              </li>
            </ul>
            <div class="row">
              <div class="col-md-12">
					 <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light">
                  <div class="portlet-title">
                    <div class="caption">
                      <i class="fa fa-briefcase font-green-sharp"></i>
                      <span class="caption-subject font-green-sharp bold uppercase">Case List</span>
                    </div>
                  </div>
                  <div class="portlet-body">
                    <div class="table-toolbar">
                      <div class="row">
						<div class="col-md-6">
                        </div>

                         <div class="col-md-6">
                          <div class="btn-group pull-right" id="all-report-bar">
                            
                          </div>
                        </div>
						
                      </div>
                    </div>
                    <table class="table table-striped table-hover" id="table-allcase-list">
                    <thead>
                    <tr>
                      <th class="table-checkbox hidden">
                        <input type="checkbox" class="group-checkable" data-set="#table-allcase-list .checkboxes"/>
                      </th>
                      <th>
                         Case #
                      </th>
          					  <th>
          						Organization
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
          					  <tr class="{{ $key % 2 > 0 ? 'odd' : 'even' }} gradeX tname" onClick="javascript:location.href='{{ route('allcases.id.show', ['org'=>$c->organization->id, 'id' => $c->id]) }}';">
          						<td class="hidden">
          						  <input type="checkbox" class="checkboxes" value="1"/>
          						</td>
                      <td>
                        {{ $c->caseNumber }}
                      </td>
      								<td>
      								  {{ $c->organization->name }}
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
          						<td class="center" data-sort="{{ $c->created_at->format('Ymd H:i') }}">
          						   {{ $c->created_at->format('n/d/y') }}
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
  
 <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/v/bs/jqc-1.12.4/jszip-2.5.0/dt-1.10.20/b-1.6.0/b-html5-1.6.0/b-print-1.6.0/datatables.min.js"></script>
  
  <script type="text/javascript" src="{{ asset('js/Cases/caselist.js') }}"></script>
@endsection