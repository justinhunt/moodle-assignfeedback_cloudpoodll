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
        checking: '... checking ...',
        nosuggestions: 'No suggestions',

        //init the module
        init: function(){
            log.debug('Grammar suggestions: feedback cloudpoodll initialising');
            this.init_strings();
            this.register_controls();
            this.register_events();
        },

        init_strings: function(){
            log.debug('Grammar suggestions: initialising strings');
          var that =this;
          str.get_string('checking','assignfeedback_cloudpoodll').done(function(s){that.checking=s;});
          str.get_string('nosuggestions','assignfeedback_cloudpoodll').done(function(s){that.nosuggestions=s;});
        },

        //load all the controls so we do not have to do it later
        register_controls: function(){
            log.debug('Grammar suggestions: registering controls');
            this.controls.checkgrammarbutton = $('.grammarsuggestionstrigger');
        },

        //attach the various event handlers we need
        register_events: function() {
            var that = this;
            log.debug('Grammar suggestions: registering events');
            log.debug(that.controls);

            that.controls.checkgrammarbutton.click(function(e){
                log.debug('grammarsuggestionstrigger clicked');
                //collect source and target from data-src and data-target
                var srcselector = $(this).data('src');
                var targetselector  = $(this).data('target');
                var differenceselector  = $(this).data('difference');
                var language = $(this).data('language');
                that.check_grammar(that,srcselector ,targetselector,differenceselector ,language);
                return false;
            });
        },//end of register events

        //to make this useful in case of a div/span/textarea ..
        set_value: function(element, value, type){
            if(element.is('textarea')){
                $(element).val(value);
            }else{
                switch(type){
                    case 'html':
                        $(element).html(value);
                        break;

                    case 'text':
                    default:
                        $(element).text(value);
                        break;
                }
            }
        },

        get_value: function(element){
            if(element.is('textarea')){
                return $(element).val();
            }else{
               return $(element).text();
            }
        },

        check_grammar: function (that,srcselector,targetselector,differenceselector, language) {
            log.debug('Grammar suggestions: checking grammar');
            var src_element = $(srcselector);
            var target_element = $(targetselector);
            var difference_element = $(differenceselector);


            //do the check
            var text = that.get_value(src_element);
            //but quit if its empty
            if(!text || text==='' || text.trim()===''){
                return;
            }
            that.set_value(target_element,that.checking, 'text');
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
                            that.set_value(target_element,that.nosuggestions, 'text');
                        }else{
                            that.set_value(target_element,payloadobject.suggestions, 'text');
                            //use this for html marked up suggestions (word numbers and spaces etc)
                            that.set_value(difference_element,payloadobject.markedupsuggestions, 'html');

                            var opts = [];
                            opts['sessionerrors'] = payloadobject.grammarerrors;
                            opts['sessionmatches'] = payloadobject.grammarmatches;
                        }

                    }else{
                        //something went wrong, user does not really need to know details
                        that.set_value(target_element,that.nosuggestions, 'text');
                        log.debug('result not fetched');
                    }

                },
                fail: notification.exception
            }]);
        },

    };//end of return value
});