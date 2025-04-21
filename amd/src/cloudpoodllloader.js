define(['jquery', 'core/log',
        'assignfeedback_cloudpoodll/cloudpoodll'],
    function ($, log, CloudPoodll) {
        return {
            init: function (recorderid, thecallback) {
                CloudPoodll.createRecorder(recorderid);
                CloudPoodll.theCallback = thecallback;
                CloudPoodll.initEvents();
                $("iframe").on("load", function () {
                    $(".assignfeedback_cloudpoodll_recording_cont").css('background-image', 'none');
                });
            }
        };
    });