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
            <th>Feedback</th>
            <th>Action</th>
        </tr>
        <tr v-for="result in results">
            <td>@{{ result.rnd }}</td>
            <td>@{{ result.tester }}</td>
            <td>
                <button v-if="result.panel_status==0" class="mbtn mbtn-raised mbtn-danger mbtn-xs">Not Checked</button>
                <button v-if="result.panel_status==1" class="mbtn mbtn-raised mbtn-warning mbtn-xs">Checked</button>
                <button v-if="result.panel_status==2" class="mbtn mbtn-raised mbtn-info mbtn-xs">Not Verified</button>
                <button v-if="result.panel_status==3" class="mbtn mbtn-raised mbtn-inverse mbtn-xs">Verified</button>
            </td>
            <td>
                <button v-if="result.feedback==0" class="mbtn mbtn-raised mbtn-success mbtn-xs">Satisfactory</button>
                <button v-if="result.feedback==1" class="mbtn mbtn-raised mbtn-primary mbtn-xs">UnSatisfactory</button>
            </td>
            <td>
            @permission('view-result')
                <button class="btn btn-sm btn-secondary" @click.prevent="viewResult(result)" ><i class="fa fa-reorder"></i> View</button>	
            @endpermission
            @permission('update-result')
                <button  v-if="result.panel_status!=3" class="btn btn-sm btn-primary" @click.prevent="editResult(result)" ><i class="fa fa-edit"></i> Edit</button>
            @endpermission
            @permission('delete-result')
                <button class="btn btn-sm btn-danger" @click.prevent="deleteResult(result)"><i class="fa fa-power-off"></i> Disable</button>
            @endpermission            
            @permission('print-result')
            <button v-if="result.panel_status==3" class="btn btn-sm btn-concrete" @click="printFeedback(result.id)"><i class="fa fa-print"></i> Print</button>
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
                                    <div class="form-group row" v-if="viewFormData.pt.panel_status!=3">
                                        <label class="col-sm-5 form-control-label" for="title"><b>Verification Comment:</b></label>
                                        <div class="col-sm-7">
                                            <textarea name="comment" class="form-control"> @{{dt.response}}</textarea>
                                        </div>
                                    </div>
                                    <hr v-if="viewFormData.pt.panel_status!=3">
                                    <div class="form-group row col-sm-offset-5 col-sm-7">
                                        <button v-if="viewFormData.pt.panel_status!=3" class="btn btn-sm btn-success "><i class='fa fa-check-circle'></i> Verify Results</button>&nbsp;
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
</div>
@endsection