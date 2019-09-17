<div class="form-group">
  <input type="hidden" id="route-face-searchimage" value="{{ route('faces.searchimage') }}" />
  <input type="hidden" id="route-face-removeface" value="{{ route('faces.removeface') }}" />
  <div class="col-xs-3 col-sm-4 col-md-4 col-lg-3 text-right">
    <label style="padding-top:7px;">Face Token</label>
  </div>
  <div class="col-xs-8 col-sm-6 col-md-7 col-lg-8 text-center">
    <input class="form-control" id="faceToken" type="text" name="faceToken">
  </div>
</div>

<div class="form-group">
  <div class="col-xs-12">
    <div class="thumbnail" id="imageThumbnail" style="width: 250px; height: 250px; margin:0 auto;">
      <img src="" alt="" id="faceImage" style="max-width: 240px; max-height: 240px;"/>
    </div>
  </div>
</div>

<div class="form-group">
  <div class="col-xs-offset-2 col-xs-8 text-center">
    <a href="javascript:searchFaceImage();" class="btn green-haze" id="btnSearch">Search</a>
    <a href="javascript:discardFaceImage();" class="btn default fileinput-exists" id="btnDiscard" style="display:none;">Discard</a>
    <a href="javascript:removeFace();" class="btn red fileinput-exists" id="btnRemove" style="display:none;">Remove</a>
  </div>
</div>