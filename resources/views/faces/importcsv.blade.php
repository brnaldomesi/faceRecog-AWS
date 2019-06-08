<div class="row">
  <div class="col-xs-12 text-center caption" style="margin-bottom: 15px;">
    <span class="caption-subject font-green-sharp bold uppercase">Import CSV File</span>
  </div>

  <div class="col-xs-offset-1 col-xs-10 text-center">
    <div class="form-group">
      <label class="col-xs-4 control-label text-right" style="padding-top:5px;">CSV File</label>
      <div class="col-xs-8 text-left">
        <div class="fileinput fileinput-new" data-provides="fileinput">
          <span class="btn default btn-file">
          <span class="fileinput-new">Browse</span>
          <span class="fileinput-exists">Browse</span>
          <input type="hidden" value="" name="..."><input type="file" name="csv" id="csvInput" accept=".csv" required>
          </span>
          <span class="fileinput-filename"></span>
          &nbsp; <a href="javascript:;" class="close fileinput-exists" data-dismiss="fileinput">
          </a>
        </div> 
      </div>
    </div>
    
    <div class="form-group">
      <input type="hidden" id="route-face-importcsv" value="{{ route('faces.importcsv') }}" />
      <label class="col-xs-4 control-label text-right" style="padding-top:5px;">Organization </label>
      <div class="col-xs-8" style="max-width:250px;">
        <select class="form-control" name="organizationCSV" id="organizationCSV" value="" required="required">
          <option></option>
          @foreach ($organizations as $org)
          <option value={{$org->id}}>{{$org->name}}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>
  
  <div class="form-group">
    <div class="col-xs-offset-3 col-xs-6" style="text-align: center;">
      <a href="javascript:importCSV();" class="btn green-haze" style="width:100px;">Import</a>
    </div>
  </div>
</div>