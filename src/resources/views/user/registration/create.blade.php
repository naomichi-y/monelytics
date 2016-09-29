@extends('layouts.master')

@section('title')
会員登録
@stop

@section('content')
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="well">
                {!! Form::open(['url' => 'user', 'class' => 'form-horizontal']) !!}
                    <fieldset>
                        <div class="form-group">
                            {!! Form::label('nickname', '名前', ['class' => 'col-md-4 control-label']) !!}
                            <div class="col-md-8">
                                {!! Form::text('nickname', null, ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('email', 'メールアドレス', ['class' => 'col-md-4 control-label']) !!}
                            <div class="col-md-8">
                                {!! Form::email('email', null, ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('password', 'パスワード', ['class' => 'col-md-4 control-label']) !!}
                            <div class="col-md-8">
                                {!! Form::password('password', ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="form-group form-group-adjust">
                            <div class="col-md-8 col-md-offset-4">
                                {!! Form::submit('会員登録', ['class' => 'btn btn-primary']) !!}
                            </div>
                        </div>
                    </fieldset>
                {!! Form::close() !!}
                <hr />
                {!! Form::open(['url' => 'user/create-oauth', 'class' => 'form-horizontal text-right']) !!}
                    {!! Form::submit('facebookで会員登録', ['class' => 'btn btn-primary']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@stop
