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
        <strong>You received new sharing application. </strong> Click <a href="{{ url('/admin/sharing') }}">here</a> to go see and approve / decline it.</div>
    @else
      <div class="alert alert-danger">
        <strong>You received {{ $appCount }} new sharing applications. </strong> Click <a href="{{ url('/admin/sharing') }}">here</a> to go see and approve / decline them.</div>
    @endif
  @endif
      <div class="row">
		    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12 margin-bottom-10">
          <a class="dashboard-stat dashboard-stat-light blue-madison" href="javascript:;">
          <div class="visual">
            <i class="fa fa-database fa-icon-medium"></i>
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
        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12 margin-bottom-10">
          <a class="dashboard-stat dashboard-stat-light blue-madison" href="javascript:;">
          <div class="visual">
            <i class="fa fa-database fa-icon-medium"></i>
          </div>
          <div class="details">
            <div class="number counter">
               {{ $facesCount }}
            </div>
            <div class="desc">
               Searchable Faces
            </div>
          </div>
          </a>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
          <a class="dashboard-stat dashboard-stat-light red-intense" href="javascript:;">
          <div class="visual">
            <i class="fa fa-briefcase"></i>
          </div>
          <div class="details">
            <div class="number counter">
              {{ $searchCount}}
            </div>
            <div class="desc">
               Searches Performed
            </div>
          </div>
          </a>
        </div>

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