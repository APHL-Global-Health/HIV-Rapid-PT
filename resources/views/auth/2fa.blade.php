<!doctype html>
<html lang="en" class="no-js">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<link rel="icon" type="image/x-icon" href="{{ Config::get('cms.favicon') }}">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="theme-color" content="#3e454c">
    <meta id="token" name="token" value="{{ csrf_token() }}">  

	<title>{!! Config::get('cms.name') !!}</title>

	<!-- Font awesome -->
    <link rel="stylesheet" href="{{ asset('css/font-awesome.css') }}">
    <!-- Bootstrap core CSS -->
        <link href="{{ asset('css/bootstrap.css') }}" rel="stylesheet">
    <!-- Custom Font -->
    <link rel="stylesheet" href="{{ asset('css/font.css') }}">
    <!-- Custom Styling -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <!-- Toastr Styling -->
    <link href="{{ asset('css/toastr.min.css') }}" rel="stylesheet">
    <!-- Sweet Alert Styling -->
    <link href="{{ asset('css/sweetalert.css') }}" rel="stylesheet">
</head>

<body>
	<div class="register-page" id="manage-registration" style="padding-top:20px;">
        <div class="card col-md-10" style="margin:auto; float:none">
			<div class="card-block">
				<div class="row" style="padding:20px">
					<div class="col-md-12 text-md-center">
						<img src="{{ '../'.Config::get('cms.logo') }}" height="50px">
						<h5 class="text-warning"><strong>{!! Config::get('cms.name') !!}</strong></h5>
                        <h5 class="text-primary"><strong>Phone number Verification</strong></h5>
                        <h6 class="text-muted"><strong>Enter the verification token sent to your phone number from Nat-HIVPT</strong></h6>
					</div>
				</div>
				<form method="POST" enctype="multipart/form-data" v-on:submit.prevent="verifyPhone" id="phone_verification">
					<!-- Verify Phone -->
					<div class="col-md-offset-1 col-md-10 text-md-center">                      
						<div class="form-group row">
                            @if (session()->has('info'))
                                <div class="alert alert-info">
                                    <p>{!! session('info') !!}</p>
                                </div>
                            @endif
                            @if (session()->has('warning'))
                                <div class="alert alert-warning">
                                    <p>{!! session('warning') !!}</p>
                                </div>
                            @endif
                            @if (session()->has('success'))
                                <div class="alert alert-success">
                                    <p>{!! session('success') !!}</p>
                                </div>
                            @endif
                            <label class="col-sm-3 form-control-label"  :class="{'help is-danger': errors.has('name') }" for="name">Verification Code:</label>
                            <div class="col-sm-4" :class="{ 'control': true }">
                                <input v-validate="'required|numeric'" class="form-control" :class="{'input': true, 'is-danger': errors.has('code') }" name="code" id="code" type="text" placeholder="" v-model="verification.code"/>
                                <span v-show="errors.has('code')" class="help is-danger">@{{ errors.first('code') }}</span>
                            </div>
                            <div class="col-sm-2">
                                <button class="btn btn-nephritis btn-block"><strong><i class="fa fa-check"></i> Verify</strong></button>                            
                            </div>
                </form>

                            <div class="col-sm-3">
                                <button class="btn btn-midnight-blue btn-block" v-on:click.prevent="resendVerificationCode"><strong><i class="fa fa-refresh" ></i> Resend Code</strong></button>                            
                            </div>
                        </div>
                        <div class="card card-outline-success text-center">
                            <div class="card-block">
                                <blockquote class="card-blockquote text-white">
                                    <strong>We've sent a welcome email to your email address with a link to activate your account. If you cannot fine the mail, check the Junk/Spam folder!</strong>
                                </blockquote>
                            </div>
                        </div>
                    </div>
			</div>
            <div class="row" style="padding:20px">
                <div class="col-md-12 text-md-center">
                    <br >
                    Designed for <a href="http://www.nphls.or.ke">NPHL</a> by <a href="//www.ilabafrica.ac.ke">@iLabAfrica</a>
                </div>
            </div>
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
    </script>
    <!-- Toastr -->
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <!-- Sweet Alert -->
    <script src="{{ asset('js/sweetalert.min.js') }}"></script>
    <script src="{{ asset('controllers/signup.js') }}"></script>
</html>
