@extends('layouts.master')

@section('title')
アカウント
@stop

@section('include_header')
<script>
  $(function() {
    // 確認モーダルの表示
    $("#confirm").click(function() {
      $("form").submit();
    });
  });
</script>
@stop

@section('content')
  <div id="confirm-modal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        {{Form::open(array('url' => 'user/withdrawal', 'id' => 'confirm-form'))}}
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title">退会の確認</h4>
          </div>
          <div class="modal-body">
            <p>サービスから退会します。本当によろしいですか?</p>
          </div>
          <div class="modal-footer">
            {{Form::button('退会する', array('class' => 'btn btn-primary', 'id' => 'confirm'))}}
            {{Form::button('キャンセル', array('class' => 'btn btn-default', 'data-dismiss' => 'modal', 'aria-hidden' => 'true'))}}
          </div>
        {{Form::close()}}
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-5">
      {{Form::open(array('url' => 'user/update', 'method' => 'post', 'class' => 'form-horizontal'))}}
        <div class="form-group">
          {{Form::label('nickname', '名前', array('class' => 'col-md-4 control-label'))}}
          <div class="col-md-8">
            {{Form::text('nickname', Input::get('nickname', Auth::user()->nickname), array('class' => 'form-control'))}}
          </div>
        </div>

        <div class="form-group">
          {{Form::label('email', 'メールアドレス', array('class' => 'col-md-4 control-label'))}}
          <div class="col-md-8">
            {{Form::text('email', Input::get('email', Auth::user()->email), array('class' => 'form-control'))}}
          </div>
        </div>

        <div class="form-group">
          {{Form::label('password', 'パスワード', array('class' => 'col-md-4 control-label'))}}
          <div class="col-md-8">
            {{Form::password('password', array('class' => 'form-control'))}}
            <span class="note">(変更する場合のみ入力)</span>
          </div>
        </div>

        <div class="form-group">
          {{Form::label('password_confirmation', 'パスワード (再入力)', array('class' => 'col-md-4 control-label'))}}
          <div class="col-md-8">
            {{Form::password('password_confirmation', array('class' => 'form-control'))}}
          </div>
        </div>

        <div class="form-group">
          <div class="col-md-8 col-md-offset-4">
            {{Form::submit('更新', array('class' => 'btn btn-primary'))}}
          </div>
        </div>
      {{Form::close()}}
    </div>
  </div>

  <hr />
  <div class="text-right">
    {{Form::open(array('url' => 'user/withdrawal'))}}
      {{Form::button('サービスの退会', array('class' => 'btn btn-danger', 'data-toggle' => 'modal', 'data-target' => '#confirm-modal'))}}
    {{Form::close()}}
  </div>
@stop
