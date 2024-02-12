define(['jquery', 'core/log','core/str','core/ajax','core/notification','assignfeedback_cloudpoodll/correctionsmarkup'],
    function ($, log, str, Ajax, notification,correctionsmarkup) {
    "use strict"; // jshint ;_;
    /*
    This file does small report
     */

    log.debug('Grammar suggestions: initialising');

    return {
        //controls
        controls: {},
        previewtimer: null,

        //init the module
        init: function(){
            log.debug('Preview corrections: feedback cloudpoodll initialising');
            this.init_strings();
            this.register_controls();
            this.register_events();
        },

        init_strings: function(){
          var that =this;
          //str.get_string('checking','assignfeedback_cloudpoodll').done(function(s){that.checking=s;});
        },

        //load all the controls so we do not have to do it later
        register_controls: function(){
            log.debug('Grammar suggestions: registering controls');
            this.controls.submittedtextarea = $('#id_submittedtext');
            this.controls.correctionstextarea = $('#id_correctedtext');
            this.controls.previewcontainer = $('.asf_cp_corrections_cont');
        },

        //attach the various event handlers we need
        register_events: function() {
            var that = this;


            that.controls.correctionstextarea.on('input', function() {
                clearTimeout(that.previewtimer);   // clear the timer whenever the input is changed
                that.previewtimer = setTimeout(function(
                ) {  // after 5s, log something to the console
                    that.render_and_markup();
                }, 5000);
            });

        },//end of register events



        render_and_markup: function () {
            var that = this;
            var thepassage = that.controls.submittedtextarea.val();
            var thecorrections = that.controls.correctionstextarea.val();
            //if no passage or corrections do nothing
            if(thepassage.length<1 || thecorrections.length<1){
                return;
            }

            Ajax.call([{
                methodname: 'assignfeedback_cloudpoodll_render_diffs',
                args: {
                    passage: thepassage,
                    corrections: thecorrections,
                },
                done: function (ajaxresult) {

                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject) {
                        if(payloadobject.markedupsuggestions.length>3){
                            that.controls.previewcontainer.html(payloadobject.markedupsuggestions);
                            correctionsmarkup.justmarkup( that.controls.previewcontainer,payloadobject.grammarerrors,payloadobject.grammarmatches);
                        }
                    }else{
                        //something went wrong, user does not really need to know details
                        log.debug('result not fetched');
                    }

                },
                fail: notification.exception
            }]);
        },

    };//end of return value
});