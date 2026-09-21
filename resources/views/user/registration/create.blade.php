@extends('layouts.master')

@section('title')
会員登録
@stop

@section('content')
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card card-body">
                {!! Form::open(['url' => 'user']) !!}
                    <fieldset>
                        <div class="row mb-3">
                            {!! Form::label('nickname', '名前', ['class' => 'col-md-4 col-form-label']) !!}
                            <div class="col-md-8">
                                {!! Form::text('nickname', null, ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="row mb-3">
                            {!! Form::label('email', 'メールアドレス', ['class' => 'col-md-4 col-form-label']) !!}
                            <div class="col-md-8">
                                {!! Form::email('email', null, ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="row mb-3">
                            {!! Form::label('password', 'パスワード', ['class' => 'col-md-4 col-form-label']) !!}
                            <div class="col-md-8">
                                {!! Form::password('password', ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="row mb-3 form-group-adjust">
                            <div class="col-md-8 offset-md-4">
                                {!! Form::submit('会員登録', ['class' => 'btn btn-primary']) !!}
                            </div>
                        </div>
                    </fieldset>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@stop
