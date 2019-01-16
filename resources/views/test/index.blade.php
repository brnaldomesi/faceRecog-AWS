@extends('layouts.master')

@section('extracss')
	<link href="{{ asset('global/plugins/select2/select2.css') }}" rel="stylesheet">
	<link href="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css') }}" rel="stylesheet">
	<link href="{{ asset('global/plugins/jquery-file-upload/blueimp-gallery/blueimp-gallery.min.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('global/plugins/jquery-file-upload/css/jquery.fileupload.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('global/plugins/fancybox/source/jquery.fancybox.css') }}" rel="stylesheet" type="text/css"/>
	<link href="{{ asset('global/plugins/jquery-file-upload/css/jquery.fileupload-ui.css') }}" rel="stylesheet" type="text/css" />

@endsection

@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
    <div class="page-head">
        <div class="container">
        <!-- BEGIN PAGE TITLE -->
        <div class="page-title">
            <h1>AWS Testing</h1>
        </div>
        <!-- END PAGE TITLE -->
        </div>
    </div>
    <!-- END PAGE HEAD -->
    
    <div class="page-content">
        <div class="container">
            <ul class="page-breadcrumb breadcrumb">
                <li>
                    <a href="{{ route('root') }}">Home</a><i class="fa fa-circle"></i>
                </li>
                <li>
                    <a href="{{ route('aws.test') }}">AWS Testing.</a><i class="fa fa-circle"></i>
                </li>
                <li class="active">
                    
                </li>
            </ul>

            <!-- testing page begin -->

            <p>Collection List</p>
            @foreach ($collections as $key => $value)
                <div>
                    <span>{{$key.' => '}} {{$value}}</span>
                </div>

            @endforeach

            <p></p>

            <form method="post">    
                <div class="row">
                    <div class="col-md-12">
                        <label>
                            Collection Name:
                            <input type="text" id="id_collection_name"  class="form-control input-inline" value="maricopacountyjail_male" readonly>
                        </label>
                    </div>
                </div>
                <input  type="button" id="id_btn_create_collection" disabled class="btn btn-success" value="create a collection">    
                
            </form>

            <br/><br/>

            <p>Face List from the Collection of "maricopacountyjail_male_1547499969"</p>
            @foreach ($faces as $key => $value)
                <div class="row">
                    <div class="col-md-5">
                        <span>{{$key.' => '}} {{'FaceId: '.$value['FaceId']}}</span>
                    </div>
                    <div class="col-md-7">
                        {{"ImageId: ".$value['ImageId']}}
                    </div>
                    <div class="col-md-12">
                        {{'ExternalImageId: '. str_replace('https/','https:',str_replace(":","/",$value['ExternalImageId']))}}
                    </div>
                    
                </div>

            @endforeach

            <p></p>


            <!-- images Indexing. -->

            <form method="post" style="margin-top:20px;">
                
                <div class="row">
                    <div class="col-md-12">
                        <label>
                            Collection ID:
                            <input type="text" id="id_collection_name"  class="form-control input-inline" value="maricopacountyjail_male_1547499969" size="100" readonly>
                        </label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label>
                            S3 Soruce: Key Name
                            <input type="text" id="id_txt_key_name"  class="form-control input-inline" value="" size="100" >
                        </label>
                    </div>
                </div>
                <input type="button" id="id_btn_indexing_face" class="btn btn-success" value="Face Indexing From S3 source.">
                
            </form>


            <!-- delete face. -->
            <form method="post" style="margin-top:20px;">
                <div class="row">
                    <div class="col-md-12">
                        <label>
                            Delete Face From this Collection:
                            <input type="text" id="id_txt_del_collection_id"  class="form-control input-inline" value="maricopacountyjail_male_1547499969" size="100" readonly>
                        </label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label>
                            Face ID:
                            <input type="text" id="id_txt_del_face_id"  class="form-control input-inline" value="" size="100" >
                        </label>
                    </div>
                </div>

                <input type="button" id="id_btn_delete_face" class="btn btn-success" value="Delete Face from Collection">
            </form>




            <!-- Face Search. -->
            <form method="post" style="margin-top:20px;">
                <div class="row">
                    <div class="col-md-12">
                        <label>
                            Search Image:
                            <input type="text" id="id_collection_name"  class="form-control input-inline" value="https://s3-us-west-2.amazonaws.com/afrengine-images/storage/face/maricopacountyjail/MALE/00d826f9134f01668937143d2a0473ef/12929c30f0ec7a40c06afd647427a5b6.jpg" size="100" readonly>
                        </label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-5">
                        <label>
                            Search Image:
                            <img width="200px" height="auto" src="https://s3-us-west-2.amazonaws.com/afrengine-images/storage/face/maricopacountyjail/MALE/00d826f9134f01668937143d2a0473ef/12929c30f0ec7a40c06afd647427a5b6.jpg">
                        </label>
                    </div>
                    <div class="col-md-1">
                        Search Result => 
                    </div>
                    <div class="col-md-6">
                        <div class="row" id="id_dv_search_result"></div>
                    </div>
                </div>

                
                <input type="button" id="id_btn_search_face" class="btn btn-success" value="Search this image From aws Rekoginition.">
            </form>
            <!-- testing page end. -->
        

        </p>
    </div>
</div>
@endsection

@section('extrajs')

<!-- The blueimp Gallery widget -->


<!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->



<script type="text/javascript" src="{{ asset('global/plugins/select2/select2.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('global/plugins/datatables/media/js/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') }}"></script>
<script type="text/javascript" src="{{ asset('admin_assets/pages/scripts/table-managed.js') }}"></script>

<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/jquery.ui.widget.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/tmpl.min.js') }}"" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/load-image.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/vendor/canvas-to-blob.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/blueimp-gallery/jquery.blueimp-gallery.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.iframe-transport.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-process.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-image.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-audio.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-video.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-validate.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/jquery-file-upload/js/jquery.fileupload-ui.js') }}" type="text/javascript"></script>
<script src="{{ asset('global/plugins/fancybox/source/jquery.fancybox.pack.js') }}" type="text/javascript"></script>


<script src="{{ asset('admin_assets/pages/scripts/form-fileupload.js') }}" type="text/javascript"></script>
<script type="text/javascript" src="{{ asset('js/test.js') }}"></script>
@endsection