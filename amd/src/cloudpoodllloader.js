define(['jquery', 'core/log',
        'https://cdn.jsdelivr.net/gh/justinhunt/cloudpoodll@latest/amd/build/cloudpoodll.min.js'],
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