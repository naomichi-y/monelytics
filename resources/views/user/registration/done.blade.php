@extends('layouts.master')

@section('title')
登録完了
@stop

@section('include_header')
    <script>
        $(function() {
            $(":button").click(function() {
                location.href = '/dashboard';
            });
        });
    </script>
@stop

@section('content')
    <script>
        spikeLoader.conversion('cv_jK3zdre5d');
    </script>
    <div class="text-center">
        <p class="lead">ご登録ありがとうございました! それではさっそく使い始めましょう!</p>
        {!! Form::open() !!}
            {!! Form::button('ホームへ移動する', ['class' => 'btn btn-info btn-lg']) !!}
        {!! Form::close() !!}
    </div>
@stop
