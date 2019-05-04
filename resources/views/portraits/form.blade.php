<form id="enrollForm" action="/portraits" method="post" enctype="multipart/form-data">
  @csrf
  <div class="row">
    <div class="col-md-12">
      @if (session('status'))
        <div class="alert alert-success" role="alert">
          {{ session('status') }}
        </div>
      @else
        <div class="row bg-white">
          <!--BEGIN PHOTO -->
          <div class="col-md-6 border-right nopadding">
            <div class="portlet light text-center">
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
                  <div class="fileinput-preview fileinput-exists thumbnail" id="portraitDiv" style="width: 300px; height: 300px;">
                  </div>
                  <div class="text-center">
                    <span class="btn default btn-file">
                      <span class="fileinput-new">
                        Upload
                      </span>
                      <span class="fileinput-exists">
                        Upload
                      </span>
                      <input type="file" accept="image/*" name="portraitInput" id="portraitInput">
                    </span>
                    <a href="javascript:;" class="btn default fileinput-exists" hidden="" data-dismiss="fileinput">
                      Remove 
                    </a>
                  </div>
                </div>
              </div>

              <div class="fileinput fileinput-new margin-top-15" data-provides="fileinput">
                <span class="btn default btn-file">
                <span class="fileinput-new">
                CSV </span>
                <span class="fileinput-exists">
                CSV </span>
                <input type="hidden" value="" name="..."><input type="file" name="csv" accept=".csv">
                </span>
                <span class="fileinput-filename"></span>
                &nbsp; <a href="javascript:;" class="close fileinput-exists" data-dismiss="fileinput">
                </a>
              </div>

            </div> 
          </div>
          <!-- END PHOTO -->
          <!-- BEGIN PERSONAL INFO -->
          <div class="col-md-6 nopadding">
            <div class="portlet light">
              <div class="portlet-title">
                <div class="caption">
                  <span class="caption-subject font-green-sharp bold uppercase">{{ __('Personal Infomation') }}</span>
                </div>
              </div>
              <div class="portlet-body">
                <div class="form-group row">
                  <label for="name" class="col-md-3 col-form-label text-right padding-top-10">{{ __('Identifiers') }}</label>
                  <div class="col-md-6">
                    <input id="identifiers" type="text" class="form-control" name="identifiers">
                  </div>
                </div>

                <div class="form-group row">
                  <label for="name" class="col-md-3 col-form-label text-right padding-top-10">{{ __('Gender') }}</label>
                  <div class="col-md-6">
        					<select class="form-control" name="gender">
        						<option value="">Select One</option>
        						<option value="MALE">Male</option>
        						<option value="FEMALE">Female</option>
        					</select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="posBottomRight">
           <a href="javascript:uploadPortrait();" class="btn green-haze">Save</a>
           <!-- <button type="submit">Save</button> -->
         </div>
          <!-- END PERSONAL INFO -->
        </div>
      @endif
    </div>
  </div>
</form>