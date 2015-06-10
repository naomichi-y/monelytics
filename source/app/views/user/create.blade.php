@extends('layouts.master')

@section('title')
会員登録
@stop

@section('content')
  <div class="row">
    <div class="col-md-6 col-md-offset-3">
      <div class="well">
        {{Form::open(array('url' => 'user/create', 'class' => 'form-horizontal'))}}
          <fieldset>
            <div class="form-group">
              {{Form::label('nickname', '名前', array('class' => 'col-md-4 control-label'))}}
              <div class="col-md-8">
                {{Form::text('nickname', null, array('class' => 'form-control'))}}
              </div>
            </div>
            <div class="form-group">
              {{Form::label('email', 'メールアドレス', array('class' => 'col-md-4 control-label'))}}
              <div class="col-md-8">
                {{Form::email('email', null, array('class' => 'form-control'))}}
              </div>
            </div>
            <div class="form-group">
              {{Form::label('password', 'パスワード', array('class' => 'col-md-4 control-label'))}}
              <div class="col-md-8">
                {{Form::password('password', array('class' => 'form-control'))}}
              </div>
            </div>
            <div class="form-group form-group-adjust">
              <div class="col-md-8 col-md-offset-4">
                {{Form::submit('会員登録', array('class' => 'btn btn-primary'))}}
              </div>
            </div>
          </fieldset>
        {{Form::close()}}
        <hr />
        {{Form::open(array('url' => 'user/create-with-oauth', 'class' => 'form-horizontal text-right'))}}
          {{Form::submit('facebookで会員登録', array('class' => 'btn btn-primary'))}}
        {{Form::close()}}
      </div>
    </div>
  </div>
@stop
