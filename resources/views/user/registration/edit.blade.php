@extends('layouts.master')

@section('title')
プロフィール
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
                {!! Form::open(['url' => 'user/withdrawal', 'id' => 'confirm-form', 'method' => 'put']) !!}
                    <div class="modal-header">
                        <h4 class="modal-title">退会の確認</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                    </div>
                    <div class="modal-body">
                        <p>サービスから退会します。本当によろしいですか?</p>
                    </div>
                    <div class="modal-footer">
                        {!! Form::button('退会する', ['class' => 'btn btn-primary', 'id' => 'confirm']) !!}
                        {!! Form::button('キャンセル', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal', 'aria-hidden' => 'true']) !!}
                    </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            {!! Form::open(['url' => 'user/update', 'method' => 'put']) !!}
                <div class="row mb-3">
                    {!! Form::label('nickname', '名前', ['class' => 'col-md-4 col-form-label']) !!}
                    <div class="col-md-8">
                        {!! Form::text('nickname', Request::input('nickname', Auth::user()->nickname), ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="row mb-3">
                    {!! Form::label('email', 'メールアドレス', ['class' => 'col-md-4 col-form-label']) !!}
                    <div class="col-md-8">
                        {!! Form::text('email', Request::input('email', Auth::user()->email), ['class' => 'form-control']) !!}
                    </div>
                </div>

                @if (strlen(Auth::user()->password))
                    {{-- 廃止した Facebook ログインだけで作られた利用者は password が空で、
                         入力できる現在のパスワードを持たない。欄を出すと入れようのない
                         ものを求めることになるため、持っている人にだけ見せる。
                         検証側も同じ条件で判断する (User::updateValidate)。 --}}
                    <div class="row mb-3">
                        {!! Form::label('current_password', '現在のパスワード', ['class' => 'col-md-4 col-form-label']) !!}
                        <div class="col-md-8">
                            {!! Form::password('current_password', ['class' => 'form-control', 'autocomplete' => 'current-password']) !!}
                            <span class="note">(パスワードを変更する場合のみ入力)</span>
                        </div>
                    </div>
                @endif

                <div class="row mb-3">
                    {!! Form::label('password', '新しいパスワード', ['class' => 'col-md-4 col-form-label']) !!}
                    <div class="col-md-8">
                        {!! Form::password('password', ['class' => 'form-control', 'autocomplete' => 'new-password']) !!}
                        <span class="note">(変更する場合のみ入力)</span>
                    </div>
                </div>

                <div class="row mb-3">
                    {!! Form::label('password_confirmation', '新しいパスワード (再入力)', ['class' => 'col-md-4 col-form-label']) !!}
                    <div class="col-md-8">
                        {!! Form::password('password_confirmation', ['class' => 'form-control', 'autocomplete' => 'new-password']) !!}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8 offset-md-4">
                        {!! Form::submit('更新', ['class' => 'btn btn-primary']) !!}
                    </div>
                </div>
            {!! Form::close() !!}
        </div>
    </div>

    <hr />
    <div class="text-end">
        {!! Form::open(['url' => 'user/withdrawal']) !!}
            {!! Form::button('サービスの退会', ['class' => 'btn btn-danger', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#confirm-modal']) !!}
        {!! Form::close() !!}
    </div>
@stop
