Vue.http.headers.common['X-CSRF-TOKEN'] = $("#token").attr("value");
Vue.http.interceptors.unshift(function(request, next) {
    next(function(response) {
        if(typeof response.headers['content-type'] != 'undefined') {
            response.headers['Content-Type'] = response.headers['content-type'];
        }
    });
});
new Vue({

    el: '#manage-user-profile',

    data: {
        offset: 4,
        formErrors:{},
        formErrorsUpdate:{},
        fillProfile : {'name':'', 'gender':'', 'email':'', 'phone':'', 'address':'', 'username':'', 'image':'', 'uid':''},
        userProfile: {'name':'', 'sex':'', 'email':'', 'phone':'', 'address':'', 'username':'', 'image':'', 'uid':''},
        programs: [],
        designations: [],
        facility : '',
        sub_county : '',
        county : '',
        userPassword : {'old':'', 'new':'', 'confirm':''},
        passwordErrors:{},
        transUser : {'designation':'', 'program':'', 'mfl_code':''},
    },

    mounted : function(){
        this.getVueProfile();
        this.loadPrograms();
        this.loadDesignations();
    },

    methods : {
        getVueProfile: function(){
            this.$http.get('/user/profile').then((response) => {
                this.userProfile = response.data;
            });
        },

        editProfile: function(userProfile){
            $("#edit-profile").modal('show');
        },

        updateSettings: function(){
            var input = this.fillSettings;
            this.$http.post('/bulk/api', input).then((response) => {
                this.changePage(this.pagination.current_page);
                this.fillSettings = {'code':'','username':'','api_key':''};
                $("#edit-settings").modal('hide');
                toastr.success('Bulk SMS Settings Updated Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
            }, (response) => {
                this.formErrorsUpdate = response.data;
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

        loadDesignations: function() {
            this.$http.get('/des').then((response) => {
                this.designations = response.data;
            }, (response) => {
                // console.log(response);
            });
        },

        fetchFacility: function() {
            let id = $('#mfl').val();
            this.$http.get('/mfl/'+id).then((response) => {
                console.log(response.data);
                this.facility = response.data.name;
                this.sub_county = response.data.sub_county;
                this.county = response.data.county;
                if(this.facility.length == 0)
                    swal("Facility not found!", "Enter a valid MFL Code.", "info");
            });
        },

        imageChanged(e){
            // console.log(e.target.files[0]);
            var fileReader = new FileReader();
            fileReader.readAsDataURL(e.target.files[0]);
            fileReader.onload = (e) => {
                this.userProfile.image = e.target.result;
            }
        },

        updateProfile()
        {
            var input = this.userProfile;
            this.$http.post('/user/profile/update', input).then((response) => {
                this.userProfile = {'name':'', 'sex':'', 'email':'', 'phone':'', 'address':'', 'username':'', 'image':'', 'uid':''},
                $("#edit-profile").modal('hide');
                this.getVueProfile();
                toastr.success('User Profile Updated Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
            }, (response) => {
                this.formErrors = response.data;
            });
        },

        updatePassword(scope)
        {
            this.$validator.validateAll(scope).then(() => {
                var input = this.userPassword;
                this.$http.post('/user/password/update', input).then((response) => {
                    if(response.data.error)
                    {
                        swal("Alert!", response.data.error, "error");
                        // toastr.warning(this.warning, 'Notification', {timeOut: 5000, positionClass: "toast-top-full-width"});
                    }
                    else
                    {
                        this.userPassword = {'old':'', 'new':'', 'confirm':''},
                        $("#update-password").modal('hide');
                        this.getVueProfile();
                        toastr.success('Password Updated Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                    }
                }, (response) => {
                    if(response.data.error)
                    {
                        swal("Alert!", response.data.error, "error");
                        // toastr.warning(this.warning, 'Notification', {timeOut: 5000, positionClass: "toast-top-full-width"});
                    }
                });
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000, positionClass: "toast-top-full-width"});
            });
        },

        fetchUser: function(userProfile){
            $("#transfer-participant").modal('show');
        },

        transferUser(scope)
        {
            this.$validator.validateAll(scope).then(() => {
                var input = this.transUser;
                this.$http.post('/user/transfer/facility', input).then((response) => {
                    this.transUser = {'designation':'', 'program':'', 'mfl_code':''},
                    $("#transfer-participant").modal('hide');
                    this.getVueProfile();
                    toastr.success('Participant Transferred Successfully.', 'Success Alert', {timeOut: 5000, positionClass: "toast-top-full-width"});
                }, (response) => {
                    if(response.data.error)
                    {
                        swal("Alert!", response.data.error, "error");
                        // toastr.warning(this.warning, 'Notification', {timeOut: 5000, positionClass: "toast-top-full-width"});
                    }
                });
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000, positionClass: "toast-top-full-width"});
            });
        },
    }
});