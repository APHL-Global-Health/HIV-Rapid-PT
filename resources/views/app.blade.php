<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <link rel="icon" type="image/x-icon" href="{{ Config::get('cms.favicon') }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta id="token" name="token" value="{{ csrf_token() }}">  

        <title>{!! Config::get('cms.name') !!}</title>

        <meta name="description" content="">
        <meta name="author" content="{{ Config::get('cms.designer') }}">
        <!-- Base Styling  -->
        <link href="{{ asset('css/app.v1.css') }}" rel="stylesheet">
        <!-- Bootstrap core CSS -->
        <link href="{{ asset('css/bootstrap.css') }}" rel="stylesheet">

        <!-- Custom Font -->
        <link rel="stylesheet" href="{{ asset('css/font.css') }}">

        <!-- Custom Styling -->
        <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
        <!-- Awesome Bootstrap checkbox -->
        <link rel="stylesheet" href="{{ asset('css/awesome-bootstrap-checkbox.css') }}">

        <!-- Toastr Styling -->
        <link href="{{ asset('css/toastr.min.css') }}" rel="stylesheet">
        <!-- Sweet Alert Styling-->
        <link rel="stylesheet" type="text/css" href="{{ asset('css/sweetalert.css') }}">

        <link rel="stylesheet" type="text/css" href="{{ asset('css/hivr.css') }}">
    </head>
    <body>
        <!-- Preloader -->
        <div class="loading-container">
            <div class="loading">
                <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Preloader -->

        <aside class="left-panel">

            <div class="user text-xs-center">
                <img src="{{ (count(Request::segments())>1)?'../../'.Config::get('cms.logo'):Config::get('cms.logo') }}" class="img-circle" alt="...">
                <h4 class="user-name text-warning">{!! Config::get('cms.name') !!}</h4>
            </div>
            <nav class="navigation">
                <ul class="list-unstyled">
                    <li class="{!! (count(Request::segments())==0 || Request::segment(1)==strtolower('welcome'))?strtolower(trans('messages.active')):'' !!}">
                        <a href="{!! url('welcome') !!}"><i class="fa fa-dashboard"></i> {!! trans('messages.dashboard') !!}</a>
                    </li>
                    @permission('proficiency-testing')
                    <li class="has-submenu{!! in_array(Request::segment(1), [strtolower('pt'), strtolower('panel'), strtolower('lot'), strtolower('material'), strtolower('round'), strtolower('shipment'), strtolower('result')])?' '.strtolower(trans('messages.active')):'' !!}">
                        <a href="#"><i class="fa fa-graduation-cap"></i> {!! trans('messages.pt') !!}</a>
                        <ul class="list-unstyled">
                            @permission('read-sample')
                            <li class="{!! Request::segment(1)==strtolower('material')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('material') !!}"><i class="fa fa-bookmark"></i> {!! trans('messages.sample-preparation') !!}</a>
                            </li>
                            @endpermission
                            @permission('read-round')
                            <li class="{!! Request::segment(1)==strtolower('round')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('round') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.pt-round', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('lot')
                            <li class="{!! Request::segment(1)==strtolower('lot')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('lot') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.lot', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('read-panel')
                            <li class="{!! Request::segment(1)==strtolower('panel')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('panel') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.panel', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('read-shipment')
                            <li class="{!! Request::segment(1)==strtolower('shipment')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('shipment') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.shipment', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('read-result')
                            <li class="{!! Request::segment(1)==strtolower('result')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('result') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.result', 2) !!}</a>
                            </li>
                            @endpermission
                        </ul>
                    </li>
                    @endpermission
                    @permission('program-management')
                    <li class="has-submenu{!! in_array(Request::segment(1), [strtolower('set'), strtolower('field'), strtolower('option')])?' '.strtolower(trans('messages.active')):'' !!}">
                        <a href="#"><i class="fa fa-google-wallet"></i> {!! trans('messages.program-management') !!}</a>
                        <ul class="list-unstyled">
                            @permission('read-set')
                            <li class="{!! Request::segment(1)==strtolower('set')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('set') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.field-set', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('read-field')
                            <li class="{!! Request::segment(1)==strtolower('field')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('field') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.field', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('read-option')
                            <li class="{!! Request::segment(1)==strtolower('response')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('option') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.option', 2) !!}</a>
                            </li>
                            @endpermission
                        </ul>
                    </li>
                    @endpermission
                    @permission('config')
                    <li class="has-submenu{!! in_array(Request::segment(1), [strtolower('program'), strtolower('shipper'), strtolower('nonperf'), strtolower('designation')])?' '.strtolower(trans('messages.active')):'' !!}">
                        <a href="#"><i class="fa fa-cog"></i> {!! trans('messages.config') !!}</a>
                        <ul class="list-unstyled">
                            @permission('read-program')
                            <li class="{!! Request::segment(1)==strtolower('program')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('program') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.program', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('read-shipper')
                            <li class="{!! Request::segment(1)==strtolower('shipper')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('shipper') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.shipper', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('read-nonperf')
                            <li style="display:none;" class="{!! Request::segment(1)==strtolower('nonperf')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('nonperf') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.nonperf', 2) !!}</a>
                            </li>
                            @permission('read-program')
                            <li class="{!! Request::segment(1)==strtolower('designation')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('designation') !!}"><i class="fa fa-bookmark"></i> Designations</a>
                            </li>
                            @endpermission
                            @endpermission
                        </ul>
                    </li>
                    @endpermission
                    @permission('facility-catalog')
                    <li class="{!! Request::segment(1)==strtolower('facility')?strtolower(trans('messages.active')):'' !!}">
                        <a href="{!! url('facility') !!}"><i class="fa fa-building"></i> {!! trans('messages.facility-catalog') !!}</a>
                    </li>
                    @endpermission
                    @permission('bulk-sms')
                    <li class="has-submenu{!! in_array(Request::segment(1), [strtolower('sms'), strtolower('settings'), strtolower('bulk'), strtolower('broadcast')])?' '.strtolower(trans('messages.active')):'' !!}">
                        <a href="#"><i class="fa fa-envelope"></i> {!! trans('messages.bulk-sms') !!}</a>
                        <ul class="list-unstyled">
                            <li class="{!! Request::segment(1)==strtolower('settings')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('settings') !!}"><i class="fa fa-bookmark"></i> {!! trans('messages.settings') !!}</a>
                            </li>
                            <!-- <li class="{!! Request::segment(2)==strtolower('broadcast')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('broadcast') !!}"><i class="fa fa-bookmark"></i> {!! trans('messages.broadcast') !!}</a>
                            </li> -->
                        </ul>
                    </li>
                    @endpermission
                    @permission('user-management')
                    <li class="has-submenu{!! in_array(Request::segment(1), [strtolower('user'), strtolower('role'), strtolower('permission'), strtolower('assign'), strtolower('self'), strtolower('participant')])?' '.strtolower(trans('messages.active')):'' !!}">
                        <a href="#"><i class="fa fa-users"></i> {!! trans('messages.user-management') !!}</a>
                        <ul class="list-unstyled">
                            @permission('read-user')
                            <li class="{!! Request::segment(1)==strtolower('user')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('user') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.user', 2) !!}</a>
                            </li>
                            <li class="{!! Request::segment(1)==strtolower('participant')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('participant') !!}"><i class="fa fa-bookmark"></i> {!! 'Participants' !!}</a>
                            </li>
                            @endpermission
                            @permission('read-role')
                            <li class="{!! Request::segment(1)==strtolower('role')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('role') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.role', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('read-permission')
                            <li class="{!! Request::segment(1)==strtolower('permission')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('permission') !!}"><i class="fa fa-bookmark"></i> {!! trans_choice('messages.permission', 2) !!}</a>
                            </li>
                            @endpermission
                            @permission('assign-role')
                            <!--<li class="{!! Request::segment(1)==strtolower('assign')?strtolower(trans('messages.active')):'' !!}">
                                <a href="{!! url('assign') !!}"><i class="fa fa-bookmark"></i> {!! trans('messages.assign-roles') !!}</a>
                            </li>-->
                            @endpermission
                        </ul>
                    </li>
                    @endpermission
                    @permission('facility-catalog')
                    <li class="{!! Request::segment(1)==strtolower('report')?strtolower(trans('messages.active')):'' !!}">
                        <a href="{!! url('report') !!}"><i class="fa fa-bar-chart-o"></i> {!! trans('messages.reports') !!}</a>
                    </li>
                    @endpermission
                    <li>
                    <a  href="{{ route('logout') }}" onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();"><i class="fa fa-sign-out" aria-hidden="true"></i>Sign Out</a>
                    </li>
                </ul>                
            </nav>
        </aside>

        <section class="content">
            <header class="top-head container-fluid">
                <nav class="navbar-default" role="navigation">
                    <div class="collapse navbar-toggleable-xs" id="collapsingNavbar">
                        <ul class="nav navbar-nav pull-right">
                            <li class="nav-item active">
                                <a class="nav-link text-primary" href="#">{!! Carbon::now(Config::get('cms.zone'))->toDayDateTimeString() !!}</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-success" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">{!! Auth::user()->name !!}</a>
                                <div class="dropdown-menu  dropdown-menu-right">
                                    <a class="dropdown-item  " href="{{ route('profile') }}">Profile</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item"  href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">Sign Out</a>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();"><i class="fa fa-sign-out" aria-hidden="true"></i></a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    {{ csrf_field() }}
                                </form>
                            </li>
                        </ul>
                    </div>
                </nav>
            <!-- Header Ends -->
            </header>

            <div class="warper container-fluid">
                @yield('content')
            </div>
            <!-- Warper Ends Here (working area) --><hr>
            <div id="footer" class="">
                <div class="container-fluid">
                    <p class="text-muted gem-h7">
                        <strong>
                            &copy; {!! date('Y').' '.Config::get('cms.copyright') !!}
                            <span style="float:right">
                                Designed by {!! Config::get('cms.designer') !!}&nbsp;&nbsp;&nbsp;
                                <a href="#" class="pull-right scrollToTop"><i class="fa fa-chevron-up"></i></a>
                            </span>
                        </strong>
                    </p>
                </div>
            </div>
        </section>
        <!-- JQuery v1.9.1 -->
        <script src="{{ asset('js/jquery-1.12.3.min.js') }}"></script>
        <script src="{{ asset('js/underscore-min.js') }}"></script>
        <script src="{{ asset('js/jquery-ui-min.js') }}"></script>
        <!-- Bootstrap -->
        <script src="{{ asset('js/bootstrap.min.js') }}"></script>
        <!-- Globalize -->
        <script src="{{ asset('js/globalize.min.js') }}"></script>

        <!-- NanoScroll -->
        <script src="{{ asset('js/jquery.nicescroll.min.js') }}"></script>
        <!-- Vue JS -->
        <script src="{{ asset('js/vue.js') }}"></script>
        <script src="{{ asset('js/vue-resource.min.js') }}"></script>
        <script src="{{ asset('js/vee-validate.js') }}"></script>

        <script>
            Vue.use(VeeValidate); // good to go. 
        </script>
        <!-- Toastr -->
        <script src="{{ asset('js/toastr.min.js') }}"></script>

        <script type="text/javascript" src="{{ URL::asset('js/script.js') }} "></script>

        <script type="text/javascript" src="{{ URL::asset('js/moment.min.js') }} "></script>        
        <!-- Custom JQuery -->
        <script src="{{ asset('js/custom.js') }}"></script>

        <script src="{{ asset('js/sweetalert.min.js') }}"></script>

        @if(Request::segment(1)==strtolower('event'))
        <script src="{{ asset('controllers/event.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('role'))
        <script src="{{ asset('controllers/role.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('option'))
        <script src="{{ asset('controllers/option.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('program'))
        <script src="{{ asset('controllers/program.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('round'))
        <script src="{{ asset('controllers/round.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('field'))
        <script src="{{ asset('controllers/field.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('set'))
        <script src="{{ asset('controllers/set.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('shipper'))
        <script src="{{ asset('controllers/shipper.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('facility'))
        <script src="{{ asset('controllers/facility.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('user'))
        <script src="{{ asset('controllers/user.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('participant'))
        <script src="{{ asset('controllers/participant.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('material'))
        <script src="{{ asset('controllers/material.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('panel'))
        <script src="{{ asset('controllers/panel.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('expected'))
        <script src="{{ asset('controllers/expected.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('shipment'))
        <script src="{{ asset('controllers/shipment.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('result'))        
        <script src="{{ asset('js/jspdf.debug.js') }}"></script>       
        <script src="{{ asset('js/jspdf.plugin.text-align.js') }}"></script>
        <script src="{{ asset('controllers/result.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('permission'))
        <script src="{{ asset('controllers/permission.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('assign'))
        <script src="{{ asset('controllers/assign.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('broadcast'))
        <script src="{{ asset('controllers/broadcast.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('nonperf'))
        <script src="{{ asset('controllers/nonperf.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('lot'))
        <script src="{{ asset('controllers/lot.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('signup'))
        <script src="{{ asset('controllers/signup.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('report'))
        <script src="{{ asset('js/highcharts.js') }}"></script>
        <script src="{{ asset('js/exporting.js') }}"></script>
        <script src="{{ asset('controllers/report.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('bulk') || Request::segment(1)==strtolower('settings'))
        <script src="{{ asset('controllers/bulk.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('profile'))
        <script src="{{ asset('controllers/profile.js') }}"></script>
        @elseif(Request::segment(1)==strtolower('designation'))
        <script src="{{ asset('controllers/designation.js') }}"></script>
        @endif
</body>
</html>