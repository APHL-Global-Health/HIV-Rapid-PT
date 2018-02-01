Vue.http.headers.common['X-CSRF-TOKEN'] = $("#token").attr("value");
Vue.http.interceptors.unshift(function(request, next) {
    next(function(response) {
        if(typeof response.headers['content-type'] != 'undefined') {
            response.headers['Content-Type'] = response.headers['content-type'];
        }
    });
});
new Vue({

    el: '#manage-des',

    data: {
      designations: [],
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
      newDesignation : {'name':'','description':''},
      fillDesignation : {'name':'','description':'','id':''},
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
        this.getVueDesignations(this.pagination.current_page);
    },

    methods : {
        getVueDesignations: function(page){
            this.$http.get('/vuedesignations?page='+page).then((response) => {
                if(response.data.data)
                {
                    this.designations = response.data.data.data;
                    this.pagination = response.data.pagination;
                }
                else
                {
                    swal("No data found for Designations.", "", "info");
                }
            });
        },

        createDesignation: function(scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.newDesignation;
                this.$http.post('/vuedesignations',input).then((response) => {
                if(response.data == 'error')
                {
                    this.error = response.data;
                    toastr.error('This Designation already exists', {timeOut: 5000});
                }
                else
                {
                    this.changePage(this.pagination.current_page);
                    this.newDesignation = {'name':'','description':''};
                    $("#create-designation").modal('hide');
                    toastr.success('Designation Created Successfully.', 'Success Alert', {timeOut: 5000});
                }
                }, (response) => {
                    this.formErrors = response.data;
                });
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000});
                return false;
            });
        },

        deleteDesignation: function(designation){
            this.$http.delete('/vuedesignations/'+designation.id).then((response) => {
                this.changePage(this.pagination.current_page);
                toastr.success('Designation Deleted Successfully.', 'Success Alert', {timeOut: 5000});
            });
        },

        restoreDesignation: function(designation){
            this.$http.patch('/vuedesignations/'+designation.id+'/restore').then((response) => {
                this.changePage(this.pagination.current_page);
                toastr.success('Designation Restored Successfully.', 'Success Alert', {timeOut: 5000});
            });
        },

        editDesignation: function(designation){
            this.fillDesignation.name = designation.name;
            this.fillDesignation.id = designation.id;
            this.fillDesignation.description = designation.description;
            $("#edit-designation").modal('show');
        },

        updateDesignation: function(id, scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.fillDesignation;
                this.$http.put('/vuedesignations/'+id,input).then((response) => {
                    this.changePage(this.pagination.current_page);
                    this.fillDesignation = {'name':'','description':'','id':''};
                    $("#edit-designation").modal('hide');
                    toastr.success('Designation Updated Successfully.', 'Success Alert', {timeOut: 5000});
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
            this.getVueDesignations(page);
        },

        search: function() {
            // Clear the error message.
            this.error = '';
            // Empty the designations array so we can fill it with the new designations.
            this.designations = [];
            // Set the loading property to true, this will display the "Searching..." button.
            this.loading = true;

            // Making a get request to our API and passing the query to it.
            this.$http.get('/api/search_designation?q=' + this.query).then((response) => {
                // If there was an error set the error message, if not fill the designations array.
                if(response.data.error)
                {
                    this.error = response.data.error;
                    toastr.error(this.error, 'Search Notification', {timeOut: 5000});
                }
                else
                {
                    this.designations = response.data.data.data;
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