@extends('layouts.master')

@section('title')
お問い合わせ
@stop

@section('content')
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="well">
                {!! Form::open(['url' => 'contact/send', 'method' => 'post', 'class' => 'form-horizontal']) !!}
                    <div class="form-group">
                        {!! Form::label('contact_name', 'お名前', ['class' => 'col-md-4 control-label']) !!}
                        <div class="col-md-8">
                            {!! Form::text('contact_name', Input::get('contact_name'), ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="form-group">
                        {!! Form::label('email', 'メールアドレス', ['class' => 'col-md-4 control-label']) !!}
                        <div class="col-md-8">
                            {!! Form::email('email', Input::get('email'), ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="form-group">
                        {!! Form::label('contact_type', 'お問い合わせ種別', ['class' => 'col-md-4 control-label']) !!}
                        <div class="col-md-8">
                            {!! Form::select('contact_type', $contact_type_list, Input::get('contact_type'), ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="form-group">
                        {!! Form::label('contact_message', 'メッセージ', ['class' => 'col-md-4 control-label']) !!}
                        <div class="col-md-8">
                            {!! Form::textarea('contact_message', Input::get('contact_message'), ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-8 col-md-offset-4">
                            {!! Form::submit('送信する', ['class' => 'btn btn-primary']) !!}
                        </div>
                    </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@stop
