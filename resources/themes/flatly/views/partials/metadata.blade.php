<title>
    @section('title')
        {{ trans($header['title'] ? $header['title'] : Widget::site_name()) }}
    @show
</title>
<meta name="description" itemprop="description"
      content="@section("meta-description"){{ trans($header['description'] ? $header['description'] : Widget::site_description()) }}@show" />
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<link rel="stylesheet" href="{{ asset('themes/flatly/css/bootstrap.min.css') }}" media="screen">
<link rel="stylesheet" href="{{ asset('themes/flatly/css/custom.min.css') }}">
<link rel="stylesheet" href="{{ asset('themes/lumen/bower/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('themes/lumen/bower/select2-bootstrap-theme/dist/select2-bootstrap.min.css') }}">
<!--[if lt IE 9]>
<script src="{{ asset('themes/flatly/bower/html5shiv/dist/html5shiv.min.js') }}"></script>
<script src="{{ asset('themes/flatly/bower/respond/dest/respond.min.js') }}"></script>
<![endif]-->
@yield('head')