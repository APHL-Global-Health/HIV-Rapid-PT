Vue.http.headers.common['X-CSRF-TOKEN'] = $("#token").attr("value");
Vue.http.interceptors.unshift(function(request, next) {
    next(function(response) {
        if(typeof response.headers['content-type'] != 'undefined') {
            response.headers['Content-Type'] = response.headers['content-type'];
        }
    });
});
new Vue({
    el: '#manage-round',

    data: {
        rounds: [],
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
        newRound : {'name':'','description':'','start_date':'','duration':'','end_date':''},
        fillRound : {'name':'','description':'','start_date':'','duration':'','end_date':'','id':''},
        loading: false,
        error: false,
        checked:false,
        query: '',
        participants: [],
        testerparticipants:[],
        srchloadedprt:'',
        srchParticipant: '',
        enrollments: [],
        esrch: '',
        date: '',
        testers: [],
        durations: [],
        duplicates: [],
        roundId:'',
        uploadify: {id: '', excel: ''}
    },

    computed: {
        isActived: function () {
            return this.pagination.current_page;
        },
        /*isDeactivated: function(){
            this.$http.get('/vuerounds?page=').then((response) => {
                if(response.data.data)
                {
                    this.rounds = response.data.data.data;                    
                    this.rounds.forEach(function(date){
                        disabled =  false;
                        console.log(date.end_date);

                        return date.end_date;
                        var today = new Date();
                        var dd = today.getDate();
                        var mm = today.getMonth()+1; 
                        var yyyy = today.getFullYear();

                       if(dd<10) {
                              dd = '0'+dd
                             } 

                        if(mm<10) {
                          mm = '0'+mm
                            } 

                           today = yyyy + '-' + mm + '-' + dd;
                           console.log(today);
                    });
                }
                
            });
        },*/
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
    	this.getVueRounds(this.pagination.current_page);
        this.getDurations();
    },

    methods : {
        getVueRounds: function(page){
            this.$http.get('/vuerounds?page='+page).then((response) => {
                if(response.data.data)
                {
                    this.rounds = response.data.data.data;
                    this.pagination = response.data.pagination;
                }
                else
                {
                    swal("No data found for PT rounds.", "", "info");
                }
            });
        },

        createRound: function(scope){
            
            this.$validator.validateAll(scope).then(() => {
                var input = this.newRound;
          		this.$http.post('/vuerounds',input).then((response) => {
                if(response.data == '1')
                {
                    this.error = response.data;
                    toastr.error('This Round already exists', {timeOut: 5000});
                }
                else if(response.data == '2')
                {
                    this.error = response.data;
                    toastr.error('Start Date should not be greater than End Date', {timeOut: 5000});
                }
                else
                {
          		    this.changePage(this.pagination.current_page);
          			this.newRound = {'name':'','description':'','start_date':'','end_date':''};
          			$("#create-round").modal('hide');
          			toastr.success('Round Created Successfully.', 'Success Alert', {timeOut: 5000});
                    this.errors.clear();
                }
          		});
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000});
                return false;
            });
    	},

        deleteRound: function(round){
            this.$http.delete('/vuerounds/'+round.id).then((response) => {
                this.changePage(this.pagination.current_page);
                toastr.success('Round Deleted Successfully.', 'Success Alert', {timeOut: 5000});
            });
        },
        deleteParticipant: function(participant){
            this.$http.delete('/vueparticipants/'+ participant).then((response) => {
                this.changePage(this.pagination.current_page);
                toastr.success('Participant(s) Deleted Successfully.', 'Success Alert', {timeOut: 5000});
            });
        },

        restoreRound: function(round){
            this.$http.patch('/vuerounds/'+round.id+'/restore').then((response) => {
                this.changePage(this.pagination.current_page);
                toastr.success('Round Restored Successfully.', 'Success Alert', {timeOut: 5000});
            });
        },

        editRound: function(round){
            this.fillRound.name = round.name;
            this.fillRound.id = round.id;
            this.fillRound.description = round.description;
            this.fillRound.start_date = round.start_date;
            this.fillRound.end_date = round.end_date;
            $("#edit-round").modal('show');
        },

        updateRound: function(id, scope){
            this.$validator.validateAll(scope).then(() => {
                var input = this.fillRound;
                this.$http.put('/vuerounds/'+id,input).then((response) => {
                    this.changePage(this.pagination.current_page);
                    this.fillRound = {'name':'','description':'','start_date':'','duration':'','end_date':'','id':''};
                    $("#edit-round").modal('hide');
                    toastr.success('Round Updated Successfully.', 'Success Alert', {timeOut: 5000});
                    this.errors.clear();
                }, (response) => {
                    this.formErrorsUpdate = response.data;
                });
            }).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000});
            });
        },
      
        changePage: function (page) {
            this.pagination.current_page = page;
            this.getVueRounds(page);
        },

        search: function() {
            // Clear the error message.
            this.error = '';
            // Empty the rounds array so we can fill it with the new rounds.
            this.rounds = [];
            // Set the loading property to true, this will display the "Searching..." button.
            this.loading = true;

            // Making a get request to our API and passing the query to it.
            this.$http.get('/api/search_round?q=' + this.query).then((response) => {
                // If there was an error set the error message, if not fill the rounds array.
                if(response.data.error)
                {
                    this.error = response.data.error;
                    toastr.error(this.error, 'Search Notification', {timeOut: 5000});
                }
                else
                {
                    this.rounds = response.data.data.data;
                    this.pagination = response.data.pagination;
                    toastr.success('The search results below were obtained.', 'Search Notification', {timeOut: 5000});
                }
                // The request is finished, change the loading to false again.
                this.loading = false;
                // Clear the query.
                this.query = '';
            });
        },

        srchEnrol: function() {
            // Clear the error message.
            this.error = '';
            // Empty the participants array so we can fill it with the new participants.
            this.participants = [];
            // Set the loading property to true, this will display the "Searching..." button.
            this.loading = true;

            // Making a get request to our API and passing the query to it.
            this.$http.get('/api/search_parts?q=' + this.srchParticipant).then((response) => {
                // If there was an error set the error message, if not fill the rounds array.
                if(response.data.error)
                {
                    this.error = response.data.error;
                    toastr.error(this.error, 'Search Notification', {timeOut: 5000});
                }
                else
                {
                    this.participants = response.data.data.data;
                    this.pagination = response.data.pagination;
                    toastr.success('The search results below were obtained.', 'Search Notification', {timeOut: 5000});
                }
                // The request is finished, change the loading to false again.
                this.loading = false;
                // Clear the query.
                this.srchParticipant = '';
            });
        },
        srchPrtEnrol: function() {
            // Clear the error message.
            this.error = '';
            // Empty the testerparticipants array so we can fill it with the new participants.
            this.testerparticipants = [];
            // Set the loading property to true, this will display the "Searching..." button.
            this.loading = true;

            // Making a get request to our API and passing the srchloadedprt query to it.
            this.$http.get('/api/search_participant?q=' + this.srchloadedprt).then((response) => {
                // If there was an error set the error message, if not fill the participant array.
                if(response.data.error)
                {
                    this.error = response.data.error;
                    toastr.error(this.error, 'Search Notification', {timeOut: 5000});
                }
                else
                {
                    this.testerparticipants = response.data.data.data;
                    this.pagination = response.data.pagination;
                    toastr.success('The search results below were obtained.', 'Search Notification', {timeOut: 5000});
                }
                // The request is finished, change the loading to false again.
                this.loading = false;
                // Clear the srchloadedprt query.
                this.srchloadedprt = '';
                // The participants who are checked won't be enrolled;
                this.checked = true;
            });
        },

        loadParticipants: function(roundID) {
            this.$http.get('/parts').then((response) => {
                //this.participants = response.data.data.data; 
                this.roundId = roundID;                             
                //this.pagination = response.data.pagination;
            }, (response) => {
                // 
            });
        },

        Participants: function(roundID) {
            
            this.$http.get('/loadparticipants' ).then((response) => {
                this.testerparticipants = response.data.data.data;
                console.log(this.testerparticipants); 
                this.pagination = response.data.pagination;
                this.roundId = roundID;
                this.checked = true;
            }, (response) => {
                // 
            });
        },

        enrolParticipants: function(){
		    let myForm = document.getElementById('partFrms');
            let formData = new FormData(myForm);
            console.log(formData);
            this.$http.post('/enrol', formData).then((response) => {
                this.changePage(this.pagination.current_page);
                //$("#enrol-participants").modal('hide');
                $("#load-participants").modal('hide');
                toastr.success('Participant(s) Enrolled Successfully.', 'Success Alert', {timeOut: 5000});
            }, (response) => {
                this.formErrors = response.data;
            });
  	    },

        loadEnrollments: function(round) {
            this.$http.get('/enrolled/'+round.id).then((response) => {
                this.testers = response.data.data.data;
                $("#enrolled-participants").modal('show');
            }, (response) => {
                //  console.log(response);
            });
        },

        getDurations: function() {
            this.$http.get('/duration').then((response) => {
                this.durations = response.data;
            }, (response) => {
                // console.log(response);
            });
        },

        uploadSheet: function(round){
            this.uploadify.id = round.id;
            $("#batch-enrolment").modal('show');
        },

        batchEnrol: function(){
            // this.$validator.validateAll().then(() => {
                var input = this.uploadify;
                this.duplicates = [];
                this.$http.post('/batch/enrol', input).then((response) => {
                    var resp = JSON.parse(response.body);
                    if(resp.errors.length > 0){
                        $("#dups").show();
                        this.duplicates = resp.errors;
                        toastr.success('Data uploaded Successfully.', 'Success Alert', {timeOut: 5000});
                        this.errors.clear();
                    }
                    else{
                        this.uploadify = {'id':'','excel':''};
                        $("#batch-enrolment").modal('hide');
                        toastr.success('Data uploaded Successfully.', 'Success Alert', {timeOut: 5000});
                        this.errors.clear();
                    }
                }, (response) => {
                    // 
                });
            /*}).catch(() => {
                toastr.error('Please fill in the fields as required.', 'Validation Failed', {timeOut: 5000});
            });*/
        },

        fileChanged(e)
        {
            console.log(e.target.files[0]);
            var fileReader = new FileReader();
            fileReader.readAsDataURL(e.target.files[0]);
            fileReader.onload = (e) => {
                this.uploadify.excel = e.target.result;
            }
        },

        downloadTesters(round) 
        {
        	let id = round.id;
            this.$http.get('/download/'+id).then((response) => {
                toastr.success('File Downloaded Successfully.', 'Success Alert', {timeOut: 5000});
            }, (response) => {
                // 
            });
        },
    }
});