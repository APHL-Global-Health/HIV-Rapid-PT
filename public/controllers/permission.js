Vue.http.headers.common['X-CSRF-TOKEN'] = $("#token").attr("value");
Vue.http.interceptors.unshift(function(request, next) {
    next(function(response) {
        if(typeof response.headers['content-type'] != 'undefined') {
            response.headers['Content-Type'] = response.headers['content-type'];
        }
    });
});
new Vue({

    el: '#manage-permission',

    data: {
        roles: [],
        permissions: [],
        checks: [],
        /*pagination: {
            total: 0, 
            per_page: 2,
            from: 1, 
            to: 0,
            current_page: 1
        },*/
        offset: 4,
        formErrors:{},
        formErrorsUpdate:{},
        newFacility : {'name':'','description':'', 'order':'', 'tag':'', 'options':''},
        fillFacility : {'name':'','description':'', 'order':'', 'tag':'', 'options':'','id':''}
    },
  
    /*computed: {
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
    },*/
    
    mounted : function(){
        this.getVuePermissions();
    },

    methods : {

        getVuePermissions: function(){
            this.$http.get('/vuepermissions').then((response) => {
                this.permissions = response.data.permissions;
                this.roles = response.data.roles;
                this.checks = response.data.checks;
            });
        },

        createPrivilege: function(){
            let myForm = document.getElementById('update_privileges');
            let formData = new FormData(myForm);
            this.$http.post('/vuepermissions', formData).then((response) => {
                toastr.success('Privileges Updated Successfully.', 'Success Alert', {timeOut: 5000});
                this.getVuePermissions();
            }, (response) => {
                this.formErrors = response.data;
            });
        },

        changePage: function (page) {
            this.pagination.current_page = page;
            this.getVuePermissions(page);
        }
    }
});