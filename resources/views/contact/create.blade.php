@extends('layouts.master')

@section('title')
お問い合わせ
@stop

@section('content')
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card card-body">
                {!! Form::open(['url' => 'contact/send', 'method' => 'post']) !!}
                    <div class="row mb-3">
                        {!! Form::label('contact_name', 'お名前', ['class' => 'col-md-4 col-form-label']) !!}
                        <div class="col-md-8">
                            {!! Form::text('contact_name', Html::requestValue('contact_name'), ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="row mb-3">
                        {!! Form::label('email', 'メールアドレス', ['class' => 'col-md-4 col-form-label']) !!}
                        <div class="col-md-8">
                            {!! Form::email('email', Html::requestValue('email'), ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="row mb-3">
                        {!! Form::label('contact_type', 'お問い合わせ種別', ['class' => 'col-md-4 col-form-label']) !!}
                        <div class="col-md-8">
                            {!! Form::select('contact_type', $contact_type_list, Html::requestValue('contact_type'), ['class' => 'form-select']) !!}
                        </div>
                    </div>

                    <div class="row mb-3">
                        {!! Form::label('contact_message', 'メッセージ', ['class' => 'col-md-4 col-form-label']) !!}
                        <div class="col-md-8">
                            {!! Form::textarea('contact_message', Html::requestValue('contact_message'), ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8 offset-md-4">
                            {!! Form::submit('送信する', ['class' => 'btn btn-primary']) !!}
                        </div>
                    </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@stop
