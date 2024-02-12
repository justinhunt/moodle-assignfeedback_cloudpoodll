define(['jquery', 'core/log','assignfeedback_cloudpoodll/definitions','core/str','core/ajax','core/notification','assignfeedback_cloudpoodll/correctionsmarkup'],
    function ($, log, def, str, Ajax, notification,correctionsmarkup) {
    "use strict"; // jshint ;_;
    /*
    This file does small report
     */

    log.debug('Grammar suggestions: initialising');

    return {
        //controls
        controls: {},
        ready: false,
        checking: '... checking ...',
        nosuggestions: 'No suggestions',

        //init the module
        init: function(){
            this.ready=false;
            this.init_strings();
            this.register_controls();
            this.register_events();
        },

        init_strings: function(){
          var that =this;
          str.get_string('checking','assignfeedback_cloudpoodll').done(function(s){that.checking=s;});
          str.get_string('nosuggestions','assignfeedback_cloudpoodll').done(function(s){that.nosuggestions=s;});
        },

        //load all the controls so we do not have to do it later
        register_controls: function(){
            this.controls.checkgrammarbutton = $('.grammarsuggestionstrigger');
        },

        //attach the various event handlers we need
        register_events: function() {
            var that = this;
            that.controls.checkgrammarbutton.click(function(e){
                //collect source and target from data-src and data-target
                var srcelement = $(this).data('src');
                var targetelement = $(this).data('target');
                var language = $(this).data('language');
                that.check_grammar(that,srcelement,targetelement,language);
                return false;
            });
        },//end of register events

        check_grammar: function (that,srcelement,targetelement,language) {

            //do the check
            var text = $(srcelement).val();
            //but quit if its empty
            if(!text || text==='' || text.trim()===''){
                return;
            }
            $(targetelement).text(that.checking);
            Ajax.call([{
                methodname: 'assignfeedback_cloudpoodll_check_grammar',
                args: {
                    text: text,
                    language: language
                },
                done: function (ajaxresult) {

                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject) {
                        if(payloadobject.grammarerrors.length<3){
                            //hacky but fast way to flag no errors
                            $(targetelement).text(that.nosuggestions);
                        }else{
                            $(targetelement).html(payloadobject.suggestions);
                            var opts = [];
                            opts['sessionerrors'] = payloadobject.grammarerrors;
                            opts['sessionmatches'] = payloadobject.grammarmatches;
                            correctionsmarkup.init(opts);
                        }

                    }else{
                        //something went wrong, user does not really need to know details
                        $(targetelement).text(that.nosuggestions);
                        log.debug('result not fetched');
                    }

                },
                fail: notification.exception
            }]);
        },

    };//end of return value
});