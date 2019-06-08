<div class="row">
  <div class="col-sm-5 text-center" style="margin-bottom:30px;">
    <div class="fileinput fileinput-new" data-provides="fileinput">
      <div class="fileinput-new thumbnail" style="width: 170px; height: 170px;">
        <img src="" alt=""/>
      </div>
      <div class="fileinput-preview fileinput-exists thumbnail" id="portraitDiv" style="width: 170px; height: 170px;">
      </div>
      <div class="text-center">
        <span class="btn default btn-file">
          <span class="fileinput-new">Browse</span>
          <span class="fileinput-exists">Browse</span>
          <input type="file" accept="image/jpeg, image/png" name="portraitInput" id="portraitInput">
        </span>
        <a href="javascript:;" class="btn default fileinput-exists" hidden="" data-dismiss="fileinput">
          Discard
        </a>
      </div>
    </div>
  </div>

  <div class="col-sm-7">
    <div class="caption text-center" style="margin-bottom:15px;">
      <span class="caption-subject font-green-sharp bold uppercase">Personal Information</span>
    </div>

    <div class="form-group" style="margin-top:15px;">
      <label for="name" class="col-sm-4 col-form-label text-right" style="padding-top:5px;">{{ __('Identifiers') }}</label>
      <div class="col-sm-8">
        <input id="identifiers" type="text" class="form-control" name="identifiers">
      </div>
    </div>

    <div class="form-group">
      <label for="name" class="col-sm-4 col-form-label text-right" style="padding-top:5px;">{{ __('Gender') }}</label>
      <div class="col-sm-8">
      <select class="form-control" name="gender" id="gender">
        <option></option>
        <option value="MALE">Male</option>
        <option value="FEMALE">Female</option>
      </select>
      </div>
    </div>

    <div class="form-group">
      <input type="hidden" id="route-face-enrollphoto" value="{{ route('faces.enrollphoto') }}" />
      <label class="col-sm-4 col-form-label text-right" style="padding-top:5px;">Organization </label>
      <div class="col-sm-8">
        <select class="form-control" name="organizationPhoto" id="organizationPhoto" value="" required="required">
          <option></option>
          @foreach ($organizations as $org)
          <option value={{$org->id}}>{{$org->name}}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="form-group">
      <div class="col-xs-12 text-center" style="margin-top:10px;">
        <a href="javascript:enrollPhoto();" class="btn green-haze" style="width:100px;">Enroll</a>
      </div>
    </div>
  </div>
</div>
