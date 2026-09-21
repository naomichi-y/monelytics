@extends('layouts.master')

@section('title')
ログイン
@stop

@section('content')
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card card-body">
                {!! Form::open(['url' => 'user/login']) !!}
                    <fieldset>
                        <div class="row mb-3">
                            {!! Form::label('email', 'メールアドレス', ['class' => 'col-md-4 col-form-label']) !!}
                            <div class="col-md-8">
                                {!! Form::email('email', null, ['class' => 'form-control', 'autofocus']) !!}
                            </div>
                        </div>
                        <div class="row mb-3">
                            {!! Form::label('password', 'パスワード', ['class' => 'col-md-4 col-form-label']) !!}
                            <div class="col-md-8">
                                {!! Form::password('password', ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="row mb-3 form-group-adjust">
                            <div class="col-md-8 offset-md-4 d-flex align-items-center gap-3">
                                {!! Form::submit('ログイン', ['class' => 'btn btn-primary']) !!}
                                <div class="form-check mb-0">
                                    {!! Form::checkbox('remember_me', '1', null, ['id' => 'remember_me', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('remember_me', 'ログイン状態を保持する', ['class' => 'form-check-label']) !!}
                                </div>
                            </div>
                        </div>
                    </fieldset>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@stop
