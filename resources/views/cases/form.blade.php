<div class="form-group {{ $errors->has('caseNumber') ? ' has-error' : ''}}">
    {!! Form::label('caseNumber', 'Case # ', ['class' => 'col-md-4 control-label']) !!}
    <div class="col-md-6">
        {!! Form::text('caseNumber', null, ['class' => 'form-control', 'required' => 'required']) !!}
        {!! $errors->first('caseNumber', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('type') ? ' has-error' : ''}}">
    {!! Form::label('type', 'Type ', ['class' => 'col-md-4 control-label']) !!}
    <div class="col-md-6">
        {!! Form::text('type', null, ['class' => 'form-control', 'required' => 'required']) !!}
        {!! $errors->first('type', '<p class="help-block">:message</p>') !!}
    </div>
</div>

<div class="form-group">
    <div class="col-md-offset-3 col-md-6" style="text-align: center;">
        {!! Form::submit('Create', ['class' => 'btn btn-primary']) !!}
        <a href="{{ route('cases.show') }}" class="btn btn-default">
            Cancel
        </a>
    </div>
</div>
