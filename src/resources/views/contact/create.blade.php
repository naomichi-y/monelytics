@extends('layouts.master')

@section('title')
お問い合わせ
@stop

@section('content')
  <div class="row">
    <div class="col-md-6 col-md-offset-3">
      <div class="well">
        {!! Form::open(array('url' => 'contact/send', 'method' => 'post', 'class' => 'form-horizontal')) !!}
          <div class="form-group">
            {!! Form::label('contact_name', 'お名前', array('class' => 'col-md-4 control-label')) !!}
            <div class="col-md-8">
              {!! Form::text('contact_name', Input::get('contact_name'), array('class' => 'form-control')) !!}
            </div>
          </div>

          <div class="form-group">
            {!! Form::label('email', 'メールアドレス', array('class' => 'col-md-4 control-label')) !!}
            <div class="col-md-8">
              {!! Form::email('email', Input::get('email'), array('class' => 'form-control')) !!}
            </div>
          </div>

          <div class="form-group">
            {!! Form::label('contact_type', 'お問い合わせ種別', array('class' => 'col-md-4 control-label')) !!}
            <div class="col-md-8">
              {!! Form::select('contact_type', $contact_type_list, Input::get('contact_type'), array('class' => 'form-control')) !!}
            </div>
          </div>

          <div class="form-group">
            {!! Form::label('contact_message', 'メッセージ', array('class' => 'col-md-4 control-label')) !!}
            <div class="col-md-8">
              {!! Form::textarea('contact_message', Input::get('contact_message'), array('class' => 'form-control')) !!}
            </div>
          </div>
          <div class="form-group">
            <div class="col-md-8 col-md-offset-4">
              {!! Form::submit('送信する', array('class' => 'btn btn-primary')) !!}
            </div>
          </div>
        {!! Form::close() !!}
      </div>
    </div>
  </div>
@stop
