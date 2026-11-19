define(['jquery', 'core/log','core/str','core/ajax','core/notification','core/modal_save_cancel', 'core/modal_events', 'assignfeedback_cloudpoodll/correctionsmarkup'],
    function ($, log, str, Ajax, notification, ModalSaveCancel, ModalEvents,correctionsmarkup) {
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
        overwrite: 'OVERWRITE',
        overwritesuggestions: 'Overwrite the existing grammar suggestions?',
        overwritewarning: 'WARNING: Overwrite',

        //init the module
        init: function(){
            this.init_strings();
            this.register_controls();
            this.register_events();
        },

        init_strings: function(){
          var that =this;
          str.get_string('checking','assignfeedback_cloudpoodll').done(function(s){that.checking=s;});
          str.get_string('nosuggestions','assignfeedback_cloudpoodll').done(function(s){that.nosuggestions=s;});
          str.get_string('overwrite','assignfeedback_cloudpoodll').done(function(s){that.overwrite=s;});
          str.get_string('overwritesuggestions','assignfeedback_cloudpoodll').done(function(s){that.overwritesuggestions=s;});
          str.get_string('overwritewarning','assignfeedback_cloudpoodll').done(function(s){that.overwritewarning=s;});
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
                var srcselector = $(this).data('src');
                var targetselector  = $(this).data('target');
                var differenceselector  = $(this).data('difference');
                var language = $(this).data('language');
                var cmid = $(this).data('cmid');

                //if we already have grammar suggestions, we need to ask if we should overwrite
                if(that.get_value($(targetselector)).length>0) {
                    ModalSaveCancel.create({
                        title: that.overwritewarning,
                        body: that.overwritesuggestions,
                    })
                        .then(function (themodal) {
                            themodal.setSaveButtonText(that.overwrite);
                            var root = themodal.getRoot();
                            root.on(ModalEvents.save,
                                function (e) {
                                    that.check_grammar(that, srcselector, targetselector, differenceselector, language, cmid);
                                }
                            );
                            themodal.show();
                            return themodal;
                        });
                }else {
                    //if not we just fetch grammar suggestions
                    that.check_grammar(that, srcselector, targetselector, differenceselector, language, cmid);
                }
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

        check_grammar: function (that, srcselector, targetselector, differenceselector, language, cmid) {
            var src_element = $(srcselector);
            var target_element = $(targetselector);
            var difference_element = $(differenceselector);

            //do the check
            var text = that.get_value(src_element);
            //but quit if its empty
            if(!text || text==='' || text.trim()===''){
                return;
            }
            //keep what was there, to put it back if the check fails
            var previousvalue = that.get_value(target_element);
            that.set_value(target_element,that.checking, 'text');
            Ajax.call([{
                methodname: 'assignfeedback_cloudpoodll_check_grammar',
                args: {
                    cmid: cmid,
                    text: text,
                    language: language
                },
                done: function (ajaxresult) {
                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject.error) {
                        that.set_value(target_element, previousvalue, 'text');
                        notification.alert('', payloadobject.error);
                        return;
                    }
                    if(payloadobject.grammarerrors.length<3){
                        //hacky but fast way to flag no errors
                        that.set_value(target_element,that.nosuggestions, 'text');
                    }else{
                        that.set_value(target_element,payloadobject.suggestions, 'text');
                        //use this for html marked up suggestions (word numbers and spaces etc)
                        that.set_value(difference_element,payloadobject.markedupsuggestions, 'html');

                        //markup corrections
                        correctionsmarkup.justmarkup(differenceselector,
                            payloadobject.grammarerrors,
                            payloadobject.grammarmatches,
                            payloadobject.insertioncount);
                        //initially the preview container is hidden
                        //HACK .. to do un-hardcode this
                        $('.asf_cp_correctionspreview_cont').show();
                    }
                },
                fail: function (error) {
                    that.set_value(target_element, previousvalue, 'text');
                    notification.exception(error);
                }
            }]);
        },

    };//end of return value
});