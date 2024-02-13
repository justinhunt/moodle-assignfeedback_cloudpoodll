define(['jquery', 'core/log','core/str','core/ajax','core/notification',],
    function ($, log, str, Ajax, notification) {
    "use strict"; // jshint ;_;
    /*
    This file does small report
     */

    log.debug('Fetch Submission: initialising');

    return {
        //controls
        controls: {},

        //init the module
        init: function () {
            log.debug('fetch submission: feedback cloudpoodll initialising');
            this.register_controls();
            this.register_events();
        },

        //load all the controls so we do not have to do it later
        register_controls: function () {
            log.debug('fetch submission: registering controls');
            this.controls.fetchsubmissionbutton = $('.fetchsubmissiontrigger');
        },

        //attach the various event handlers we need
        register_events: function () {
            var that = this;
            that.controls.fetchsubmissionbutton.click(function(e){
                log.debug('fetchsubmissiontrigger clicked');
                //collect target from  data-target
                var targetselector  = $(this).data('target');
                $(targetselector).html('I wanting to go the park with my 2 friend but a big dog running at us. very scared.');
                return false;
            });
        },//end of register events
    };

});