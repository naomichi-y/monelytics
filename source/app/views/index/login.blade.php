@extends('layouts.master')

@section('include_header')
{{HTML::script('assets/js/jquery.backstretch.min.js')}}
<style>
.lead {
  margin-top: 60px;
  margin-bottom: 60px;
}

.well {
  background-color: #333;
  color: #fff;
  opacity: 0.9;
}

.page-header {
  border-bottom: 0;
  margin-bottom: 0;
}

hr {
  border: 1px solid #585858;
}
</style>
<script>
  $(function() {
    $.backstretch("/assets/images/top_image.jpg");

    $("#trial").click(function() {
      $("#trial_email").val("demo@monelytics.me");
      $("#trial_password").val("demo");
    });
  });
</script>
@stop

@section('content_header')
  <div class="lead">
    ようこそ! monelyticsはシンプルで使いやすい無料の家計簿アプリです。
  </div>
@stop

@section('content')
  <div class="row">
    <div class="col-md-5 col-md-offset-1">
      <div class="well bs-component">
        {{Form::open(array('url' => 'user/login', 'class' => 'form-horizontal'))}}
          <fieldset>
            <div class="form-group">
              {{Form::label('email', 'メールアドレス', array('class' => 'col-md-4 control-label'))}}
              <div class="col-md-8">
                {{Form::email('email', null, array('class' => 'form-control', 'autofocus'))}}
              </div>
            </div>
            <div class="form-group">
              {{Form::label('password', 'パスワード', array('class' => 'col-md-4 control-label'))}}
              <div class="col-md-8">
                {{Form::password('password', array('class' => 'form-control'))}}
              </div>
            </div>
            <div class="form-group form-group-adjust">
              <div class="col-md-3 col-md-offset-4">
                {{Form::submit('ログイン', array('class' => 'btn btn-primary'))}}
              </div>
              <div class="col-md-5">
                <div class="checkbox-inline">
                  {{Form::checkbox('remember_me', '1', null, array('id' => 'remember_me'))}}
                  {{Form::label('remember_me', '保存する')}}
                </div>
              </div>
            </div>
          </fieldset>
        {{Form::close()}}
        <hr />
        {{Form::open(array('url' => 'user/login-with-oauth', 'class' => 'form-horizontal'))}}
          <fieldset>
            <div class="form-group form-group-adjust text-right">
              <div class="col-md-8 col-md-offset-4">
                {{Form::submit('facebookでログイン', array('name' => 'facebook', 'class' => 'btn btn-primary btn-sm'))}}
              </div>
            </div>
          </fieldset>
        {{Form::close()}}
      </div>
    </div>
    <div class="col-md-5">
      <div class="well bs-component">
        {{Form::open(array('url' => 'user/create', 'class' => 'form-horizontal'))}}
          <fieldset>
            <div class="form-group">
              {{Form::label('nickname', '名前', array('class' => 'col-md-4 control-label'))}}
              <div class="col-md-8">
                {{Form::text('nickname', null, array('class' => 'form-control', 'id' => null))}}
              </div>
            </div>
            <div class="form-group">
              {{Form::label('email', 'メールアドレス', array('class' => 'col-md-4 control-label'))}}
              <div class="col-md-8">
                {{Form::email('email', null, array('class' => 'form-control', 'id' => null))}}
              </div>
            </div>
            <div class="form-group">
              {{Form::label('password', 'パスワード', array('class' => 'col-md-4 control-label'))}}
              <div class="col-md-8">
                {{Form::password('password', array('class' => 'form-control', 'id' => null))}}
              </div>
            </div>
            <div class="form-group form-group-adjust">
              <div class="col-md-8 col-md-offset-4">
                {{Form::submit('会員登録', array('name' => 'registration', 'class' => 'btn btn-primary'))}}
              </div>
            </div>
          </fieldset>
        {{Form::close()}}
        <hr />

        {{Form::open(array('url' => 'user/create-with-oauth', 'class' => 'form-horizontal'))}}
          <fieldset>
            <div class="form-group form-group-adjust text-right">
              <div class="col-md-8 col-md-offset-4">
                {{Form::submit('facebookで登録', array('name' => 'facebook', 'class' => 'btn btn-primary btn-sm'))}}
              </div>
            </div>
          </fieldset>
        {{Form::close()}}
      </div>
    </div>
  </div>
  {{Form::open(array('url' => 'user/login', 'class' => 'form-horizontal', 'id' => 'trial'))}}
    <div class="text-center">
      <p>monelyticsは試用版としてログインすることもできます。</p>
      {{Form::hidden('email', '', array('id' => 'trial_email'))}}
      {{Form::hidden('password', '', array('id' => 'trial_password'))}}
      {{Form::submit('体験する', array('class' => 'btn btn-info btn-lg'))}}
    </div>
  {{Form::close()}}
@stop
