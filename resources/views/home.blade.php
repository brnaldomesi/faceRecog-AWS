@extends('layouts.master')

@section('content')
<div class="page-container">
  <!-- BEGIN PAGE HEAD -->
  <div class="page-head">
    <div class="container">
      <!-- BEGIN PAGE TITLE -->
      <div class="page-title">
        <h1>Dashboard</h1>
      </div>
      <!-- END PAGE TITLE -->
    </div>
  </div>
  <!-- END PAGE HEAD -->
  <div class="page-content">
    <div class="container">
     
      <ul class="page-breadcrumb breadcrumb">
        <li>
          <a href="{{ url('/') }}">Home</a><i class="fa fa-circle"></i>
        </li>
        <li class="active">
          Dashboard
        </li>
      </ul>
  @if (Auth::user()->permission->isAdmin() && $appCount > 0)
    @if ($appCount == 1)
      <div class="alert alert-danger">
        <strong>You have a new request to share mugshot data. Click <a href="{{ url('/admin/sharing') }}">here</a> to manage your requests.</div>
    @else
      <div class="alert alert-danger">
        <strong>You received {{ $appCount }} new requests to share mugshot data. Click <a href="{{ url('/admin/sharing') }}">here</a> to manage your requests.</div>
    @endif
  @endif
      <div class="row">
        @if (Auth::user()->permission->isSuperAdmin())
          <div class="col-sm-6 col-xs-12 margin-bottom-10">
            <a class="dashboard-stat dashboard-stat-light purple-plum" href="{{ route('organization') }}">
            <div class="visual">
              <i class="fa fa-building fa-icon-medium"></i>
            </div>
            <div class="details">
              <div class="number">
                 {{ $organizationCount }}
              </div>
              <div class="desc">
                 Organization count
              </div>
            </div>
            </a>
          </div>
          <div class="col-sm-6 col-xs-12 margin-bottom-10">
            <a class="dashboard-stat dashboard-stat-light blue-madison" href="{{ route('allcases.show') }}">
            <div class="visual">
              <i class="fa fa-briefcase fa-icon-medium"></i>
            </div>
            <div class="details">
              <div class="number">
                 {{ $caseCount }} / {{ $solvedCaseCount }}
              </div>
              <div class="desc">
                 Total Cases / Solved Cases
              </div>
            </div>
            </a>
          </div>
          <div class="col-sm-6 col-xs-12 margin-bottom-10">
            <a class="dashboard-stat dashboard-stat-light blue-hoki" href="{{ route('faces.index') }}">
            <div class="visual">
              <i class="fa fa-user fa-icon-medium"></i>
            </div>
            <div class="details">
              <div class="number">
                 {{ $faceCount }}
              </div>
              <div class="desc">
                 Face count
              </div>
            </div>
            </a>
          </div>
          <div class="col-sm-6 col-xs-12 margin-bottom-10">
            <a class="dashboard-stat dashboard-stat-light green-haze" href="javascript:;">
            <div class="visual">
              <i class="fa fa-search fa-icon-medium"></i>
            </div>
            <div class="details">
              <div class="number">
                 {{ $searchCount }}
              </div>
              <div class="desc">
                 Search count
              </div>
            </div>
            </a>
          </div>
		  <div class="col-sm-6 col-xs-12 margin-bottom-10">
            <a class="dashboard-stat dashboard-stat-light blue-hoki" href="javascript:;">
            <div class="visual">
              <i class="fa fa-user fa-icon-medium"></i>
            </div>
            <div class="details">
              <div class="number">
                 {{ $faceQue }}
              </div>
              <div class="desc">
                 Face Que
              </div>
            </div>
            </a>
          </div>
		  <div class="col-sm-6 col-xs-12 margin-bottom-10">
            <a class="dashboard-stat dashboard-stat-light green-haze" href="javascript:;">
            <div class="visual">
              <i class="fa fa-search fa-icon-medium"></i>
            </div>
            <div class="details">
              <div class="number">
                 {{ $todaysUsers }}
              </div>
              <div class="desc">
                 Active Users Today
              </div>
            </div>
            </a>
          </div>
        @else
  		    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12 margin-bottom-10">
            <a class="dashboard-stat dashboard-stat-light blue-madison" href="javascript:;">
            <div class="visual">
              <i class="fa fa-briefcase fa-icon-medium"></i>
            </div>
            <div class="details">
              <div class="number">
                 {{ $caseCount }}
              </div>
              <div class="desc">
                 Your Active Cases
              </div>
            </div>
            </a>
          </div>
          
        @endif
      </div>
    </div>
  </div>
</div>

@endsection

@section('extrajs')
  <script type="text/javascript" src="{{ asset('global/plugins/counterup/jquery.waypoints.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('global/plugins/counterup/jquery.counterup.min.js') }}"></script>
  <script>
    $(document).ready(function () {
          //Search.init()
          $('#filteredCountLabel').text('')
          $('.counter').counterUp({
              delay: 10,
              time: 1000
          })
      });
  </script>
@endsection