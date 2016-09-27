<!doctype html>
<html>
  @include('layouts.head_tags')
  @yield('include_header')

  <body>
    @include('layouts.content_header')

    <div class="container">
      <div class="page-header">
        <div class="row">
          <div class="col-md-8"><h1>@yield('title')</h1></div>
          <div class="col-md-4 text-right">
             @yield('function')
          </div>
        </div>
      </div>

      @include('layouts.content_notice')

      @yield('content')
    </div>

    @include('layouts.content_footer')
  </body>
</html>
