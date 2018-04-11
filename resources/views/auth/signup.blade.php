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
					<div class="col-md-12  text-md-center">
						<img src="{{ '../'.Config::get('cms.logo') }}" height="50px">
						<h5 class="text-primary">{!! Config::get('cms.name') !!}</h5>
					</div>
				</div>
				<form method="POST" enctype="multipart/form-data" v-on:submit.prevent="createParticipant" id="self_registration">
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
					<!-- Begin form fields -->
					<div v-cloak class="col-md-6">
						<div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('first name') }" for="first name">First Name:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required|alpha_spaces'" class="form-control" :class="{'input': true, 'is-danger': errors.has('first name') }" name="first name" type="text" v-model="newParticipant.fname"/>
                                <span v-show="errors.has('first name')" class="help is-danger">@{{ errors.first('first name') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('middle name') }" for="middle name">Middle Name:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'alpha_spaces'" class="form-control" :class="{'input': true, 'is-danger': errors.has('middle name') }" name="middle name" type="text" v-model="newParticipant.oname"/>
                                <span v-show="errors.has('middle name')" class="help is-danger">@{{ errors.first('middle name') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('last_name') }" for="last_name">Last Name:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required|alpha_spaces'" class="form-control" :class="{'input': true, 'is-danger': errors.has('last_name') }" name="last_name" type="text" v-model="newParticipant.last_name"/>
                                <span v-show="errors.has('last_name')" class="help is-danger">@{{ errors.first('last_name') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('gender') }" for="tester id">Gender:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <div class="form-radio radio-inline" v-for="sex in sexes">
                                    <label class="form-radio-label">
                                        <input v-validate="'required'" type="radio" name="gender" :value="sex.name" :class="{'input': true, 'is-danger': errors.has('gender') }" v-model="newParticipant.gender">
                                        @{{ sex.title }}
                                    </label>
                                </div>
                                <span v-show="errors.has('gender')" class="help is-danger">@{{ errors.first('gender') }}</span>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('phone number') }" for="phone number">Phone Number:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required|digits:10'" class="form-control" :class="{'input': true, 'is-danger': errors.has('phone number') }" name="phone number" type="text" v-model="newParticipant.phone"/>
                                <span v-show="errors.has('phone number')" class="help is-danger">@{{ errors.first('phone number') }}</span>
                                <span v-if="formErrors['phone']" class="error text-danger">@{{ formErrors['phone'] }}</span>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('email') }" for="email">Email:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required|email'" class="form-control" :class="{'input': true, 'is-danger': errors.has('email') }" name="email" type="text" v-model="newParticipant.email"/>
                                <span v-show="errors.has('email')" class="help is-danger">@{{ errors.first('email') }}</span>
                                <span v-if="formErrors['email']" class="error text-danger">@{{ formErrors['email'] }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('designation') }" for="designation">Designation:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <select v-validate="'required'" class="form-control c-select" name="designation" :class="{'input': true, 'is-danger': errors.has('designation') }" v-model="newParticipant.designation">
                                    <option selected></option>
                                    <option v-for="des in designations" :value="des.name">@{{ des.title }}</option>
                                </select>
                                <span v-show="errors.has('designation')" class="help is-danger">@{{ errors.first('designation') }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-cloak class="col-md-6">
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('program') }" for="program">Program:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <select v-validate="'required'" class="form-control c-select" name="program" :class="{'input': true, 'is-danger': errors.has('program') }" v-model="newParticipant.program">
                                    <option selected></option>
                                    <option v-for="prog in programs" :value="prog.id">@{{ prog.value }}</option>
                                </select>
                                <span v-show="errors.has('program')" class="help is-danger">@{{ errors.first('program') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('mfl code') }" for="mfl code">MFL Code:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required|numeric'" class="form-control" :class="{'input': true, 'is-danger': errors.has('mfl code') }" name="mfl code" type="text" v-model="newParticipant.mfl_code" @change="fetchFacility" id="mfl" />
                                <span v-show="errors.has('mfl code')" class="help is-danger">@{{ errors.first('mfl code') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('facility name') }" for="facility name">Facility Name:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required'" class="form-control" :class="{'input': true, 'is-danger': errors.has('facility name') }" name="facility name" type="text"  v-model="newParticipant.facility" readonly="true" />
                                <span v-show="errors.has('facility name')" class="help is-danger">@{{ errors.first('facility name') }}</span>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('sub county') }" for="sub county">Sub County:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required'" class="form-control" :class="{'input': true, 'is-danger': errors.has('sub county') }" name="sub county" type="text"  v-model="newParticipant.sub_county" readonly="true" />
                                <span v-show="errors.has('sub county')" class="help is-danger">@{{ errors.first('sub county') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('county') }" for="county">County:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required'" class="form-control" :class="{'input': true, 'is-danger': errors.has('county') }" name="county" type="text"  v-model="newParticipant.county" readonly="true" />
                                <span v-show="errors.has('county')" class="help is-danger">@{{ errors.first('county') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('in-charge') }" for="in-charge">In Charge:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required|alpha_spaces'" class="form-control" :class="{'input': true, 'is-danger': errors.has('in-charge') }" name="in-charge" type="text"  v-model="newParticipant.in_charge"/>
                                <span v-show="errors.has('in-charge')" class="help is-danger">@{{ errors.first('in-charge') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('in-charge email') }" for="in-charge email">In Charge Email:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required|email'" class="form-control" :class="{'input': true, 'is-danger': errors.has('in-charge email') }" name="in-charge email" type="text"  v-model="newParticipant.in_charge_email"/>
                                <span v-show="errors.has('in-charge email')" class="help is-danger">@{{ errors.first('in-charge email') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('in-charge phone') }" for="in-charge phone">In Charge Phone:</label>
                            <div class="col-sm-8" :class="{ 'control': true }">
                                <input v-validate="'required|digits:10'" class="form-control" :class="{'input': true, 'is-danger': errors.has('in-charge phone') }" name="in-charge phone" type="text"  v-model="newParticipant.in_charge_phone"/>
                                <span v-show="errors.has('in-charge phone')" class="help is-danger">@{{ errors.first('in-charge phone') }}</span>
                            </div>
                        </div>
					</div>
					<div class="form-group row">
						<div class="col-md-offset-2 col-md-10">
							<button class="btn btn-nephritis btn-block">REGISTER</button>							
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>

    <!-- Verify Phone -->
    <div class="modal fade" id="verify-phone" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" id="myModalLabel">Edit Option</h4>
                </div>
                <div class="row">
                    <div class="modal-body">

                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="verifyPhone">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('code') }" for="title">Code:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input v-validate="'required|numeric'" class="form-control" :class="{'input': true, 'is-danger': errors.has('code') }" name="code" type="text" placeholder=""/>
                                        <span v-show="errors.has('code')" class="help is-danger">@{{ errors.first('code') }}</span>
                                    </div>
                                </div>
                                <div class="form-group row col-sm-offset-4 col-sm-8">
                                    <button class="btn btn-sm btn-danger"><i class='fa fa-plus-circle'></i> Verify</button>
                                </div>
                            </div>
                        </form>
                    </div>
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
