@extends('layouts.master')

@section('title')
ログイン
@stop

@section('content')
  <div class="row">
    <div class="col-md-6 col-md-offset-3">
      <div class="well">
        {!! Form::open(['url' => 'user/login', 'class' => 'form-horizontal']) !!}
          <fieldset>
            <div class="form-group">
              {!! Form::label('email', 'メールアドレス', ['class' => 'col-md-4 control-label']) !!}
              <div class="col-md-8">
                {!! Form::email('email', null, ['class' => 'form-control', 'autofocus']) !!}
              </div>
            </div>
            <div class="form-group">
              {!! Form::label('password', 'パスワード', ['class' => 'col-md-4 control-label']) !!}
              <div class="col-md-8">
                {!! Form::password('password', ['class' => 'form-control']) !!}
              </div>
            </div>
            <div class="form-group form-group-adjust">
              <div class="col-md-3 col-md-offset-4">
                {!! Form::submit('ログイン', ['class' => 'btn btn-primary']) !!}
              </div>
              <div class="col-md-5">
                <div class="checkbox-inline">
                  {!! Form::checkbox('remember_me', '1', null, ['id' => 'remember_me']) !!}
                  {!! Form::label('remember_me', '保存する') !!}
                </div>
              </div>
            </div>
          </fieldset>
        {!! Form::close() !!}
        <hr />
        {!! Form::open(['url' => 'user/login-oauth', 'class' => 'form-horizontal text-right']) !!}
          {!! Form::submit('facebookでログイン', ['class' => 'btn btn-primary']) !!}
        {!! Form::close() !!}
      </div>
    </div>
  </div>
@stop
