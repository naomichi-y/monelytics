@extends('layouts.master')

@section('title')
登録完了
@stop

@section('include_header')
  <script>
    $(function() {
      $(":button").click(function() {
        location.href = '/home';
      });
    });
  </script>
@stop

@section('content')
  <div class="text-center">
    <p class="lead">ご登録ありがとうございました! それではさっそく使い始めましょう!</p>
    {{Form::open()}}
      {{Form::button('ホームへ移動する', array('class' => 'btn btn-info btn-lg'))}}
    {{Form::close()}}
  </div>
@stop