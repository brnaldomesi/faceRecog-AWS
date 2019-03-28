<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Face') }}</title>

    <!-- Fonts -->
    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="{{ asset('global/plugins/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('global/plugins/simple-line-icons/simple-line-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('global/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('global/plugins/uniform/css/uniform.default.css') }}" rel="stylesheet">
    <!-- END GLOBAL MANDATORY STYLES -->
    <!-- BEGIN PAGE LEVEL STYLES -->
    @yield('extracss')

    <link href="{{ asset('css/custom.css') }}" rel="stylesheet" type="text/css" />

    <!-- END PAGE LEVEL STYLES -->
    <!-- BEGIN THEME STYLES -->
    <link href="{{ asset('css/components-rounded.css') }}" rel="stylesheet">
    <link href="{{ asset('css/plugins.css') }}" rel="stylesheet">
    <link href="{{ asset('admin_assets/layout3/css/layout.css') }}" rel="stylesheet">
    <link href="{{ asset('admin_assets/layout3/css/themes/default.css') }}" rel="stylesheet" id="style_color">
    <link href="{{ asset('admin_assets/layout3/css/custom.css') }}" rel="stylesheet">
    <!-- END THEME STYLES -->
    <link href="{{ asset('css/appcustom.css') }}" rel="stylesheet">
</head>
<!-- END HEAD -->
<!-- BEGIN BODY -->
<!-- DOC: Apply "page-header-menu-fixed" class to set the mega menu fixed  -->
<!-- DOC: Apply "page-header-top-fixed" class to set the top menu fixed  -->
<body>
    <div id="app">
        <!-- <nav class="navbar navbar-expand-md navbar-light navbar-laravel"> -->
          <!-- BEGIN HEADER -->
          <div class="page-header">
            <!-- BEGIN HEADER TOP -->
            <div class="page-header-top">
              <div class="container">
                <!-- BEGIN LOGO -->
                <div class="page-logo">
                  <a class="navbar-brand" href="{{ url('/') }}">
						<img src="{{ url('/img/logo_default.png') }}" style="max-width:150px;">
                  </a>
                </div>
                <!-- END LOGO -->
                <!-- BEGIN RESPONSIVE MENU TOGGLER -->
                <a href="javascript:;" class="menu-toggler"></a>
                <!-- END RESPONSIVE MENU TOGGLER -->
                <!-- BEGIN TOP NAVIGATION MENU -->
                <div class="top-menu">
                  <ul class="nav navbar-nav pull-right">
                      <!-- Authentication Links -->
                      @guest
                          <li>
                              <a href="{{ route('login') }}">{{ __('Login') }}</a>
                          </li>
                      @else
                        <li class="dropdown dropdown-user dropdown-dark">

                          <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre  data-hover="dropdown" data-close-others="true">
                              <span class="username">{{ Auth::user()->name }}</span>
                          </a>
                          <ul class="dropdown-menu dropdown-menu-default">
                            <!-- <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown"> -->
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">
                                    <i class="icon-key"> 
                                    </i>
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                              </li>
                            <!-- </div> -->
                          </ul>
                      </li>
                      @endguest
                  </ul>
                </div>
              </div>
            </div>
            <!-- END HEADER TOP -->
            <!-- BEGIN HEADER MENU -->
            @guest
            @else
            <div class="page-header-menu">
              <div class="container">
                  
                  <!-- <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                      <span class="navbar-toggler-icon"></span>
                  </button> -->

                  <div class="hor-menu">
                      <!-- Left Side Of Navbar
                      <ul class="navbar-nav mr-auto">

                      </ul> -->
                      <ul class="nav navbar-nav">
                        <li class="nav-item @if(Request::segment(1) == 'home'){{ __('active')}}@endif">
                            <a class="nav-link" href="{{ url('/home') }}">{{ __('Dashboard') }}</a>
                        </li>

                        @if (Auth::user()->permission->isSuperAdmin())
                          <li class="nav-item @if(Request::segment(1) == 'organization'){{ __('active')}}@endif">
                              <a class="nav-link" href="{{ url('/organization') }}">{{ __('Organization') }}</a>
                          </li>
                        @else
  						            <li class="nav-item @if(Request::segment(1) == 'cases'){{ __('active')}}@endif">
                              <a class="nav-link" href="{{ url('/cases') }}">{{ __('Cases') }}</a>
                          </li>
                          <li class="nav-item @if(Request::segment(1) == 'portraits' && Request::segment(2) == 'create'){{ __('active')}}@endif">
                              <a class="nav-link" href="{{ url('/portraits/create') }}">{{ __('Enroll') }}</a>
                          </li>
                        @endif

              @if (Auth::user()->permission->isAdmin())
                        <li class="nav-item @if(Request::segment(1) == 'admin'){{ __('active')}}@endif">
                            <a class="nav-link" href="{{ url('/admin') }}">{{ __('Admin') }}</a>
                        </li>
              @endif

                      </ul>
                      <!-- Right Side Of Navbar -->
                      
                  </div>
              </div>
            </div>
            <!-- END HEADER MENU -->
            @endguest
          </div>
          <!-- END HEADER -->
    <!--     </nav> -->

  

        <main class="py-4">
            <!-- sidebar content -->
            {{--
            <div id="sidebar" class="col-md-2">
                @yield('sidebar')
            </div>
            --}}
            <!-- main content -->
            <!--<div id="content" class="col-md-10">-->
                @yield('content')
            <!--</div>-->
        </main>
    </div>
    @guest
    @else
    <!-- BEGIN FOOTER -->
    <div class="page-footer">
      <div class="container">
         2019 &copy; AFR Engine
      </div>
    </div>
    <div class="scroll-to-top">
      <i class="icon-arrow-up"></i>
    </div>
    <!-- END FOOTER -->
    @endguest
    <script src="{{ asset('global/plugins/jquery.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/plugins/jquery-migrate.min.js') }}" type="text/javascript"></script>
    <!-- IMPORTANT! Load jquery-ui.min.js before bootstrap.min.js to fix bootstrap tooltip conflict with jquery ui tooltip -->
    <script src="{{ asset('global/plugins/jquery-ui/jquery-ui.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/plugins/bootstrap/js/bootstrap.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/plugins/jquery-slimscroll/jquery.slimscroll.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/plugins/jquery.blockui.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/plugins/jquery.cokie.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/plugins/uniform/jquery.uniform.min.js') }}" type="text/javascript"></script>
    <!-- END CORE PLUGINS -->
    <!-- BEGIN PAGE LEVEL PLUGINS -->
    <script type="text/javascript">
        var base_url = '{{$base_url}}';
        var s3_base_image_url = '{{env('AWS_S3_REAL_OBJECT_URL_DOMAIN')}}';
    </script>
    @yield('extrajs')
    <!-- END PAGE LEVEL PLUGINS -->
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="{{ asset('global/plugins/exif-js/exif.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/plugins/javascript-load-image/js/load-image.all.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/scripts/metronic.js') }}" type="text/javascript"></script>
    <script src="{{ asset('admin_assets/layout3/scripts/layout.js') }}" type="text/javascript"></script>
    <script src="{{ asset('admin_assets/layout3/scripts/demo.js') }}" type="text/javascript"></script>
    <script src="{{ asset('global/plugins/bootbox/bootbox.min.js') }}" type="text/javascript"></script>
    <script>
      jQuery(document).ready(function() {    
        Metronic.init(); // init metronic core components
        Layout.init(); // init current layout
        Demo.init(); // init demo features
      });

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
   </script>
   <!-- END PAGE LEVEL SCRIPTS -->
</body>
</html>
