Vue.http.headers.common['X-CSRF-TOKEN'] = $("#token").attr("value");
Vue.http.interceptors.unshift(function(request, next) {
    next(function(response) {
        if(typeof response.headers['content-type'] != 'undefined') {
            response.headers['Content-Type'] = response.headers['content-type'];
        }
    });
});
new Vue({

    el: '#manage-partner',

    data: {
        implementing_partners: [],
        counties: [],
        agencies: [],
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
        newImplementingPartner : {'name':'','agency_id':'','county_id':[]},
        fillImplementingPartner : {'name':'','agency_id':'','county_id':[], 'id':''},
        loading: false,
        error: false,
        query: '',
        someImplementingPartner : {'name':'','agency':'','counties':'','id':''},
        formTransErrors:{},
        jimbo: [],
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
        this.getVueImplementingPartners(this.pagination.current_page);
        this.loadAgencies();
        this.loadCounties();
    },

    methods : {

        getVueImplementingPartners: function(page){
            this.$http.get('/vueimplementingpartners?page='+page).then((response) => {
                if(response.data.data)
                {
                this.implementing_partners = response.data.data.data;
                this.pagination = response.data.pagination;
            }
            else
            {
                swal("No data found for Partner.","","info");
            }
            });
        },

        createImplementingPartner: function(scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.newImplementingPartner;
                this.$http.post('/vueimplementingpartners',input).then((response) => {
                    this.changePage(this.pagination.current_page);
                    this.newImplementingPartner = {'name':'', 'agency_id': '', 'county_id': []};
                    $("#create-implementing-partner").modal('hide');
                    toastr.success('ImplementingPartner Created Successfully.', 'Success Alert', {timeOut: 5000});
                }, (response) => {
                    this.formErrors = response.data;
                });
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000});
                return false;
            });
        },

        deleteImplementingPartner: function(user){
            this.$http.delete('/vueimplementingpartners/'+user.id).then((response) => {
                this.changePage(this.pagination.current_page);
                toastr.success('ImplementingPartner Deleted Successfully.', 'Success Alert', {timeOut: 5000});
            });
        },

        restoreImplementingPartner: function(user){
            this.$http.patch('/vueimplementingpartners/'+user.id+'/restore').then((response) => {
                this.changePage(this.pagination.current_page);
                toastr.success('ImplementingPartner Restored Successfully.', 'Success Alert', {timeOut: 5000});
            });
        },

        editImplementingPartner: function(implementingPartner){
            this.fillImplementingPartner.id = implementingPartner.id;
            this.fillImplementingPartner.name = implementingPartner.name;
            this.fillImplementingPartner.county_id = implementingPartner.counties;
            this.fillImplementingPartner.agency_id = implementingPartner.agency_id;
            $("#edit-user").modal('show');
        },

        updateImplementingPartner: function(id, scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.fillImplementingPartner;
                this.$http.put('/vueimplementingpartners/'+id,input).then((response) => {
                    this.changePage(this.pagination.current_page);
                    this.fillImplementingPartner = {'name':'','agency_id':'','county_id':[], 'id':''};
                    $("#edit-user").modal('hide');
                    toastr.success('ImplementingPartner Updated Successfully.', 'Success Alert', {timeOut: 5000});
                }, (response) => {
                    this.formErrorsUpdate = response.data;
                });
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000});
                return false;
            });
        },

        changePage: function (page) {
            this.pagination.current_page = page;
            this.getVueImplementingPartners(page);
        },
        // Populate counties from FacilityController
        loadCounties: function() {
            this.$http.get('/cnts').then((response) => {
                this.counties = response.data;
            }, (response) => {
                // console.log(response);
            });
        },
        loadAgencies: function() {
            this.$http.get('/vueagencies').then((response) => {
                this.agencies = response.data.data.data;
            }, (response) => {
                // console.log(response);
            });
        },

        fileChanged(e){
            console.log(e.target.files[0]);
            var fileReader = new FileReader();
            fileReader.readAsDataURL(e.target.files[0]);
            fileReader.onload = (e) => {
                this.uploadify.excel = e.target.result;
            }
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
            this.implementing_partners = [];
            // Set the loading property to true, this will display the "Searching..." button.
            this.loading = true;

            // Making a get request to our API and passing the query to it.
            this.$http.get('/api/search_partner?q=' + this.query).then((response) => {
                // If there was an error set the error message, if not fill the users array.
                if(response.data.error)
                {
                    this.error = response.data.error;
                    toastr.error(this.error, 'Search Notification', {timeOut: 5000});
                }
                else
                {
                    this.implementing_partners = response.data.data.data;
                    this.pagination = response.data.pagination;
                    toastr.success('The search results below were obtained.', 'Search Notification', {timeOut: 5000});
                }
                // The request is finished, change the loading to false again.
                this.loading = false;
                // Clear the query.
                this.query = '';
            });
        },

        viewImplementingPartner: function(implementing_partner){
            this.someImplementingPartner.id = implementing_partner.id;
            this.someImplementingPartner.name = implementing_partner.name;
            this.someImplementingPartner.agency = implementing_partner.agency;
            this.someImplementingPartner.counties = implementing_partner.counties;
            $("#view-implementing-partner").modal('show');
        },
    }
});