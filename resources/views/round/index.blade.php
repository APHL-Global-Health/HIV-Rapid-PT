@extends('app')
@section('content')
<div class="row">
    <div class="col-sm-12">
        <ol class="breadcrumb">
            <li><a href="{!! url('home') !!}"><i class="fa fa-home"></i> {!! trans('messages.home') !!}</a></li>
            <li class="active"><i class="fa fa-cubes"></i> {!! trans('messages.pt') !!}</li>
            <li class="active"><i class="fa fa-cube"></i> {!! trans_choice('messages.pt-round', 2) !!}</li>
        </ol>
    </div>
</div>
<div class="" id="manage-round">
    <!-- Round Listing -->
    <div class="row">
    @if (Session::has('message'))
            <div class="alert alert-info">{{ Session::get('message') }}</div>
        @endif
        <div class="col-lg-12 margin-tb">
            <div class="pull-left col-md-8">
                <h5><i class="fa fa-book"></i> {!! trans_choice('messages.pt-round', 2) !!}
        
                @permission('create-round')
                    <button type="button" class="btn btn-sm btn-belize-hole" data-toggle="modal" data-target="#create-round">
                        <i class="fa fa-plus-circle"></i>
                        {!! trans('messages.add') !!}
                    </button>
                @endpermission
                    <a class="btn btn-sm btn-carrot" href="#" onclick="window.history.back();return false;" alt="{!! trans('messages.back') !!}" title="{!! trans('messages.back') !!}">
                        <i class="fa fa-step-backward"></i>
                        {!! trans('messages.back') !!}
                    </a>
                @permission('enrol-participants')
                    <a style="display: none;" class="btn btn-sm btn-concrete" href="/Enrollment.xlsx">
                        <i class="fa fa-download"></i>
                        Worksheet
                    </a>
                @endpermission
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
            <th>Title</th>
            <th>Description</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Action</th>
        </tr>
        <tr v-for="round in rounds">
            <td>@{{ round.name }}</td>
            <td>@{{ round.description }}</td>
            <td>@{{ round.start_date }}</td>
            <td>@{{ round.end_date }}</td>
            <td>
            @permission('update-round')	
                <button v-bind="{ 'disabled': round.deleted_at}" class="btn btn-sm btn-primary" @click.prevent="editRound(round)"><i class="fa fa-edit"></i> Edit</button>
            @endpermission
            @permission('restore-round')
                <button v-if="round.deleted_at" class="btn btn-sm btn-success" @click.prevent="restoreRound(round)"><i class="fa fa-toggle-on"></i> Enable</button>
            @endpermission
            @permission('delete-round')
                <button v-if="!round.deleted_at" class="btn btn-sm btn-danger" @click.prevent="deleteRound(round)"><i class="fa fa-power-off"></i> Disable</button>
            @endpermission
            @permission('enrol-participants')
                <button v-if="!round.deleted_at" class="btn btn-sm btn-wet-asphalt" id="enrol" data-toggle="modal" data-target="#enrol-participants" style="display:none;" :data-fk="round.id" @click.prevent="loadParticipants(1)"><i class="fa fa-send"></i> Enrol</button>                
                <a v-if="!round.deleted_at" class="btn btn-sm btn-wet-asphalt" :href="'/download/' + round.id" id="enrolled" ><i class="fa fa-level-down"></i> Summary Workbook</a>
                <button v-if="!round.deleted_at" class="btn btn-sm btn-nephritis" @click.prevent="uploadSheet(round)"><i class="fa fa-level-up"></i> Upload Worksheet</button>
                <button v-if="!round.deleted_at" class="btn btn-sm btn-new-participants" id="enrol" data-toggle="modal" data-target="#enrol-participants" :data-fk="round.id"   @click.prevent="loadParticipants(round.id)"><i class="fa fa-book"></i> New Participants</button>                
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

    <!-- Create Round Modal -->
    <div class="modal fade" id="create-round" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Create Round</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="createRound('create_round')" id="create_round" data-vv-scope="create_round">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('create_round.name') }" for="name">Title:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input v-validate="'required|numeric'" class="form-control" :class="{'input': true, 'is-danger': errors.has('create_round.name') }" name="name" type="text" placeholder="" v-model="newRound.name" />
                                        <span v-show="errors.has('create_round.name')" class="help is-danger">@{{ errors.first('create_round.name') }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" for="description">Description:</label>
                                    <div class="col-sm-8">
                                        <textarea name="description" class="form-control" v-model="newRound.description"></textarea>
                                        <span v-if="formErrors['description']" class="error text-danger">@{{ formErrors['description'] }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" :class="{'help is-danger': errors.has('create_round.start date') }" for="start date">Start Date:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input v-validate="'required'" class="form-control" :class="{'input': true, 'is-danger': errors.has('create_round.start date') }" name="start date" type="date" placeholder="" v-model="newRound.start_date" id="start_date" />
                                        <span v-show="errors.has('create_round.start date')" class="help is-danger">@{{ errors.first('create_round.start date') }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" :class="{'help is-danger': errors.has('create_round.end date') }" for="end date">End Date:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input v-validate="'required|after:start date'" class="form-control" :class="{'input': true, 'is-danger': errors.has('create_round.end date') }" name="end date" type="date" placeholder="" v-model="newRound.end_date" id="end_date" />
                                        <span v-show="errors.has('create_round.end date')" class="help is-danger">@{{ errors.first('create_round.end date') }}</span>
                                    </div>
                                </div>
                                <div class="form-group row col-sm-offset-4 col-sm-8">
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

    <!-- Edit Round Modal -->
    <div class="modal fade" id="edit-round" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Edit Round</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="updateRound(fillRound.id, 'update_round')" data-vv-scope="update_round">
                            <div class="col-md-12">
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('update_round.round number') }" for="title">Number:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input v-validate="'required|numeric'" class="form-control" :class="{'input': true, 'is-danger': errors.has('update_round.round number') }" name="round number" type="text" placeholder="" v-model="fillRound.name" />
                                        <span v-show="errors.has('update_round.round number')" class="help is-danger">@{{ errors.first('update_round.round number') }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" for="description">Description:</label>
                                    <div class="col-sm-8">
                                        <textarea name="description" class="form-control" v-model="fillRound.description"></textarea>
                                        <span v-if="formErrors['description']" class="error text-danger">@{{ formErrors['description'] }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" :class="{'help is-danger': errors.has('update_round.start date') }" for="start date">Start Date:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input v-validate="'required'" class="form-control" :class="{'input': true, 'is-danger': errors.has('update_round.start date') }" name="start date" type="date" placeholder="" v-model="fillRound.start_date" />
                                        <span v-show="errors.has('update_round.start date')" class="help is-danger">@{{ errors.first('update_round.start date') }}</span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" :class="{'help is-danger': errors.has('update_round.end date') }" for="end date">End Date:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input v-validate="'required'" class="form-control" :class="{'input': true, 'is-danger': errors.has('update_round.end date') }" name="end date" type="date" placeholder="" v-model="fillRound.end_date" />
                                        <span v-show="errors.has('update_round.end date')" class="help is-danger">@{{ errors.first('update_round.end date') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row col-sm-offset-4 col-sm-8">
                                <button class="btn btn-sm btn-success"><i class='fa fa-plus-circle'></i> Submit</button>
                                <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrol Users Modal -->
    <div id="enrol-participants" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Enrol Participants</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4"></div>
                        <div class="col-md-4"></div>
                        <div class="col-md-4" style="padding-bottom:10px;">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" placeholder="Search for..." v-model="srchParticipant">
                                <span class="input-group-btn">
                                    <button class="btn btn-secondary" type="button" @click="srchEnrol" v-if="!loading"><i class="fa fa-search"></i></button>
                                    <button class="btn btn-secondary" type="button" disabled="disabled" v-if="loading">Searching...</button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="enrolParticipants" id="partFrm">
                            <div class="col-md-12">
                                <input type="hidden" class="form-control" name="round_id" id="round-id" v-bind:value="roundId"/>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Participant</th>
                                        <th>UID</th>
                                        <th>Facility</th>
                                        <th>Program</th>
                                    </tr>
                                    <tr v-for="participant in participants">
                                        <td>
                                            <input type="checkbox" :value="participant.id" name="usrs[]">
                                            @{{ participant.name }}
                                        </td>
                                        <td>@{{ participant.uid }}</td>
                                        <td>@{{ participant.facility }}</td>
                                        <td>@{{ participant.program }}</td>
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

                                <div class="form-group row col-sm-offset-4 col-sm-8">
                                    <button type="submit" class="btn btn-sm btn-success"><i class='fa fa-plus-circle'></i> Submit</button>
                                    <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch Enrolment Modal -->
    <div id="batch-enrolment" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Batch Enrolment</h4>
                </div>
                <div id="dups" class="alert alert-danger" style="display: none">
                   Some duplicated records were found and ignored.
                   <ul id="manage-round">
                      <li v-for="duplicate in duplicates">
                        @{{ duplicate[0] +', '+ duplicate[1] +', '+ duplicate[2] +', '+ duplicate[3] }}
                      </li>
                    </ul>
                </div>
                <div class="modal-body">
                    <div class="row">
                        
                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="batchEnrol" id="btch">
                            <div class="col-md-12">
                                <input type="hidden" class="form-control" id="round-id" :value="uploadify.id" v-model="uploadify.id"/>
                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" :class="{'help is-danger': errors.has('excel file') }" for="excel file">File:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input type="file" v-validate="'required|ext:xlsx,xls'" class="form-control-file" :class="{'input': true, 'is-danger': errors.has('excel file') }" name="excel file" @change="fileChanged">
                                        <span v-show="errors.has('excel file')" class="help is-danger">@{{ errors.first('excel file') }}</span>
                                    </div>
                                </div>
                                <div class="form-group row col-sm-offset-4 col-sm-8">
                                    <button id="upload" class="btn btn-sm btn-success"><i class='fa fa-plus-circle'></i> Submit</button>
                                    <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                                </div>                                
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--View Enrolled Participants Modal -->
    <div id="enrolled-participants" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Enrolled Participants</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4"></div>
                        <div class="col-md-4"></div>
                        <div class="col-md-4" style="padding-bottom:10px;">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" placeholder="Search for..." v-model="esrch">
                                <span class="input-group-btn">
                                    <button class="btn btn-secondary" type="button" @click="srchEnrol()" v-if="!loading"><i class="fa fa-search"></i></button>
                                    <button class="btn btn-secondary" type="button" disabled="disabled" v-if="loading">Searching...</button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Participant</th>
                                    <th>UID</th>
                                    <th>Facility</th>
                                    <th>Program</th>
                                </tr>
                                <tr v-for="enrol in testers">
                                    <td>@{{ enrol.name }}</td>
                                    <td>@{{ enrol.uid }}</td>
                                    <td>@{{ enrol.facility }}</td>
                                    <td>@{{ enrol.program }}</td>
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

                            <div class="form-group row col-sm-offset-4 col-sm-8">
                                <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection