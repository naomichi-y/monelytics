@extends('layouts.master')

@section('title')
アカウント
@stop

@section('content_header')
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

  <div class="alert alert-dismissible alert-info">
    <p>アカウントの更新機能は未実装です。</p>
  </div>

  <div class="row">
    <div class="col-md-6">
      {{Form::open(array('url' => 'user/update', 'method' => 'post', 'class' => 'form-horizontal'))}}
        <div class="form-group">
          {{Form::label('nickname', '名前', array('class' => 'col-md-3 control-label'))}}
          <div class="col-md-9">
            {{Form::text('nickname', Input::get('nickname', Auth::user()->nickname), array('class' => 'form-control'))}}
          </div>
        </div>

        <div class="form-group">
          {{Form::label('email', 'メールアドレス', array('class' => 'col-md-3 control-label'))}}
          <div class="col-md-9">
            {{Form::text('email', Input::get('email', Auth::user()->email), array('class' => 'form-control'))}}
          </div>
        </div>

        <div class="form-group">
          <div class="col-md-9 col-md-offset-3">
            @if (Auth::user()->type == User::TYPE_DEMO)
              {{Form::submit('更新 (デモでは利用不可)', array('class' => 'btn btn-primary disabled'))}}
            @else
              {{Form::submit('更新', array('class' => 'btn btn-primary disabled'))}}
            @endif
          </div>
        </div>
      {{Form::close()}}
    </div>
  </div>

  <hr />
  <div class="text-right">
    {{Form::open(array('url' => 'user/withdrawal'))}}
      @if (Auth::user()->type == User::TYPE_DEMO)
        {{Form::button('サービスの退会 (デモでは利用不可)', array('class' => 'btn btn-danger disabled'))}}
      @else
        {{Form::button('サービスの退会', array('class' => 'btn btn-danger', 'data-toggle' => 'modal', 'data-target' => '#confirm-modal'))}}
      @endif
    {{Form::close()}}
  </div>
@stop
