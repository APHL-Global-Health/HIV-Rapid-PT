<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
// Authentication routes...
Route::get('login', 'Auth\LoginController@getLogin');

Auth::routes();

Route::get('signup', function () {
    return view('auth.signup');
});

Route::get('/2fa', 'Auth\RegisterController@twoFa');

Route::get('/verified', function () {
    return view('auth.verified');
});

Route::get('/email/verify/resend', 'Auth\RegisterController@resend');
Route::get('/email/verify/{code}', 'UserController@emailVerification');

Route::post('/token', 'UserController@phoneVerification');

Route::get("/sex", array(
    "as"   => "sex.fetch",
    "uses" => "UserController@sex"
));

Route::get("/cnts", array(
    "as"   => "cnts.fetch",
    "uses" => "FacilityController@counties"
));

Route::get("/progs", array(
    "as"   => "programs.fetch",
    "uses" => "ProgramController@programs"
));

Route::get("/des", array(
    "as"   => "designations.fetch",
    "uses" => "UserController@designations"
));

Route::post("/register", array(
    "as"   => "self.register",
    "uses" => "Auth\RegisterController@register"
));

Route::get('dashboard', function () {
    return view('landing');
});

/*Route::get('/', 'DashboardController@manageDash');
Route::resource('/','DashboardController');
Route::get("dash/ge", array(
    "as"   => "enrolment.gender",
    "uses" => "DashboardController@gender"
));
Route::get("dash/pr", array(
    "as"   => "enrolment.program",
    "uses" => "DashboardController@program"
));*/
Route::group(['middleware' => 'auth'], function()
{
    Route::get('/', function () {
        return view('welcome');
    });
    Route::get('welcome', function () {
        return view('welcome');
    });
    Route::get('home', function () {
        return view('welcome');
    });

    Route::get('panel', 'PanelController@managePanel');
    Route::resource('vuepanels','PanelController');
    Route::any('vuepanels/{id}/restore','PanelController@restore');

    Route::get('lot', 'LotController@manageLot');
    Route::resource('vuelots','LotController');
    Route::any('vuelots/{id}/restore','LotController@restore');

    Route::get('event', 'EventController@manageEvent');
    Route::resource('vueevents','EventController');

    Route::get('role', 'RoleController@manageRole');
    Route::resource('vueroles','RoleController');
    Route::any('vueroles/{id}/restore','RoleController@restore');

    Route::get('option', 'OptionController@manageOption');
    Route::resource('vueoptions','OptionController');
    Route::any('vueoptions/{id}/restore','OptionController@restore');

    Route::get('set', 'SetController@manageSet');
    Route::resource('vuesets','SetController');
    Route::any('vuesets/{id}/restore','SetController@restore');

    Route::get('program', 'ProgramController@manageProgram');
    Route::resource('vueprograms','ProgramController');
    Route::any('vueprograms/{id}/restore','ProgramController@restore');

    Route::get('round', 'RoundController@manageRound');
    Route::resource('vuerounds','RoundController');
    Route::any('vuerounds/{id}/restore','RoundController@restore');

    Route::get('field', 'FieldController@manageField');
    Route::resource('vuefields','FieldController');
    Route::any('vuefields/{id}/restore','FieldController@restore');

    Route::get('facility', 'FacilityController@manageFacility');
    Route::resource('vuefacilitys','FacilityController');
    Route::any('vuefacilitys/{id}/restore','FacilityController@restore');
    Route::get('search_facility',array('as'=>'search_facility','uses'=>'FacilityController@search_facility'));

    Route::get('user', 'UserController@manageUser');
    Route::resource('vueusers','UserController');
    Route::any('vueusers/{id}/restore','UserController@restore');

    Route::get('shipper', 'ShipperController@manageShipper');
    Route::resource('vueshippers','ShipperController');
    Route::any('vueshippers/{id}/restore','ShipperController@restore');

    Route::get('material', 'MaterialController@manageMaterial');
    Route::resource('vuematerials','MaterialController');
    Route::any('vuematerials/{id}/restore','MaterialController@restore');

    //Route::get('item', 'ItemController@manageItem');
    //Route::resource('vueitems','ItemController');

    Route::get('expected', 'ExpectedController@manageExpected');
    Route::resource('vueexpecteds','ExpectedController');
    Route::any('vueexpecteds/{id}/restore','ExpectedController@restore');

    Route::get('shipment', 'ShipmentController@manageShipment');
    Route::resource('vueshipments','ShipmentController');
    Route::any('vueshipments/{id}/restore','ShipmentController@restore');

    Route::get('receipt', 'ReceiptController@manageReceipt');
    Route::resource('vuereceipts','ReceiptController');
    Route::any('vuereceipts/{id}/restore','ReceiptController@restore');

    Route::get('nonperf', 'NonperformanceController@manageNonperformance');
    Route::resource('vuenonperfs','NonperformanceController');
    Route::any('vuenonperfs/{id}/restore','NonperformanceController@restore');

    //Route::get('manage-vue', 'VueItemController@manageVue');
    //Route::resource('vueitems','VueItemController');


    Route::get("/assign", array(
        "as"   => "role.assign",
        "uses" => "RoleController@manageAssignment"
    ));
   
    Route::post("/assign", array(
        "as"   => "role.assign",
        "uses" => "RoleController@saveUserRoleAssignment"
    ));
    Route::get("/st", array(
        "as"   => "st.fetch",
        "uses" => "ShipperController@options"
    ));

    Route::get("/mt", array(
        "as"   => "mt.fetch",
        "uses" => "MaterialController@options"
    ));

    Route::get("/mat", array(
        "as"   => "mat.fetch",
        "uses" => "PanelController@materials"
    ));

    Route::get("/rnds", array(
        "as"   => "rnds.fetch",
        "uses" => "RoundController@rounds"
    ));

    Route::get("/rndsDone", array(
        "as"   => "rnds.fetch",
        "uses" => "RoundController@roundsDone"
    ));

    Route::get("/itms", array(
        "as"   => "itms.fetch",
        "uses" => "ExpectedController@items"
    ));

    Route::get("/rslts", array(
        "as"   => "rslts.fetch",
        "uses" => "ExpectedController@options"
    ));

    Route::get("/con_subs/{id?}", array(
        "as"   => "cons.subs",
        "uses" => "FacilityController@consignment"
    ));

    Route::get("/fclts/{id}", array(
        "as"   => "facilities.fetch",
        "uses" => "FacilityController@facilities"
    ));

    Route::get("/shpprs/{id}", array(
        "as"   => "shippers.fetch",
        "uses" => "ShipperController@shippers"
    ));

    Route::get("/rng", array(
        "as"   => "ranges.fetch",
        "uses" => "UserController@ranges"
    ));

    Route::get("/reasons", array(
        "as"   => "reasons.fetch",
        "uses" => "NonperformanceController@reasons"
    ));

    Route::get("/lots", array(
        "as"   => "lots.fetch",
        "uses" => "LotController@lots"
    ));
    

    Route::get('settings', 'BulkSMSController@manageSettings');

    Route::get('broadcast', 'BulkSMSController@manageBroadcast');
    Route::resource('vuebroadcasts','BulkSMSController');
    
    //  Save sms gateway username and api-key
    Route::post("/bulk/api", array(
        "as"   => "bulk.api",
        "uses" => "BulkSMSController@api"
    ));
    Route::get("/bulk/key", array(
        "as"   => "bulk.key",
        "uses" => "BulkSMSController@key"
    ));
    //  Form for composing and sending SMS
    Route::get("/bulk/compose", array(
        "as"   => "bulk.compose",
        "uses" => "BulkSMSController@compose"
    ));
    Route::post("/bulk/send", array(
        "as"   => "bulk.send",
        "uses" => "BulkSMSController@broadcast"
    ));
    Route::get("/bulk/broadcast", array(
        "as"   => "bulk.broadcast",
        "uses" => "BulkSMSController@bulk"
    ));
    Route::get("/sms/{id}", array(
        "as"   => "sms.bulk",
        "uses" => "BulkSMSController@sms"
    ));
    //  Receive shipment
    Route::post("/receive", array(
        "as"   => "shipment.receive",
        "uses" => "ShipmentController@receive"
    ));
    //  Distribute shipment
    Route::post("/distribute", array(
        "as"   => "shipment.distribute",
        "uses" => "ShipmentController@distribute"
    ));
    //  Enrol participanets
    Route::post("/enrol", array(
        "as"   => "enrol.participants",
        "uses" => "RoundController@enrol"
    ));
    //  Enrolled participanets
    Route::get("/enrolled/{id}", array(
        "as"   => "enrolled.participants",
        "uses" => "UserController@enrolled"
    ));

    Route::get('result', 'ResultController@manageResult');
    Route::resource('vueresults','ResultController');
    Route::any('vueresults/{id}/restore','ResultController@restore');
    Route::get("/pt/{id}", array(
        "as"   => "pt.fetch",
        "uses" => "ResultController@edit"
    ));
    Route::any("/verify_results", array(
        "as"   => "verify_results",
        "uses" => "ResultController@verify"
    ));

    Route::get("/form", array(
        "as"   => "fields.fetch",
        "uses" => "QuestionnaireController@fetch"
    ));

    Route::get("/frmSets", array(
        "as"   => "fields.fetch",
        "uses" => "QuestionnaireController@fetch"
    ));

    Route::get('permission', 'PermissionController@managePermissions');
    Route::resource('vuepermissions','PermissionController');

    Route::get('assign', 'AssignmentController@manageAssignments');
    Route::resource('vueassigns','AssignmentController');

     Route::any("/assignParticipantRole", array(
        "as"   => "assignParticipantRole",
        "uses" => "AssignmentController@assignParticipantRole"
    ));

    Route::get("/ntfctns", array(
        "as"   => "notifications.fetch",
        "uses" => "NotificationController@fetch"
    ));
    Route::get("/tmplt/{id}", array(
        "as"   => "template.fetch",
        "uses" => "NotificationController@template"
    ));
    Route::get("/quest", array(
        "as"   => "questionnaire.fetch",
        "uses" => "QuestionnaireController@quest"
    ));
    Route::get("/preceed", array(
        "as"   => "sets.fetch",
        "uses" => "SetController@sets"
    ));
    Route::get("/flds", array(
        "as"   => "fields.fetch",
        "uses" => "FieldController@fields"
    ));
    Route::get("/tags", array(
        "as"   => "tags.fetch",
        "uses" => "FieldController@tags"
    ));
    Route::get("/opt", array(
        "as"   => "options.fetch",
        "uses" => "OptionController@options"
    ));
    Route::get("/parts", array(
        "as"   => "participants.fetch",
        "uses" => "UserController@forEnrol"
    ));
    Route::get("/frmfld/{id}", array(
        "as"   => "frmfld.fetch",
        "uses" => "FieldController@edit"
    ));
    Route::get("/priv", array(
        "as"   => "roles.fetch",
        "uses" => "RoleController@roles"
    ));
    Route::get("/privs", array(
        "as"   => "privs.fetch",
        "uses" => "RoleController@usrRoles"
    ));
    Route::get('report', 'ReportController@manageReport');
    Route::resource('vuereports','ReportController');

    Route::get("/rdata", array(
        "as"   => "lots.fetch",
        "uses" => "LotController@lots"
    ));

    //  Picked consignments
    Route::get("/consignments/{id}", array(
        "as"   => "picked.consignments",
        "uses" => "ShipmentController@consignments"
    ));

    //  Get feedback
    Route::get("/feedback/{id}", array(
        "as"   => "feedback.fetch",
        "uses" => "ResultController@feedback"
    ));

    //  Export summary
    Route::any('/review/summary/{id}/export', array(
        "as"    =>  "report.summary.export",
        "uses"  =>  "ReportController@download"
    ));
    //  Get durations
    Route::get('/duration', array(
        "as"    =>  "round.durations",
        "uses"  =>  "RoundController@durations"
    ));
    //  Batch enrolment
    Route::post("/batch/enrol", array(
        "as"   => "batch.enrolment",
        "uses" => "RoundController@batchRegisterAndEnrol"
    ));
    //  Batch registration
    Route::post('/batch/register', array(
        "as"    =>  "batch.registration",
        "uses"  =>  "UserController@batchRegistration"
    ));

    //  Batch facility import
    Route::post('/batch/facilities', array(
        "as"    =>  "batch.import",
        "uses"  =>  "FacilityController@batchImport"
    ));

    //  import users list
    Route::post('/import/users', array(
        "as"    =>  "import.users",
        "uses"  =>  "UserController@importUserList"
    ));

    Route::get("/download/{id}/{status?}", array(
        "as"   => "testers.download",
        "uses" => "RoundController@testerSummary"
    ));

    Route::any("/approve/{id}", array(
        "as"   => "approve.participant",
        "uses" => "ParticipantController@approve"
    ));
    Route::put("/denyUserVerification/{id}", array(
        "as"   => "denyUserVerification.participant",
        "uses" => "ParticipantController@denyUserVerification"
    ));

    Route::get('designation', 'DesignationController@manageDesignation');
    Route::resource('vuedesignations','DesignationController');
    Route::any('vuedesignations/{id}/restore','DesignationController@restore');

    Route::get('participant', 'ParticipantController@manageParticipant');
    Route::resource('vueparticipants','ParticipantController');
    Route::any('vueparticipants/{id}/restore','ParticipantController@restore');

    Route::get('api/search_role',['as'=>'role.search', 'uses'=>'RoleController@index']);
    Route::get('api/search_material',['as'=>'material.search', 'uses'=>'MaterialController@index']);
    Route::get('api/search_option',['as'=>'option.search', 'uses'=>'OptionController@index']);
    Route::get('api/search_program',['as'=>'program.search', 'uses'=>'ProgramController@index']);
    Route::get('api/search_result',['as'=>'result.search', 'uses'=>'ResultController@index']);
    Route::get('api/search_round',['as'=>'round.search', 'uses'=>'RoundController@index']);
    Route::get('api/search_set',['as'=>'set.search', 'uses'=>'SetController@index']);
    Route::get('api/search_shipment',['as'=>'shipment.search', 'uses'=>'ShipmentController@index']);
    Route::get('api/search_shipper',['as'=>'shipper.search', 'uses'=>'ShipperController@index']);
    Route::get('api/search_user',['as'=>'user.search', 'uses'=>'UserController@index']);
    Route::get('api/search_panel',['as'=>'panel.search', 'uses'=>'PanelController@index']);
    Route::get('api/search_field',['as'=>'field.search', 'uses'=>'FieldController@index']);
    Route::get('api/search_facility',['as'=>'facility.search', 'uses'=>'FacilityController@index']);
    Route::get('api/search_expected',['as'=>'expected.search', 'uses'=>'ExpectedController@index']);
    Route::get('api/search_participant',['as'=>'participant.search', 'uses'=>'ParticipantController@index']);
    Route::get('api/search_nonperf',['as'=>'nonperf.search', 'uses'=>'NonperformanceController@index']);
    Route::get('api/search_parts',['as'=>'participants.search', 'uses'=>'UserController@forEnrol']);
    Route::get('api/search_designation',['as'=>'designation.search', 'uses'=>'DesignationController@index']);
});

Route::get("/subs/{id}", array(
    "as"   => "subs.fetch",
    "uses" => "FacilityController@subs"
));

Route::get("/mfls/{id}", array(
    "as"   => "mfls.fetch",
    "uses" => "FacilityController@mfls"
));

Route::get("/mfl/{id}", array(
    "as"   => "mfl.fetch",
    "uses" => "FacilityController@mfl"
));
Route::get("/resend/{phone}", array(
    "as"   => "resend.code",
    "uses" => "Auth\RegisterController@resend"
));

Route::get('profile', 'ProfileController@manageProfile')->name('profile');

Route::get("/user/profile", array(
    "as"   => "user.profile",
    "uses" => "ProfileController@index"
));

Route::post("/user/profile/update", array(
    "as"   => "user.profile.update",
    "uses" => "ProfileController@update"
));

Route::post("/user/password/update", array(
    "as"   => "user.password.update",
    "uses" => "ProfileController@updatePassword"
));

Route::post("/user/transfer/facility", array(
    "as"   => "user.transfer",
    "uses" => "ProfileController@transferUser"
));
Route::get("/workbook", array(
    "as"   => "participants.download",
    "uses" => "ParticipantController@testerSummary"
));

Route::get("/new_participants/{id}/{}", array(
    "as"   => "new.participants",
    "uses" => "RoundController@testerSummary"
));