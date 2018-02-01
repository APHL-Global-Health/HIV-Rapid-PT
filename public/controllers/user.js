Vue.http.headers.common['X-CSRF-TOKEN'] = $("#token").attr("value");
Vue.http.interceptors.unshift(function(request, next) {
    next(function(response) {
        if(typeof response.headers['content-type'] != 'undefined') {
            response.headers['Content-Type'] = response.headers['content-type'];
        }
    });
});
new Vue({

    el: '#manage-user',

    data: {
        users: [],
        roles: [],
        counties: [],
        subs: [],
        programs:[],
        facilities: [],
        pagination: {
            total: 0, 
            per_page: 2,
            from: 1, 
            to: 0,
            current_page: 1
        },
        offset: 4,
        formErrors:{},
        formErrorsUpdate:{},
        newUser : {'name':'','username': '','role': '','gender':'', 'phone':'', 'email':'', 'address':''},
        fillUser : {'name':'','username': '','role': '','gender':'', 'phone':'', 'email':'', 'address':'', 'id':''},
        transferUser : {'facility_id':'','program_id':'', 'id':''},
        loading: false,
        error: false,
        query: '',
        formTransErrors:{},
        uploadify: {id: '', excel: ''},
        someUser : {'facility_id':'','program_id':'','sub_county_id':'','county_id':'','facility':'','program':'','sub_county':'','county':'','in_charge':'', 'id':''},
        jimbo: [],
        sexes: [],
        uploadify: {excel: ''},
        upload: {list: ''}
    },

    computed: {
        isActived: function () {
            return this.pagination.current_page;
        },
        pagesNumber: function () {
            if (!this.pagination.to) {
                return [];
            }
            var from = this.pagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }
            var to = from + (this.offset * 2);
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
            }
            var pagesArray = [];
            while (from <= to) {
                pagesArray.push(from);
                from++;
            }
            return pagesArray;
        }
    },

    mounted : function(){
        this.getVueUsers(this.pagination.current_page);
        this.loadPrograms();
        this.loadCounties();
        this.loadRoles();
        this.loadSexes();
    },

    methods : {

        getVueUsers: function(page){
            this.$http.get('/vueusers?page='+page).then((response) => {
                this.users = response.data.data.data;
                this.pagination = response.data.pagination;
            });
        },

        createUser: function(scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.newUser;
                this.loading = true;
                this.$http.post('/vueusers',input).then((response) => {
                    this.changePage(this.pagination.current_page);
                    this.newUser = {'name':'', 'username': '','gender':'', 'phone':'', 'email':'', 'address':''};
                    $("#create-user").modal('hide');
                    if(response.data.errormessage.length > 0){
                        toastr.error("<ul><li>" + response.data.errormessage.join("</li><li>") + "</li></ul>", 'Error Alert', {closeButton: true, positionClass: "toast-top-full-width"});
                    }else{
                        toastr.success('User Created Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                    }
                    this.loading = false;
                }, (response) => {
                    this.formErrors = response.data;
                    this.loading = false;
                });
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000, positionClass: "toast-top-full-width"});
                return false;
            });
        },

        deleteUser: function(user){
            this.$http.delete('/vueusers/'+user.id).then((response) => {
                this.changePage(this.pagination.current_page);
                if(response.data.errormessage.length > 0){
                    toastr.error("<ul><li>" + response.data.errormessage.join("</li><li>") + "</li></ul>", 'Error Alert', {closeButton: true, positionClass: "toast-top-full-width"});
                }else{
                    toastr.success('User Deleted Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                }
            });
        },

        restoreUser: function(user){
            this.$http.patch('/vueusers/'+user.id+'/restore').then((response) => {
                this.changePage(this.pagination.current_page);
                if(response.data.errormessage.length > 0){
                    toastr.error("<ul><li>" + response.data.errormessage.join("</li><li>") + "</li></ul>", 'Error Alert', {closeButton: true, positionClass: "toast-top-full-width"});
                }else{
                    toastr.success('User Restored Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                }
            });
        },

        editUser: function(user){
            this.fillUser.id = user.id;
            this.fillUser.name = user.name;
            this.fillUser.username = user.username;
            this.fillUser.gender = user.gender;
            this.fillUser.phone = user.phone;
            this.fillUser.email = user.email;
            this.fillUser.address = user.address;
            this.fillUser.rl = user.rl;
            this.fillUser.role = user.role;
            this.fillUser.program = user.program;
            this.fillUser.uid = user.uid;
            this.fillUser.address = user.address;
            $("#edit-user").modal('show');
        },

        updateUser: function(id, scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.fillUser;
                this.loading = true;
                this.$http.put('/vueusers/'+id,input).then((response) => {
                    this.fillUser = {'name':'','username': '','gender':'', 'phone':'', 'email':'', 'address':''};
                    $("#edit-user").modal('hide');
                    if(response.data.errormessage.length > 0){
                        toastr.error("<ul><li>" + response.data.errormessage.join("</li><li>") + "</li></ul>", 'Error Alert', {closeButton: true, positionClass: "toast-top-full-width"});
                    }else{
                        toastr.success('User updated successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                    }
                    this.getVueUsers(this.pagination.current_page);
                    this.loading = false;
                }, (response) => {
                    this.formErrorsUpdate = response.data;
                    this.loading = false;
                });
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000, positionClass: "toast-top-full-width"});
                return false;
            });
        },

        changePage: function (page) {
            this.pagination.current_page = page;
            this.getVueUsers(page);
        },
        //Populate counties from FacilityController
        loadCounties: function() {
            this.$http.get('/cnts').then((response) => {
                this.counties = response.data;
                this.jimbo = response.data;
            }, (response) => {
                // console.log(response);
            });
        },
        // Populate subcounties from FacilityController
        loadSubcounties: function() {
            let id = $('#county').val();
            this.$http.get('/subs/'+id).then((response) => { 
                this.subcounties = response.data;
            }, (response) => {
                // console.log(response);
            });
        },      
        // Populate facilities from FacilityController
        loadFacilities: function() {
            let id = $('#sub_county').val();
            this.$http.get('/fclts/'+id).then((response) => { 
                this.facilities = response.data;
            }, (response) => {
                // console.log(response);
            });
        },
        //    Populate programs from ProgramController
        loadPrograms: function() {
            this.$http.get('/progs').then((response) => { 
                this.programs = response.data;
            }, (response) => {
                // console.log(response);
            });
        },
        //    Populate roles from RoleController
        loadRoles: function() {
            this.$http.get('/privs').then((response) => { 
                this.roles = response.data;
            }, (response) => {
                // console.log(response);
            });
        },

        loadSexes: function() {
            this.$http.get('/sex').then((response) => {
                this.sexes = response.data;
            }, (response) => {
                // console.log(response);
            });
        },

        uploadSheet: function(){
            $("#batch-registration").modal('show');
            console.log("Live on raw.");
        },

        batchReg(){
            // this.$validator.validateAll().then(() => {
                var input = this.uploadify;
                this.$http.post('/batch/register', input).then((response) => {
                    this.uploadify = {'excel':''};
                    $("#batch-registration").modal('hide');
                    toastr.success('Data Uploaded Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                    this.errors.clear();
                }, (response) => {
                    // 
                });
            /*}).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000, positionClass: "toast-top-full-width"});
            });*/
        },

        fileChanged(e){
            console.log(e.target.files[0]);
            var fileReader = new FileReader();
            fileReader.readAsDataURL(e.target.files[0]);
            fileReader.onload = (e) => {
                this.uploadify.excel = e.target.result;
            }
        },

        importUsers(scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.upload;
                this.loading = true;
                this.$http.post('/import/users', input).then((response) => {
                    this.upload = {'list':''};
                    $("#import-users-list").modal('hide');
                    toastr.success('Data Uploaded Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                    this.errors.clear();
                    this.loading = false;
                }, (response) => {
                    // 
                    this.loading = false;
                });
            }).catch(() => {
                toastr.error('Please upload a file.', 'Validation Failed', {timeOut: 5000, positionClass: "toast-top-full-width"});
            });
        },
        listChanged(e){
            console.log(e.target.files[0]);
            var fileReader = new FileReader();
            fileReader.readAsDataURL(e.target.files[0]);
            fileReader.onload = (e) => {
                this.upload.list = e.target.result;
            }
        },

        search: function() {
            // Clear the error message.
            this.error = '';
            // Empty the users array so we can fill it with the new users.
            this.users = [];
            // Set the loading property to true, this will display the "Searching..." button.
            this.loading = true;

            // Making a get request to our API and passing the query to it.
            this.$http.get('/api/search_user?q=' + this.query).then((response) => {
                // If there was an error set the error message, if not fill the users array.
                if(response.data.error)
                {
                    this.error = response.data.error;
                    toastr.error(this.error, 'Search Notification', {timeOut: 5000, positionClass: "toast-top-full-width"});
                }
                else
                {
                    this.users = response.data.data.data;
                    this.pagination = response.data.pagination;
                    toastr.success('The search results below were obtained.', 'Search Notification', {timeOut: 5000, positionClass: "toast-top-full-width"});
                }
                // The request is finished, change the loading to false again.
                this.loading = false;
                // Clear the query.
                this.query = '';
            });
        },

        loadCounties: function() {
            this.$http.get('/cnts').then((response) => {
                this.counties = response.data;
            }, (response) => {
                // console.log(response);
            });
        },

        fetchSubs: function() {
            let id = $('#county_id').val();
            this.$http.get('/subs/'+id).then((response) => {
                this.subs = response.data;
            }, (response) => {
                // console.log(response);
            });
        },

        fetchFacilities: function() {
            let id = $('#sub_id').val();
            this.$http.get('/fclts/'+id).then((response) => {
                this.facilities = response.data;
            }, (response) => {
                // console.log(response);
            });
        },

        populateUser: function(user){
            this.transferUser.id = user.id;
            this.transferUser.facility_id = user.facility;
            this.transferUser.program_id = user.program;
            $("#transfer-user").modal('show');
        },

        transUser: function(id){
            var input = this.transferUser;
            this.loading = true;
            this.$http.put('/transfer/'+id,input).then((response) => {
                this.changePage(this.pagination.current_page);
                this.transferUser = {'facility_id':'', 'program_id':'','id': ''};
                $("#transfer-user").modal('hide');
                toastr.success('User Updated Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                this.loading = false;
            }, (response) => {
                this.formTransErrors = response.data;
                this.loading = false;
            });
        },

        registered: function() {
            // Clear the error message.
            this.error = '';
            // Empty the users array so we can fill it with the new users.
            this.users = [];
            // Set the loading property to true, this will display the "Searching..." button.
            this.loading = true;

            // Making a get request to our API and passing the query to it.
            this.$http.get('/api/search_user?filter=' + 'self').then((response) => {
                // If there was an error set the error message, if not fill the users array.
                if(response.data.error)
                {
                    this.error = response.data.error;
                    toastr.error(this.error, 'Error Notification', {timeOut: 5000, positionClass: "toast-top-full-width"});
                }
                else
                {
                    this.users = response.data.data.data;
                    this.pagination = response.data.pagination;
                    toastr.success('The results below were obtained.', 'Filter Notification', {timeOut: 5000, positionClass: "toast-top-full-width"});
                }
                // The request is finished, change the loading to false again.
                this.loading = false;
            });
        },

        openUser: function(user){
            $("#approve-user").modal('show');
        },

        approveUser: function(id){
            var input = this.someUser;
            this.loading = true;
            this.$http.put('/approve/'+id, input).then((response) => {
                this.changePage(this.pagination.current_page);
                this.someUser = {'facility_id':'','program_id':'','sub_county_id':'','county_id':'','facility':'','program':'','sub_county':'','county':'','in_charge':'', 'id':''};
                $("#approve-user").modal('hide');
                toastr.success('User Approved Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                this.loading = false;
            }, (response) => {
                // 
                this.loading = false;
            });
        },
    }
});