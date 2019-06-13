<div class="row">
  <div class="col-md-12">
    <div class="form-group text-center">
      <div class="caption">
        <span class="caption-subject font-red bold uppercase" style="display:none;" id="compareResultCaption">{{ __('Similarity between these faces is: ') }}</span>
      </div>
      <div class="caption" style="display:none;" id="compareResultValue">
        <div class="row" style="margin-bottom:5px;">
        </div>
        <span class="caption-subject font-red bold uppercase" style="font-size: 24px;" id="similarityValue"></span>
        <div class="row" style="margin-bottom:5px;">
        </div>
      </div>
      <div class="row text-center" style="display:none;" id="resultProcessButtons">
        <a href="javascript:discardCompareResult();" class="btn btn-danger" style="width:115px; text-align:center;">Discard Result</a>
        <a href="javascript:saveCompareResult();" class="btn btn-primary" style="width:115px; text-align:center;">Save Result</a>
      </div>
    </div>

    <div class="form-group">
      <!-- BEGIN FIRST PHOTO -->
      <div class="col-lg-6 nopadding">
        <div class="form-group text-center">
          <div class="caption" style="margin-bottom:5px;">
            <span class="caption-subject font-green-sharp bold uppercase">{{ __('Photo1') }}</span>
          </div>
          <div class="row">
            <div class="fileinput fileinput-new" data-provides="fileinput">
              <div class="fileinput-new thumbnail" style="width: 210px; height: 210px; margin:0 auto;">
                <img src="" alt=""/>
              </div>
              <div class="fileinput-preview fileinput-exists thumbnail" id="portraitDiv1" style="width: 210px; height: 210px; margin:0 auto;">
              </div>
              <div class="text-center" style="margin-top:5px;">
                <span class="btn default btn-file">
                  <span class="fileinput-new">
                    Browse
                  </span>
                  <span class="fileinput-exists">
                    Browse
                  </span>
                  <input type="file" accept="image/jpeg, image/png" name="portraitInput1" id="portraitInput1">
                </span>
                <a href="javascript:;" class="btn default fileinput-exists" hidden="" data-dismiss="fileinput">
                  Discard 
                </a>
              </div>
            </div>
          </div>
        </div> 
      </div>
      <!-- END FIRST PHOTO -->
      <!-- BEGIN SECOND PHOTO -->
      <div class="col-lg-6 nopadding">
      <div class="form-group text-center">
          <div class="caption" style="margin-bottom:5px;">
            <span class="caption-subject font-green-sharp bold uppercase">{{ __('Photo2') }}</span>
          </div>
          <div class="row">
            <div class="fileinput fileinput-new" data-provides="fileinput">
              <div class="fileinput-new thumbnail" style="width: 210px; height: 210px; margin:0 auto;">
                <img src="" alt=""/>
              </div>
              <div class="fileinput-preview fileinput-exists thumbnail" id="portraitDiv2" style="width: 210px; height: 210px; margin:0 auto;">
              </div>
              <div class="text-center" style="margin-top:5px;">
                <span class="btn default btn-file">
                  <span class="fileinput-new">
                    Browse
                  </span>
                  <span class="fileinput-exists">
                    Browse
                  </span>
                  <input type="file" accept="image/jpeg, image/png" name="portraitInput2" id="portraitInput2">
                </span>
                <a href="javascript:;" class="btn default fileinput-exists" hidden="" data-dismiss="fileinput">
                  Discard 
                </a>
              </div>
            </div>
          </div>
        </div> 
      </div>
      <!-- END SECOND PHOTO -->
    </div>

    <div class="form-group text-center">
      <a href="javascript:compareFaces();" class="btn green-haze" id="compareButton" style="width:110px;">Compare</a>
    </div>

  </div>
</div>