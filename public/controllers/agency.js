Vue.http.headers.common['X-CSRF-TOKEN'] = $("#token").attr("value");
Vue.http.interceptors.unshift(function(request, next) {
    next(function(response) {
        if(typeof response.headers['content-type'] != 'undefined') {
            response.headers['Content-Type'] = response.headers['content-type'];
        }
    });
});
new Vue({

    el: '#manage-agency',

    data: {
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
      newAgency : {'name':''},
      fillAgency : {'name':'','id':''},
      loading: false,
      error: false,
      query: ''
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
        this.getVueAgencies(this.pagination.current_page);
    },

    methods : {
        getVueAgencies: function(page){
            this.$http.get('/vueagencies?page='+page).then((response) => {
                if(response.data.data)
                {
                    this.agencies = response.data.data.data;
                    this.pagination = response.data.pagination;
                }
                else
                {
                    swal("No data found for Agencies.", "", "info");
                }
            });
        },

        createAgency: function(scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.newAgency;
                this.$http.post('/vueagencies',input).then((response) => {
                if(response.data == 'error')
                {
                    this.error = response.data;
                    toastr.error('This Agency already exists', {timeOut: 5000});
                }
                else
                {
                    this.changePage(this.pagination.current_page);
                    this.newAgency = {'name':''};
                    $("#create-agency").modal('hide');
                    toastr.success('Agency Created Successfully.', 'Success Alert', {timeOut: 5000});
                }
                }, (response) => {
                    this.formErrors = response.data;
                });
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000});
                return false;
            });
        },

        deleteAgency: function(agency){
            this.$http.delete('/vueagencies/'+agency.id).then((response) => {
                this.changePage(this.pagination.current_page);
                toastr.success('Agency Deleted Successfully.', 'Success Alert', {timeOut: 5000});
            });
        },

        restoreAgency: function(agency){
            this.$http.patch('/vueagencies/'+agency.id+'/restore').then((response) => {
                this.changePage(this.pagination.current_page);
                toastr.success('Agency Restored Successfully.', 'Success Alert', {timeOut: 5000});
            });
        },

        editAgency: function(agency){
            this.fillAgency.name = agency.name;
            this.fillAgency.id = agency.id;
            $("#edit-agency").modal('show');
        },

        updateAgency: function(id, scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.fillAgency;
                this.$http.put('/vueagencies/'+id,input).then((response) => {
                    this.changePage(this.pagination.current_page);
                    this.fillAgency = {'name':'','id':''};
                    $("#edit-agency").modal('hide');
                    toastr.success('Agency Updated Successfully.', 'Success Alert', {timeOut: 5000});
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
            this.getVueAgencies(page);
        },

        search: function() {
            // Clear the error message.
            this.error = '';
            // Empty the agencies array so we can fill it with the new agencies.
            this.agencies = [];
            // Set the loading property to true, this will display the "Searching..." button.
            this.loading = true;

            // Making a get request to our API and passing the query to it.
            this.$http.get('/api/search_agency?q=' + this.query).then((response) => {
                // If there was an error set the error message, if not fill the agencies array.
                if(response.data.error)
                {
                    this.error = response.data.error;
                    toastr.error(this.error, 'Search Notification', {timeOut: 5000});
                }
                else
                {
                    this.agencies = response.data.data.data;
                    this.pagination = response.data.pagination;
                    toastr.success('The search results below were obtained.', 'Search Notification', {timeOut: 5000});
                }
                // The request is finished, change the loading to false again.
                this.loading = false;
                // Clear the query.
                this.query = '';
            });
        }
    }
});