@extends('layouts.master')

@section('extracss')
	<link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
  <link href="{{ asset('global/plugins/bootstrap-datepicker/css/datepicker3.css') }}" rel="stylesheet">
  <link href="{{ asset('global/plugins/select2/select2.css') }}" rel="stylesheet">
  <link href="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.css') }}" rel="stylesheet">
  <link href="{{ asset('css/enroll.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
  <div class="page-head">
    <div class="container">
      <!-- BEGIN PAGE TITLE -->
      <div class="page-title">
        <h1>Enroll</h1>
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
                Enroll
              </li>
            </ul>
            <div class="row">
              <!-- Enroll Panel -->
              <div class="col-md-12">
                <div class="portlet light">
                  <div class="portlet-title">
                    <div class="caption">
                      <i class="fa fa-user-plus font-green-sharp"></i>
                      <span class="caption-subject font-green-sharp bold uppercase">Enroll</span>
                    </div>
                  </div>

                  <div class="portlet-body">
                    <div class="tab-content">
                      <input type="hidden" id="route-enroll" value="{{ route('enroll.enroll') }}" />
                      <!-- From Storage Panel -->
                      <div class="tab-pane active" id="portlet_fromstorage">
                        {!! Form::open(['id' => 'fromStorageForm', 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
                        <div class="row">
                          <div class="col-sm-6 text-center" style="margin-bottom:40px;">
                            <div class="row" style="margin:15px;">
                              <div class="col-sm-12 caption text-center">
                                <span class="caption-subject font-green-sharp bold uppercase">Import / Take a photo</span>
                              </div>
                            </div>
                            <div class="fileinput fileinput-new" data-provides="fileinput">
                              <div class="fileinput-new thumbnail" style="width: 250px; height: 250px;">
                                <img src="" alt=""/>
                              </div>
                              <div class="fileinput-preview fileinput-exists thumbnail" id="portraitDiv" style="width: 250px; height: 250px;">
                              </div>
                              <div class="text-center">
                                <span class="btn default btn-file">
                                  <span class="fileinput-new">Pickup</span>
                                  <span class="fileinput-exists">Pickup</span>
                                  <input type="file" accept="image/jpeg, image/png" name="portraitInput" id="portraitInput">
                                </span>
                                <a href="javascript:;" class="btn default fileinput-exists" hidden="" data-dismiss="fileinput">
                                  Discard
                                </a>
                              </div>
                            </div>
                          </div>

                          <div class="col-sm-5">
                            <div class="row" style="margin:15px;">
                              <div class="col-sm-offset-2 caption text-center">
                                <span class="caption-subject font-green-sharp bold uppercase">Personal Information</span>
                              </div>
                            </div>

                            <div class="row" style="margin-bottom:10px;">
                              <label for="name" class="col-sm-3 col-form-label text-right" style="padding-top:5px;">{{ __('Name') }}</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" id="fromstorage_name" name="fromstorage_name">
                              </div>
                            </div>

                            <div class="row" style="margin-bottom:10px;">
                              <label for="name" class="col-sm-3 col-form-label text-right" style="padding-top:5px;">{{ __('DOB') }}</label>
                              <div class="input-group date col-sm-9" data-date-format="mm/dd/yyyy" style="padding:0px 15px 0px 15px !important;">
                                <input type="text" class="form-control text" placeholder="mm/dd/yyyy" id="fromstorage_dob" name="fromstorage_dob">
                                <span class="input-group-addon">
                                  <span class="fa fa-calendar">
                                  </span>
                                </span>
                              </div>
                            </div>

                            <div class="row" style="margin-bottom:10px;">
                              <label for="name" class="col-sm-3 col-form-label text-right" style="padding-top:5px;">{{ __('Gender') }}</label>
                              <div class="col-sm-9">
                              <select class="form-control" id="fromstorage_gender" name="fromstorage_gender">
                                <option></option>
                                <option value="MALE">Male</option>
                                <option value="FEMALE">Female</option>
                              </select>
                              </div>  
                            </div>
                            
                            <div class="row" style="margin-bottom:10px;">
                              <div class="col-sm-offset-2 text-center" style="margin-top:10px;">
                                <a href="javascript:enrollFromStorage();" class="btn green-haze" style="width:100px;">Enroll</a>
                              </div>
                            </div>
                          </div>
                        </div>
                        {!! Form::close() !!}
                      </div>
                      
                      <!-- From Camera Panel -->
                      <!-- Not being used now-->
                      <!-- <div class="tab-pane" id="portlet_fromcamera">
                        {!! Form::open(['id' => 'fromCameraForm', 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
                        <div class="row">
                          <input type="hidden" id="portraitCamera" name="portraitCamera">
                          <div class="col-sm-6 text-center" style="margin-bottom:40px;">
                            <div class="row" style="margin:15px;">
                              <div class="col-sm-12 caption text-center">
                                <span class="caption-subject font-green-sharp bold uppercase">Take a photo</span>
                              </div>
                            </div>
                          
                            <div id="container" style="max-width:320px; margin: 0px auto;">
                              <div id="vid_container">
                                <video id="video" autoplay playsinline></video>
                                <div id="video_overlay"></div>
                                <img id="imageSnapshot">
                              </div>
                              <div id="gui_controls">
                                <button class="ui-btn" id="switchCameraButton" name="switch Camera" type="button" aria-pressed="false"></button>
                                <button class="ui-btn" id="takePhotoButton" name="take Photo" type="button"></button>
                                <button class="ui-btn" id="retakePhotoButton" name="retake Photo" type="button"></button>
                              </div>
                            </div> 
                            
                          </div>

                          <div class="col-sm-5" id="storagePanel">
                            <div class="row" style="margin:15px;">
                              <div class="col-sm-offset-2 caption text-center">
                                <span class="caption-subject font-green-sharp bold uppercase">Personal Information</span>
                              </div>
                            </div>

                            <div class="row" style="margin-bottom:10px;">
                              <label for="name" class="col-sm-3 col-form-label text-right" style="padding-top:5px;">{{ __('Name') }}</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" id="fromcamera_name" name="fromcamera_name">
                              </div>
                            </div>

                            <div class="row" style="margin-bottom:10px;">
                              <label for="name" class="col-sm-3 col-form-label text-right" style="padding-top:5px;">{{ __('DOB') }}</label>
                              <div class="input-group date col-sm-9" data-date-format="dd/mm/yyyy" style="padding:0px 15px 0px 15px !important;">
                                <input type="text" class="form-control text" placeholder="dd/mm/yyyy" id="fromcamera_dob" name="fromcamera_dob">
                                <span class="input-group-addon">
                                  <span class="fa fa-calendar">
                                  </span>
                                </span>
                              </div>
                            </div>

                            <div class="row" style="margin-bottom:10px;">
                              <label for="name" class="col-sm-3 col-form-label text-right" style="padding-top:5px;">{{ __('Gender') }}</label>
                              <div class="col-sm-9">
                              <select class="form-control" id="fromcamera_gender" name="fromcamera_gender">
                                <option></option>
                                <option value="MALE">Male</option>
                                <option value="FEMALE">Female</option>
                              </select>
                              </div>  
                            </div>
                           
                            <div class="row" style="margin-bottom:10px;">
                              <div class="col-sm-offset-2 text-center" style="margin-top:10px;">
                                <a href="javascript:enrollFromCamera();" class="btn green-haze" style="width:100px;">Enroll</a>
                              </div>
                            </div>
                          </div>
                        </div>
                        {!! Form::close() !!}
                      </div> -->
                    </div>
                  </div>
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
  <script type="text/javascript" src="{{ asset('global/plugins/bootbox/bootbox.min.js') }}"></script>
	<script type="text/javascript" src="{{ asset('global/plugins/select2/select2.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/bootstrap-fileinput/bootstrap-fileinput.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/fileValidation.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/enroll/DetectRTC.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/enroll/adapter.min.js') }}"></script>  
  <script type="text/javascript" src="{{ asset('js/enroll/screenfull.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/enroll/howler.core.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/enroll/enroll.js') }}"></script>
@endsection