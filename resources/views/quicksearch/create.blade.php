<div class="row">
  <div class="col-md-12">
    <div class="form-group text-center">
      <div class="row text-center" style="display:none;" id="resultProcessButtons">
        <a href="javascript:saveCompareResult();" class="btn btn-primary" style="width:115px; text-align:center;">Save Result</a>
      </div>
    </div>

    <div class="form-group">
      <!-- BEGIN FIRST PHOTO -->
      <div class="col-md-12 nopadding">
        <div class="form-group text-center">
          <div class="caption" style="margin-bottom:5px;">
            <span class="caption-subject font-green-sharp bold uppercase">{{ __('IMAGE') }}</span>
          </div>
          <div class="row">
            <div class="fileinput fileinput-new" data-provides="fileinput">
              <div class="fileinput-new thumbnail" style="width: 210px; height: 210px; margin:0 auto;">
                <img src="" alt=""/>
              </div>
			  
			  <div class="fileinput-preview fileinput-exists thumbnail" id="portraitDiv1" name="portraitDiv1" style="width: 210px; height: 210px; margin:0 auto;">
              </div>
		
			  <div>
				  <label for="name" class="col-form-label text-right" style="padding-top:5px;">{{ __('Gender:')}}</label>

				  <select name="gender" class="form-control" id="gender" style="margin:5px; width:130px;">
						<option></option>
						<option value="MALE">Male</option>
						<option value="FEMALE">Female</option>
					</select>
					<strong class="error text-white label label-danger" style="margin:5px;"></strong>
			 </div>
			 
			 <div>
				<label for="name" class="col-form-label text-right" style="padding-top:5px;">{{ __('Reference #:')}}</label>
				<input type="text" class="form-control" name="reference" id="reference" style="margin:5px; width:130px;">
			 </div>

              <div class="text-center" style="margin-top:5px;">
                <span class="btn default btn-file">
                  <span class="fileinput-new">
                    Select Image
                  </span>
                  <span class="fileinput-exists">
                    Select Image
                  </span>
                  <input type="file" accept="image/jpeg, image/png" name="portraitInput1" id="portraitInput1">
                </span>
                <a href="javascript:;" class="btn default fileinput-exists" hidden="" data-dismiss="fileinput">
                  Discard 
                </a>
              </div>
            </div>
          </div>
		  <div class="row">
		  
		  </div>
        </div> 
      </div>
      <!-- END FIRST PHOTO -->
    </div>

    <div class="form-group text-center">
      <a href="javascript:quickSearch();" class="btn green-haze" id="quickSearchButton" style="width:110px;">Search</a>
    </div>

  </div>
</div>