@if (Session::has('success'))
    <div class="alert alert-dismissible fade show alert-success">
        {{{Session::get('success')}}}
    </div>
@endif

@if (isset($errors) && $errors->count())
    <div class="alert alert-dismissible fade show alert-warning">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{{$error}}}</li>
            @endforeach
        </ul>
    </div>
@endif
