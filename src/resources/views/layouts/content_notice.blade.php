@if (Session::has('success'))
  <div class="alert alert-dismissable alert-success">
    {{{Session::get('success')}}}
  </div>
@endif

@if ($errors->count())
  <div class="alert alert-dismissable alert-warning">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <ul>
      @foreach ($errors->all() as $error)
      <li>{{{$error}}}</li>
      @endforeach
    </ul>
  </div>
@endif
