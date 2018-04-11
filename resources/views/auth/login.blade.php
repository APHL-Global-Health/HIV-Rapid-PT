<!doctype html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" type="image/x-icon" href="{{ Config::get('cms.favicon') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="theme-color" content="#3e454c">

    <title>{!! Config::get('cms.name') !!}</title>

    <!-- Font awesome -->
    <link rel="stylesheet" href="{{ asset('css/font-awesome.css') }}">
    <!-- Bootstrap core CSS -->
    <link href="{{ asset('css/bootstrap.css') }}" rel="stylesheet">
    <!-- Custom Font -->
    <link rel="stylesheet" href="{{ asset('css/font.css') }}">
    <!-- Custom Styling -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <!-- Sweet Alert Styling -->
    <link href="{{ asset('css/sweetalert.css') }}" rel="stylesheet">
    <title>Kenya Serology Rapid HIV PT,Login</title>
    <style>
        .form-control:focus 
        {
            border-color: #18bc9c;
            outline: none;
            border-width:2px;
        }
        .form-control 
        {
            border-width:2px;
        }
        .loginArea
        {
            color:#333;
            margin-top:15px;
            padding: 15px;
            border-radius: 3px 
        }
    </style>  
</head>
<body>    
    <div class="login-page" id="sign-in" style="padding-top:20px;">
        <div class="card col-md-5" style="margin:auto; float:none">
            <div style="text-align:center;">
                <br>
                <img src="{{ '../'.Config::get('cms.logo') }}" height="75px;">
                <h3 class="form-signin-heading">
                    <span>Kenya Serology Rapid HIV PT</span>
                </h3>
            </div> 
            <div class="bs-callout bs-callout-info text-left">
                <h6 class="md-18">Getting Started</h6>
                <small>Login with your Email Address or PT Enrollment ID.</small>
            </div>
            <form class="mt form-signin" id="loginForm" Irole="form" method="POST" action="{{ route('login') }}">
                <!-- CSRF Token -->
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <!-- ./ csrf token -->
                @if($errors)
                    @if (count($errors) > 0)
                    <div class="alert alert-danger col-md-12">
                        <ul class="list-unstyled">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                @endif
                @if (session()->has('message'))
                    <div class="alert alert-danger">
                        <p>{!! session('message') !!}</p>
                    </div>
                @endif     
                <div class="form-group">
                    <label :class="{'help is-danger': errors.has('username') }" for="username/TesterID"><h6>Email / PT Enrollment ID</h6></label>
                    <input type="text" v-validate="'required'" class="form-control" :class="{'input': true, 'is-danger': errors.has('username') }"  name="username" placeholder="eg. me@mymail.com or 11695" data-toggle="tooltip" title="Enter Username or PT Enrollment ID" data-placement="top" data-trigger="hover" autofocus="">
                    <span v-show="errors.has('username')" class="help is-danger">@{{ errors.first('username') }}</span>
                </div>
                <div class="form-group">
                    <label :class="{'help is-danger': errors.has('password') }" for="password"><h6>Password</h6></label>
                    <input type="password" v-validate="'required'" class="form-control" :class="{'input': true, 'is-danger': errors.has('password') }" name="password" placeholder="eg.qwerty" data-toggle="tooltip" data-placement="top">
                    <span v-show="errors.has('password')" class="help is-danger">@{{ errors.first('password') }}</span>
                </div>
                <div class="form-group">
                    <button class="btn btn-primary btn-block" style="color:#fff;background-color:#2c3e50;border-color:#2c3e50" type="submit" name="signin" data-toggle="tooltip" data-placement="top" data-trigger="hover" title="Click to LogIn"  >LOG IN</button>
                </div>
                <div class="form-group form-check">
                    <div class="col-md-12">
                        <label class="form-check-label">
                            <input class="form-check-input" type="checkbox"> Remember me
                        </label>
                    </div>
		</div>
		<div class="form-group">
		    If you have forgotten your password, you can reset it by clicking the Lost Password link below.<br />
                    <a style="color:#18bc9c;" href="{{url('password/reset')}}" data-toggle="tooltip" data-placement="top" data-trigger="hover"><strong>Lost password?</strong></a>
			<br />
                </div>
                <div class="form-group">
                    <a class="btn btn-nephritis btn-block" onclick="confirmRegistration()" data-toggle="tooltip" data-placement="top" data-trigger="hover" title="Click to Register as a new participant"><strong>Register</strong></a>
                </div>
                <div>
                    Click on the Register button to register as a new participant.
                </div>
		<div class="form-group">
			<center><span><a style="color:#18bc9c;" href="http://nphls.or.ke/helpdesk/index.php?a=add"><strong>PT Help Desk</strong></a></span></center>
		</div>
            </form>
        </div>
    </div>    
</body>
<!-- Core JS -->
<script src="{{ asset('js/jquery-1.12.3.min.js') }}"></script>
<script src="{{ asset('js/jquery.js') }}"></script>
<!-- Bootstrap -->
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<!-- Vue JS -->
<script src="{{ asset('js/vue.js') }}"></script>
<script src="{{ asset('js/vue-resource.min.js') }}"></script>
<script src="{{ asset('js/vee-validate.js') }}"></script>
<script>
    Vue.use(VeeValidate); // good to go. 
    new Vue({
        el: '#sign-in'
    });
</script>
<!-- Sweet Alert -->
<script src="{{ asset('js/sweetalert.min.js') }}"></script>
<script type="text/javascript">
    $(function () {
       $('[data-toggle="tooltip"]').tooltip()
      });
    function confirmRegistration()
    {
        swal({
            title:"Have you been registered in the system before?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "No",
            cancelButtonText: "Yes",
            closeOnConfirm: false,
            closeOnCancel: false
        },
        function(isConfirm)
        {
            if (isConfirm) 
            {
                window.location.replace("/signup");
            } 
            else 
            {
                swal("PT Participation Prompt", "By getting to this page, you're already registered, please retrieve your enrollment ID from past reports or use the PT helpdesk above to proceed.", "success");
            }
        });
    }        
</script> 
</html>
