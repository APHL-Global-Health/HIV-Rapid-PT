@extends('app')
@section('content')
<div class="row">
    <div class="col-sm-12">
        <ol class="breadcrumb">
            <li><a href="{!! url('home') !!}"><i class="fa fa-home"></i> {!! trans('messages.home') !!}</a></li>
            <li class="active"><i class="fa fa-users"></i> {!! trans('messages.user-management') !!}</li>
            <li class="active"><i class="fa fa-cube"></i> {!! trans_choice('messages.user', 2) !!}</li>
        </ol>
    </div>
</div>
<div class="" id="manage-partner">
    <!-- ImplementingPartner Listing -->
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left col-md-9">
                <h5><i class="fa fa-book"></i> {!! trans_choice('messages.user', 2) !!}        
                
                    <a class="btn btn-sm btn-carrot" href="#" onclick="window.history.back();return false;" alt="{!! trans('messages.back') !!}" title="{!! trans('messages.back') !!}">
                        <i class="fa fa-step-backward"></i>
                        {!! trans('messages.back') !!}
                    </a>
                @permission('create-user')
                    <button type="button" class="btn btn-sm btn-belize-hole" data-toggle="modal" data-target="#create-implementing-partner" >
                        <i class="fa fa-plus-circle"></i>
                        {!! trans('messages.add') !!}
                    </button>
                @endpermission
                </h5>
            </div>
            <div class="col-md-3">
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
    @if(session()->has('error'))
        <div class="alert alert-info">{!! session()->get('error') !!}</div>
    @endif
    <table class="table table-bordered">
        <tr>
            <th>Name</th>
            <th>Agency</th>
            <th>Action</th>
        </tr>
        <tr v-for="implementing_partner in implementing_partners">
            <td>@{{ implementing_partner.name }}</td>
            <td>@{{ implementing_partner.agency.name }}</td>
            <td>
           @permission('update-user')   
                <button v-bind="{ 'disabled': implementing_partner.deleted_at }" class="btn btn-sm btn-primary"  @click.prevent="editImplementingPartner(implementing_partner)"><i class="fa fa-edit"></i> Edit</button>
            @endpermission
            @permission('restore-user') 
                <button v-if="implementing_partner.deleted_at" class="btn btn-sm btn-success" @click.prevent="restoreImplementingPartner(implementing_partner)"><i class="fa fa-toggle-on"></i> Enable</button>
            @endpermission
            @permission('delete-user') 
                <button v-if="!implementing_partner.deleted_at" class="btn btn-sm btn-alizarin" @click.prevent="deleteImplementingPartner(implementing_partner)"><i class="fa fa-power-off"></i> Disable</button>
            @endpermission
                <button v-if="!implementing_partner.deleted_at"
                class="btn btn-sm btn-nephritis"  @click.prevent="viewImplementingPartner(implementing_partner)"><i class="fa fa-user-circle"></i> View</button>
            </td>
        </tr>
    </table>
    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <li v-if="pagination.current_page > 1"  class="page-item">
                <a class="page-link" href="#" aria-label="Previous"
                    @click.prevent="changePage(pagination.current_page - 1)">
                    <span aria-hidden="true">«</span>
                </a>
            </li>
            <li v-for="page in pagesNumber"  class="page-item"
                v-bind:class="[ page == isActived ? 'active' : '']">
                <a class="page-link" href="#"
                    @click.prevent="changePage(page)">@{{ page }}</a>
            </li>
            <li v-if="pagination.current_page < pagination.last_page"  class="page-item">
                <a class="page-link" href="#" aria-label="Next"
                    @click.prevent="changePage(pagination.current_page + 1)">
                    <span aria-hidden="true">»</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Create ImplementingPartner Modal -->
    <div class="modal fade" id="create-implementing-partner" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" id="myModalLabel">Create ImplementingPartner</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="createImplementingPartner('create_implementing_partner')" data-vv-validate="create_implementing_partner">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('create_implementing_partner.name') }"
                                        for="name">Name:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input v-validate="'required|alpha_spaces'" class="form-control"
                                            :class="{'input': true,'is-danger': errors.has('create_implementing_partner.name') }" name="name"
                                            type="text" placeholder=""
                                            v-model="newImplementingPartner.name" />
                                        <span v-show="errors.has('create_implementing_partner.name')" class="help is-danger">
                                            @{{ errors.first('create_implementing_partner.name') }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" :class="{'help is-danger': errors.has('agency') }" for="agency">Agency:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <select v-validate="'required'" class="form-control c-select" name="agency" :class="{'input': true, 'is-danger': errors.has('agency') }" v-model="newImplementingPartner.agency_id">
                                            <option selected></option>
                                            <option v-for="agency in agencies" :value="agency.id">@{{ agency.name }}</option>
                                        </select>
                                        <span v-show="errors.has('agency')" class="help is-danger">@{{ errors.first('agency') }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" :class="{'help is-danger': errors.has('county') }" for="county">County:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <select v-validate="'required'" class="form-control c-select" name="county" :class="{'input': true, 'is-danger': errors.has('county') }" multiple v-model="newImplementingPartner.county_id">
                                            <option selected></option>
                                            <option v-for="county in counties" :value="county.id">@{{ county.value }}</option>
                                        </select>
                                        <span v-show="errors.has('county')" class="help is-danger">@{{ errors.first('county') }}</span>
                                    </div>
                                </div>

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

    <!-- Edit ImplementingPartner Modal -->
    <div class="modal fade" id="edit-user" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
	                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
	                <h4 class="modal-title" id="myModalLabel">Edit Implementing Partner</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="updateImplementingPartner(fillImplementingPartner.id, 'update_user')" data-vv-validate="update_user">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label"  :class="{'help is-danger': errors.has('name') }"
                                        for="name">Name:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <input v-validate="'required|alpha_spaces'" class="form-control"
                                            :class="{'input': true,'is-danger': errors.has('name') }" name="name"
                                            type="text" placeholder=""
                                            v-model="fillImplementingPartner.name" />
                                        <span v-show="errors.has('name')" class="help is-danger">
                                            @{{ errors.first('name') }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" :class="{'help is-danger': errors.has('agency') }" for="agency">Agency:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <select v-validate="'required'" class="form-control c-select" name="agency_id" :class="{'input': true, 'is-danger': errors.has('agency_id') }" v-model="fillImplementingPartner.agency_id">
                                            <option selected></option>
                                            <option v-for="agency in agencies" :value="agency.id">@{{ agency.name }}</option>
                                        </select>
                                        <span v-show="errors.has('agency')" class="help is-danger">@{{ errors.first('agency') }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 form-control-label" :class="{'help is-danger': errors.has('county') }" for="county">County:</label>
                                    <div class="col-sm-8" :class="{ 'control': true }">
                                        <select v-validate="'required'" class="form-control c-select" name="county_id" :class="{'input': true, 'is-danger': errors.has('county_id') }" multiple v-model="fillImplementingPartner.county_id">
                                            <option selected></option>
                                            <option v-for="county in counties" :value="county.id">@{{ county.value }}</option>
                                        </select>
                                        <span v-show="errors.has('county')" class="help is-danger">@{{ errors.first('county') }}</span>
                                    </div>
                                </div>


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
    <!-- View Implementing Partner -->
    <div class="modal fade" id="view-implementing-partner"
        tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" id="myModalLabel">View Implementing Partner</h4>
                </div>
                <div class="modal-body">
                        <div class="row">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td><strong>Name</strong></td>
                                        <td>@{{someImplementingPartner.name}}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Agency</strong></td>
                                        <td>@{{someImplementingPartner.agency.name}}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Counties</strong></td>
                                        <td>
                                            <div v-for="county in someImplementingPartner.counties">
                                                @{{ county.name }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
