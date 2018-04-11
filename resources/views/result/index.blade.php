@extends('app')
@section('content')
<div class="row">
    <div class="col-sm-12">
        <ol class="breadcrumb">
            <li><a href="{!! url('home') !!}"><i class="fa fa-home"></i> {!! trans('messages.home') !!}</a></li>
            <li class="active"><i class="fa fa-cubes"></i> {!! trans('messages.pt') !!}</li>
            <li class="active"><i class="fa fa-cube"></i> {!! trans_choice('messages.result', 2) !!}</li>
        </ol>
    </div>
</div>
<div class="" id="manage-result">
    <!-- Round Listing -->
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left col-md-6">
                <h5><i class="fa fa-book"></i> {!! trans_choice('messages.result', 2) !!}
        
                @permission('create-result')
                    <button type="button" class="btn btn-sm btn-belize-hole" data-toggle="modal" data-target="#create-result">
                        <i class="fa fa-plus-circle"></i>
                        {!! trans('messages.enter-result') !!}
                    </button>
                @endpermission
                    <a class="btn btn-sm btn-carrot" href="#" onclick="window.history.back();return false;" alt="{!! trans('messages.back') !!}" title="{!! trans('messages.back') !!}">
                        <i class="fa fa-step-backward"></i>
                        {!! trans('messages.back') !!}
                    </a>
                </h5>
            </div>
            <div class="col-md-2"></div>
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" placeholder="Search for..." v-model="query">
                    <span class="input-group-btn">
                        <button class="btn btn-secondary" type="button" @click="search()" v-if="!loading"><i class="fa fa-search"></i></button>
                        <button class="btn btn-secondary" type="button" disabled="disabled" v-if="loading">Searching...</button>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <table class="table table-bordered">
        <tr>
            <th>PT Round</th>
            <th>Participant</th>
            <th>Status</th>
            <th>Results</th>
            <th>Action</th>
        </tr>
        <tr v-for="result in results">
            <td>@{{ result.rnd }}</td>
            <td>@{{ result.tester }}</td>
            <td>
                <button v-if="result.panel_status==0" class="mbtn mbtn-raised mbtn-danger mbtn-xs">Not Checked</button>
                <button v-if="result.panel_status==1" class="mbtn mbtn-raised mbtn-warning mbtn-xs">Checked</button>
                <button v-if="result.panel_status==2" class="mbtn mbtn-raised mbtn-info mbtn-xs">Evaluated</button>
                <button v-if="result.panel_status==3" class="mbtn mbtn-raised mbtn-inverse mbtn-xs">Verified</button>
            </td>
            <td>
                <button v-if="result.feedback==0" class= "mbtn mbtn-raised mbtn-success mbtn-xs">Satisfactory</button>
                <button v-if="result.feedback==1" class="mbtn mbtn-raised mbtn-primary mbtn-xs">UnSatisfactory</button>
            </td>
            <td>
            @permission('view-result')
                <button class="btn btn-sm btn-secondary" v-if="result.panel_status==0 || result.panel_status==1" @click.prevent="viewResult(result)" ><i class="fa fa-reorder"></i> View</button>	
                <button class="btn btn-sm btn-secondary" v-if="result.panel_status==3" @click.prevent="showEvaluatedResults(result)" ><i class="fa fa-reorder"></i> View</button>                   
            @endpermission
            @permission('update-result')
                <button  v-if="result.panel_status==0" class="btn btn-sm btn-primary" @click.prevent="editResult(result)" ><i class="fa fa-edit"></i> Edit</button>
            @endpermission
            @permission('delete-result')
                <button class="btn btn-sm btn-danger" @click.prevent="deleteResult(result)"><i class="fa fa-power-off"></i> Disable</button>
            @endpermission 

            @permission('verify-result')
            <button v-if="result.panel_status==2" class="btn btn-sm btn-primary" @click.prevent="showEvaluatedResults(result)"><i class="fa fa-list"></i> Review</button>
            <button v-if="result.panel_status==2 && result.feedback==0" class="btn btn-sm btn-success" @click.prevent="quickVerifyEvaluatedResult(result.id)"><i class="fa fa-check-circle"></i> Verify</button>
            @endpermission 

            @permission('print-results')
            <a v-if="result.panel_status==3" class="btn btn-concrete" :href="'print_result/' +result.id"><i class="fa fa-print"></i> Print</a>
            @endpermission 
            </td>
        </tr>
    </table>
    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <li v-if="pagination.current_page > 1" class="page-item">
                <a class="page-link" href="#" aria-label="Previous"
                    @click.prevent="changePage(pagination.current_page - 1)">
                    <span aria-hidden="true">«</span>
                </a>
            </li>
            <li v-for="page in pagesNumber" class="page-item"
                v-bind:class="[ page == isActived ? 'active' : '']">
                <a class="page-link" href="#"
                    @click.prevent="changePage(page)">@{{ page }}</a>
            </li>
            <li v-if="pagination.current_page < pagination.last_page" class="page-item">
                <a class="page-link" href="#" aria-label="Next"
                    @click.prevent="changePage(pagination.current_page + 1)">
                    <span aria-hidden="true">»</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Enter Result Modal -->
    <div class="modal fade" id="create-result" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Test Results</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="createResult" id="analysis_results">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="col-sm-5 form-control-label" for="title">PT Round:</label>
                                    <div class="col-sm-7">
                                        <select class="form-control c-select" name="round_id">
                                            <option selected></option>
                                            <option v-for="round in roundsDone" :value="round.id">@{{ round.value }}</option>   
                                        </select>
                                        <span v-if="formErrors['round_id']" class="error text-danger">@{{ formErrors['round_id'] }}</span>
                                    </div>
                                </div>
                                <div v-for="frm in form">
                                    <p class="text-primary">@{{ frm.title }}</p>
                                    <hr>
                                    <div v-for="item in frm.fields">
                                        <div class="form-group row">
                                            <label class="col-sm-5 form-control-label" for="title">@{{ item.title }}:</label>
                                            <div class="col-sm-7">
                                                <div v-if="item.tag == 1">
                                                    <div class="form-checkbox checkbox-inline" v-for="option in item.options">
                                                        <label class="form-checkbox-label">
                                                            <input type="checkbox" :value="option.id" :name="'field_'+item.id">
                                                            @{{ option.title }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <div v-if="item.tag == 2">
                                                    <input type="date" :name="'field_'+item.id" class="form-control"/>
                                                </div>
                                                <div v-if="item.tag == 3">
                                                    <input type="email" :name="'field_'+item.id" class="form-control" />
                                                </div>
                                                <div v-if="item.tag == 4">
                                                    <input type="text" :name="'field_'+item.id" class="form-control" />
                                                </div>
                                                <div v-if="item.tag == 5">
                                                    <div class="form-radio radio-inline" v-for="option in item.options">
                                                        <label class="form-radio-label">
                                                            <input type="radio" :value="option.id" :name="'field_'+item.id" />
                                                            @{{ option.title }}
                                                        </label>
                                                    </div>
                                                    <!-- <input type="text" :name="'comment_'+item.id" class="form-control" /> -->
                                                </div>
                                                <div v-if="item.tag == 6">
                                                    <select class="form-control c-select" :name="'field_'+item.id">
                                                        <option selected></option>
                                                        <option v-for="option in item.options" :value="option.id">@{{ round.title }}</option>   
                                                    </select>
                                                </div>
                                                <div v-if="item.tag == 7">
                                                    <textarea :name="'field_'+item.id" class="form-control"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- <div class="form-group row" :class="'toggle_'+item.id" style="display:none;">
                                            <label class="col-sm-5 form-control-label text-danger font-weight-bold" for="title">Please Specify:</label>
                                            <div class="col-sm-7">
                                                <textarea :name="'field_'+item.id" class="form-control"></textarea>
                                            </div>
                                        </div> -->
                                    </div>
                                </div>
                                <div class="form-group row col-sm-offset-5 col-sm-7">
                                    <button class="btn btn-sm btn-success"><i class='fa fa-plus-circle'></i> Submit</button>
                                    <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Test Results Modal -->
    <div class="modal fade" id="edit-result" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Test Results</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="updateResult('update_results')" id="update_test_results" data-vv-validate="update_results">
                            <input type="hidden"  name="id" :value="frmData.pt"> 
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="col-sm-5 form-control-label" for="title">PT Round:</label>
                                    <div class="col-sm-7">
                                        <select class="form-control c-select" name="round_id">
                                            <option selected></option>
                                            <option v-for="round in rounds" v-if="frmData.round" v-bind="{ 'selected': round.id==frmData.round.id}" :value="round.id">@{{ round.value }}</option>   
                                        </select>
                                    </div>
                                </div>
                                <div v-for="frm in form">
                                    <p class="text-primary">@{{ frm.title }}</p>
                                    <hr>
                                    <div v-for="item in frm.fields">
                                        <div v-if="frmData" v-for="dt in frmData.results">
                                            <div class="form-group row" v-if="dt.field_id==item.id">
                                                <label class="col-sm-5 form-control-label" for="title">@{{ item.title }}:</label>
                                                <div class="col-sm-7">
                                                    <div v-if="item.tag == 1">
                                                        <div class="form-checkbox form-checkbox-inline" v-for="option in item.options">
                                                            <label class="form-checkbox-label">
                                                                <input type="checkbox" :value="option.id" :name="'field_'+item.id">
                                                                @{{ option.title }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div v-if="item.tag == 2">
                                                        <input type="date" :name="'field_'+item.id" class="form-control" :value="dt.response" />
                                                    </div>
                                                    <div v-if="item.tag == 3">
                                                        <input type="email" :name="'field_'+item.id" class="form-control" :value="dt.response" />
                                                    </div>
                                                    <div v-if="item.tag == 4">
                                                        <input type="text" :name="'field_'+item.id" class="form-control" :value="dt.response" />
                                                    </div>
                                                    <div v-if="item.tag == 5">

                                                        <div class="form-radio radio-inline" v-for="option in item.options">
                                                            <label class="form-radio-label">
                                                                <input type="radio" :value="option.id" :name="'field_'+item.id" v-bind="{ 'checked': option.id==dt.response}"/>
                                                                @{{ option.title }}
                                                            </label>
                                                        </div>
                                                        <input v-if="dt.response==4" type="text" :name="'comment_'+item.id" class="form-control" :value="dt.comment" />
                                                    </div>
                                                    <div v-if="item.tag == 6">
                                                        <select class="form-control c-select" :name="'field_'+item.id">
                                                            <option selected></option>
                                                            <option v-for="option in item.options" v-bind="{ 'selected': option.id==dt.response}" :value="option.id">@{{ round.title }}</option>   
                                                        </select>
                                                    </div>
                                                    <div v-if="item.tag == 7">
                                                        <textarea v-if="dt.field_id==item.id" :name="'field_'+item.id" class="form-control">@{{dt.response}}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row col-sm-offset-5 col-sm-7">
                                    <button class="btn btn-sm btn-success"><i class='fa fa-plus-circle'></i> Submit</button>
                                    <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- View Test Results Modal -->
    <div class="modal fade" id="view-result" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Test Results</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group row" v-if="viewFormData.round">
                                <label class="col-sm-5 form-control-label" for="title"><b>PT Round:</b></label>
                                <div class="col-sm-7">
                                    <div  v-for="round in rounds">
                                        <label class="form-label" v-if="round.id==viewFormData.round.id" >@{{ round.value }}</label>
                                    </div>
                                    <span v-if="formErrors['round_id']" class="error text-danger">@{{ formErrors['round_id'] }}</span>
                                </div>
                            </div>
                            <div v-for="frm in sets">
                                <p class="text-primary"><b>@{{ frm.title }}</b></p>
                                <hr>
                                <div v-if="viewFormData.results" v-for="dt in viewFormData.results">
                                    <div v-for="item in frm.fields">
                                        <div class="form-group row" v-if="dt.field_id==item.id">
                                            <label class="col-sm-5 form-control-label" for="title"><b>@{{ item.title }}:</b></label>
                                            <div class="col-sm-7">
                                                <div v-if="item.tag == 1">
                                                    <div class="form-checkbox form-checkbox-inline" v-for="option in item.options">
                                                        <label class="form-checkbox-label">
                                                            @{{ option.title }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <div v-if="item.tag == 2 ||item.tag == 3||item.tag == 4||item.tag == 7">
                                                    <label class="form-label" v-if="dt.field_id==item.id">@{{dt.response}}</label>
                                                </div>
                                                
                                                <div v-if="item.tag == 5||item.tag == 6">
                                                    <div v-if="dt.field_id==item.id"  v-for="option in item.options">
                                                        <label class="form-label" v-if="option.id==dt.response" >@{{ option.title }}<span v-if="dt.response == 4">@{{ '-'+dt.comment}}</span></label>
                                                    </div>
                                                </div>                                 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="verifyResult" id="verify_test_results">
                                <div v-if="viewFormData.pt">
                                    <input type="hidden" class="form-control" name="pt_id" :value="viewFormData.pt.id">
                                    <div class="form-group row" v-if="viewFormData.pt.panel_status==0">
                                        <label class="col-sm-5 form-control-label" for="title"><b>Verification Comment:</b></label>
                                        <div class="col-sm-7">
                                            <textarea name="comment" class="form-control"> @{{dt.response}}</textarea>
                                        </div>
                                        <p class="form-control">Once you verify, the document will be submitted to NPHL and you will not be able to change the results</p>
                                    </div>
                                    <hr v-if="viewFormData.pt.panel_status=0">
                                    <div class="form-group row col-sm-offset-5 col-sm-7">
                                        <button v-if="viewFormData.pt.panel_status==0" class="btn btn-sm btn-success "><i class='fa fa-check-circle'></i> Verify Results</button>&nbsp;
                                        <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
       <!-- Verify Evaluted Results Modal -->
    <div class="modal fade" id="view-evaluted-result" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Evaluated Test Results</h4>
                </div>
                <div class="modal-body" id="printpdf">
                    <div class="row">
                        <div class="col-md-12">    
                            <!-- Round details -->
                            <div class="row">
                                <div class="col-md-12">
                                   <table class="table table-bordered" id="partinfo">
                                        <tr class="text-center"><b>Participant Information </b></tr>
                                        <tr class="col-md-12">
                                            <td class="col-md-3"><b>Round</b></td>
                                            <td class="col-md-3">@{{evaluated_results.round_name}}</td>
                                            <td class="col-md-3"><b>County</b></td>
                                            <td class="col-md-3">@{{evaluated_results.county}}</td>
                                        </tr>
                                         <tr>
                                            <td class="col-md-3"><b>Tester ID</b></td>
                                            <td class="col-md-3">@{{evaluated_results.tester_id}}</td>
                                            <td class="col-md-3"><b>Sub County</b></td>
                                            <td class="col-md-3">@{{evaluated_results.sub_county}}</td>
                                        </tr>
                                         <tr>
                                            <td class="col-md-3"><b>Tester Name</b></td>
                                            <td class="col-md-3">@{{evaluated_results.user_name}}</td>
                                            <td class="col-md-3"><b>Facility</b></td>
                                            <td class="col-md-3">@{{evaluated_results.facility}}</td>
                                        </tr>
                                         <tr>
                                            <td class="col-md-3"><b>Program</b></td>
                                            <td class="col-md-3">@{{evaluated_results.program}}</td>
                                            <td class="col-md-3"><b>Facility MFL</b></td>
                                            <td class="col-md-3">@{{evaluated_results.mfl}}</td>
                                        </tr>
                                   </table>   
                                </div>
                            </div>
                            <!-- panel details -->
                            <div class="row">
                                <div class="col-md-12">
                                   <table class="table table-bordered">
                                        <tr class="text-center"> <b>Panel Information</b></tr>
                                        <tr>
                                            <td><b>Receive Date</b></td>
                                            <td>@{{evaluated_results.date_collected}}</td>                                            
                                        </tr>
                                         <tr>
                                            <td><b>Constituted Date</b></td>
                                            <td>@{{evaluated_results.date_prepared}}</td>
                                        </tr>
                                         <tr>
                                            <td><b>Tested Date</b></td>
                                            <td>@{{evaluated_results.expiry_date}}</td>
                                        </tr>
                                   </table>   
                                </div>
                            </div>
                            <!-- kit details -->
                            <div class="row">
                                <div class="col-md-12">
                                   <table class="table table-bordered">
                                        <tr> <b>Kit Information</b></tr>
                                        <tr>
                                            <th>Kit Name</th>                                           
                                            <th>Kit No</th>                                           
                                            <th>Kit Expiry Date</th>                                           
                                        </tr>
                                         <tr>
                                            <td>@{{evaluated_results.determine}}</td>
                                            <td>@{{evaluated_results.determine_lot_no}}</td>
                                            <td>@{{evaluated_results.determine_expiry_date}}</td>
                                        </tr>
                                        <tr>
                                            <td>@{{evaluated_results.firstresponse}}</td>
                                            <td>@{{evaluated_results.firstresponse_lot_no}}</td>
                                            <td>@{{evaluated_results.firstresponse_expiry_date}}</td>
                                        </tr>
                                         
                                   </table>   
                                </div>
                            </div>
                            <!-- Results details -->                            
                            <div class="row">
                                <div class="col-md-12">
                                   <table class="table table-bordered">
                                        <tr>
                                            <th>PT Sample ID</th>
                                            <th>Determine</th>
                                            <th>First Response</th>
                                            <th>Final Result</th>
                                            <th>Expected Result</th>
                                        </tr>                                        
                                        <tr>
                                            <td>@{{evaluated_results.sample_1}}</td>
                                            <td>@{{evaluated_results.pt_panel_1_kit1_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_1_kit2_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_1_final_results}}</td>
                                            <td class="text-uppercase">@{{evaluated_results.expected_result_1}}</td>
                                        </tr>
                                        <tr>
                                            <td>@{{evaluated_results.sample_2}}</td>
                                            <td>@{{evaluated_results.pt_panel_2_kit1_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_2_kit2_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_2_final_results}}</td>
                                            <td class="text-uppercase">@{{evaluated_results.expected_result_2}}</td>
                                        </tr>
                                        <tr>
                                            <td>@{{evaluated_results.sample_3}}</td>
                                            <td>@{{evaluated_results.pt_panel_3_kit1_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_3_kit2_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_3_final_results}}</td>
                                            <td class="text-uppercase">@{{evaluated_results.expected_result_3}}</td>
                                        </tr>
                                        <tr>
                                            <td>@{{evaluated_results.sample_4}}</td>
                                            <td>@{{evaluated_results.pt_panel_4_kit1_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_4_kit2_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_4_final_results}}</td>
                                            <td class="text-uppercase">@{{evaluated_results.expected_result_4}}</td>
                                        </tr>
                                        <tr>
                                            <td>@{{evaluated_results.sample_5}}</td>
                                            <td>@{{evaluated_results.pt_panel_5_kit1_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_5_kit2_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_5_final_results}}</td>
                                            <td class="text-uppercase">@{{evaluated_results.expected_result_5}}</td>
                                        </tr>
                                        <tr>
                                            <td>@{{evaluated_results.sample_6}}</td>
                                            <td>@{{evaluated_results.pt_panel_6_kit1_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_6_kit2_results}}</td>
                                            <td>@{{evaluated_results.pt_panel_6_final_results}}</td>
                                            <td class="text-uppercase">@{{evaluated_results.expected_result_6}}</td>
                                        </tr>
                                        <tr>
                                            <td><b>Panel Results</b></td>
                                            <td>@{{evaluated_results.feedback}}</td>
                                        </tr>
                                        <tr>
                                            <td><b>Overall Evaluation</b></td>
                                            <td>@{{evaluated_results.feedback}}</td>
                                        </tr>
                                   </table>
                                </div>
                            </div>  
                            <div class="row">
                                <div class="col-md-12">
                                   <table class="table table-bordered">
                                       <tr><b>Reasons For Unsatisfactory:   </b></tr>
                                       <tr>
                                           @{{evaluated_results.remark}}
                                       </tr>
                                   </table>
                                </div>
                            </div>                           
                            <div v-if="evaluated_results.panel_status==2">
                                <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="verifyEvaluatedResult(evaluated_results.pt_id)" id="verify_evaluated_test_results">
                                    <div v-if="evaluated_results.feedback">
                                        <input type="hidden" class="form-control" name="pt_id" :value="evaluated_results.pt_id">
                                        <div class="form-group row" v-if="evaluated_results.panel_status==2">
                                            <label class="col-sm-5 form-control-label" for="title"><b>Verification Comment:</b></label>
                                            <div class="col-sm-7">
                                                <textarea name="comment" class="form-control">@{{evaluated_results.feedback}}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row col-sm-offset-5 col-sm-7">
                                            <button  class="btn btn-sm btn-success "><i class='fa fa-check-circle'></i> Verify Evaluated Results</button>&nbsp;
                                            <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                             <div class="form-group row" v-else-if="evaluated_results.panel_status==3">                                
				<label class="col-sm-5 form-control-label" for="title"><b>Comments:</b></label>
                                <div class="col-sm-7 form-control">
                                    <p>@{{evaluated_results.pt_approved_comment}}</p>
                                    <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                                </div>                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
