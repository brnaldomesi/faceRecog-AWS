@extends('layouts.master')

@section('extracss')
	<link href="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('global/plugins/fancybox/source/jquery.fancybox.css') }}">
  <link rel="stylesheet" href="{{ asset('global/plugins/select2/select2.css') }}">
@endsection

@section('content')
<form id="searchForm" action="/portraits/search" method="post" enctype="multipart/form-data">
	<div class="page-container">
	  <!-- BEGIN PAGE HEAD -->
	  <div class="page-head">
	    <div class="container">
	      <!-- BEGIN PAGE TITLE -->
	      <div class="page-title">
	        <h1>Search</h1>
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
	                <a href="{{ url('/') }}">Home</a><i class="fa fa-circle"></i>
	              </li>
	              <li class="active">
	                Search
	              </li>
	            </ul>
	            <!-- END PAGE BREADCRUMB -->
	            <!-- BEGIN PAGE CONTENT INNER -->
	            <div class="row bg-white">
								<!-- BEGIN PHOTO -->
								<div class="col-md-6 border-right">
									<div class="portlet light">
								    <div class="portlet-title">
								      <div class="caption">
								        <span class="caption-subject font-green-sharp bold uppercase">{{ __('Photo') }}</span>
								      </div>
								    </div>
								    <div class="portlet-body text-center">
								      <div class="fileinput fileinput-new" data-provides="fileinput">
								        <div class="fileinput-new thumbnail" style="width: 300px; height: 300px;">
								          <img src="" alt=""/>
								        </div>
								        <div class="fileinput-preview fileinput-exists thumbnail" id="searchPortraitDiv" style="width: 300px; height: 300px;">
								        </div>
								        <div class="text-center">
								          <span class="btn default btn-file w-25">
								            <span class="fileinput-new">
								              Pick
								            </span>
								            <span class="fileinput-exists">
								              Pick
								            </span>
								            <input type="file" name="searchPortraitInput" accept="image/*">
								          </span>
								          <a href="javascript:;" class="btn default fileinput-exists" hidden="" data-dismiss="fileinput">
								            Remove 
								          </a>
								          <div class="posBottomRight">
								            <a href="javascript:search();" class="btn green-haze">
								            	Search
								            </a>
							          	</div>
								        </div>
								      </div>
								    </div>
								  </div>
								  
								</div>
								<!-- END PHOTO -->
								<!-- BEGIN TABLE -->
								<div class="col-md-6">
									<div class="portlet light">
										<!-- BEGIN FACESET SELECT -->
										<div>
											<div class="form-group row d-inline-block">
									      <select class="form-control select2me input-small" id="facesetSelect" data-placeholder="Select Faceset" required>
									        <option value=""></option>
									        @for ($i = 0; $i < count($faceSets); $i++)
								            <option value="{{ $faceSets[$i]->id }}">{{ __('Faceset-') . ($i + 1) }}</option>
									        @endfor
									      </select>
									    </div>
									    <div class=" d-inline-block float-right">
									    	<label id="filteredCountLabel"></label>
									    </div>
									  </div>
								    <!-- END FACESET SELECT -->
										<!-- BEGIN TABLE CONTENT -->
										<div class="table-responsive">
											<table class="table table-striped table-bordered table-advance table-hover" id="searchResultTable">
												<thead>
													<tr>
														<th>
															Photo
														</th>
														<th>
															Confidence
														</th>
														<th>
															Name
														</th>
														<th>
															Birthday
														</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td class="text-center" colspan="4">Search results</td>
													</tr>
												</tbody>
												
											</table>
										</div>
										<!-- END TABLE CONTENT -->
										<!-- BEGIN PAGINIATION -->
										<!-- <div class="margin-top-20">
											<ul class="pagination pagination-circle">
												<li>
													<a href="javascript:;">
													Prev </a>
												</li>
												<li>
													<a href="javascript:;">
													1 </a>
												</li>
												<li>
													<a href="javascript:;">
													2 </a>
												</li>
												<li class="active">
													<a href="javascript:;">
													3 </a>
												</li>
												<li>
													<a href="javascript:;">
													4 </a>
												</li>
												<li>
													<a href="javascript:;">
													5 </a>
												</li>
												<li>
													<a href="javascript:;">
													Next </a>
												</li>
											</ul>
										</div> -->
										<!-- END PAGINATION -->
									</div>
								</div>
								<!-- END TABLE -->
							</div>
							<!-- END PAGE CONTENT INNER -->
	            <!-- </div>
	          </div>
	        </div>
	      </div> -->
	    </div>
	  </div>
	</div>
</form>
@endsection

@section('extrajs')
	<script type="text/javascript" src="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.js') }}"></script>
	<script type="text/javascript" src="{{ asset('global/plugins/select2/select2.min.js') }}"></script>
	<script type="text/javascript" src="{{ asset('global/plugins/fancybox/source/jquery.fancybox.pack.js') }}"></script>
	<script type="text/javascript" src="{{ asset('js/portraits.js') }}"></script>
	<script type="text/javascript" src="{{ asset('js/portraits/search.js') }}"></script>
@endsection