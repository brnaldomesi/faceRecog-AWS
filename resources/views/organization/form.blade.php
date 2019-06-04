<div class="form-group {{ $errors->has('name') ? ' has-error' : ''}}">
    {!! Form::label('name', 'Orgzaization name ', ['class' => 'col-md-4 control-label']) !!}
    <div class="col-md-6">
        {!! Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) !!}
        {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('name') ? ' has-error' : ''}}">
    {!! Form::label('account', 'Account ', ['class' => 'col-md-4 control-label']) !!}
    <div class="col-md-6">
        {!! Form::text('account', null, ['class' => 'form-control', 'required' => 'required']) !!}
        {!! $errors->first('account', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('adminName') ? ' has-error' : ''}}">
    {!! Form::label('adminName', 'Administrator ', ['class' => 'col-md-4 control-label']) !!}
    <div class="col-md-6">
        {!! Form::text('adminName', null, ['class' => 'form-control', 'required' => 'required']) !!}
        {!! $errors->first('adminName', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('email') ? ' has-error' : ''}}">
 {!! Form::label('email', 'E-Mail ', ['class' => 'col-md-4 control-label']) !!}
  <div class="col-md-6">
    {!! Form::email('email', null, ['class' => 'form-control', 'required' => 'required']) !!}
    {!! $errors->first('email', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group {{ $errors->has('contactPhone') ? ' has-error' : ''}}">
    {!! Form::label('contactPhone', 'Phone ', ['class' => 'col-md-4 control-label']) !!}
    <div class="col-md-6">
        {!! Form::text('contactPhone', null, ['class' => 'form-control', 'required' => 'required']) !!}
        {!! $errors->first('contactPhone', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('password') ? ' has-error' : ''}}">
  {!! Form::label('password', 'Password ', ['class' => 'col-md-4 control-label']) !!}
  <div class="col-md-6">
    {!! Form::password('password', ['class' => 'form-control', 'placeholder' => 'Setup organization password here...']) !!}
    {!! $errors->first('password', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group">
  {!! Form::label('password_confirmation', 'Confirm ', ['class' => 'col-md-4 control-label']) !!}
  <div class="col-md-6">
    {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
  </div>
</div>

<div class="form-group">
    <div class="col-md-offset-3 col-md-6" style="text-align: center;">
        {!! Form::submit('Create', ['class' => 'btn green-haze']) !!}
        <a href="{{ route('organization') }}" class="btn btn-default">
            Cancel
        </a>
    </div>
</div>
