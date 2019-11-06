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
        <h1>Help & Support</h1>
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
                Help & Support
              </li>
            </ul>
            <div class="row">
              <div class="col-md-12">
			  
				<h3>For technical support, please email us at <a href="mailto:support@afrengine.com?subject=Support Question">support@afrengine.com</a></h3>
				<br>
				
				<!--Chat button will appear here-->
				<div id="MyLiveChatContainer"></div>

				<!--Add the following script at the bottom of the web page (before </body></html>)-->
				<script type="text/javascript">function add_chatbutton(){var hccid=89042608;var nt=document.createElement("script");nt.async=true;nt.src="https://mylivechat.com/chatbutton.aspx?hccid="+hccid;var ct=document.getElementsByTagName("script")[0];ct.parentNode.insertBefore(nt,ct);}
				add_chatbutton();</script>
				</div>
			</div>
            
			<div class="row">
              <div class="col-md-12">

				<h2>Frequently Asked Questions</h2><br>
				
				<ul>
					<li><a href="#faq_001">How does AFR Engine work?</a></li>
					<li><a href="#faq_002">I get the error "No Face Detected" when adding a Case Image</a></li>
				</ul>
				
				<hr><br>
				
				<h4 id="faq_001"><b>How does AFR Engine work?</b></h4>
				<p>AFR Engine is a search engine that searches faces.  There are two components that make up the data that AFR Engine uses.  The first component is the stored database.  That database is created from the department's arrest records as well as arrest records from other participating law enforcement agencies.  The second component is the photograph or video containing the person that needs to be identified.  The submitted face is compared to the arrest record database and results are returned by statistical ranking called "Similarity."  The Similarity score is presented to the user for informational purposes with the highest-ranking result at the top of the list.  The user then examines the result set to see if they can make a match.  The user looks for distinguishing features in both the results and submitted photo that match such as the ears, size of the nose, or distinctive markings.  If the user determines they have found a match, that investigative lead is shared within the agency and the investigator can take the appropriate actions in accordance to the law.</p>
				
				<h4 id="faq_002"><b>I get the error "No Face Detected" when adding a Case Image</b></h4>
				<p>If you tried to upload an image of a suspect into one of your cases and saw an error that said "No Face Detected", that means the image quality was too poor to be used with facial recognition.  The minimum size image is 80x80 pixels however we recommend you use a screenshot from the original video source.  The higher quality image the more accurate your search results will be.</p>
				
				<h2>Tutorial Videos</h2>
				<br>
				
				<h3>How to crop an image for your cases</h3>
				<br>
				<iframe src="https://player.vimeo.com/video/336662042" width="640" height="349" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>

				<h3>How to create a new case and add an image</h3>
				<br>
				<iframe src="https://player.vimeo.com/video/336670409" width="640" height="349" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
				
				<h3>How to perform a case search</h3>
				<br>
				<iframe src="https://player.vimeo.com/video/336675379" width="640" height="349" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
				
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
@endsection