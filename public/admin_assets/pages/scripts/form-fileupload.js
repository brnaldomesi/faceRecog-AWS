var FormFileUpload = function () {


    return {
        //main function to initiate the module
        init: function (finishCallback) {

             // Initialize the jQuery File Upload widget:
            $('#enrollForm').fileupload({
                disableImageResize: false,
                autoUpload: false,
                disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator.userAgent),
                maxFileSize: 5000000,
                acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
                // Uncomment the following to send cross-domain cookies:
                //xhrFields: {withCredentials: true},                
            });
            
            // Enable iframe cross-domain access via redirect option:
            $('#enrollForm').fileupload(
                'option',
                'redirect',
                window.location.href.replace(
                    /\/[^\/]*$/,
                    '/cors/result.html?%s'
                )
            ).on('fileuploaddone', function (e, data) {
                if ($(this).fileupload('active') == 1) {
                    finishCallback.call(this);
                }
            });

            // Load & display existing files:
            $('#enrollForm').addClass('fileupload-processing');
            $.ajax({
                // Uncomment the following to send cross-domain cookies:
                //xhrFields: {withCredentials: true},
                url: $('#enrollForm').attr("action"),
                dataType: 'json',
                context: $('#enrollForm')[0]
            }).always(function () {
                $(this).removeClass('fileupload-processing');
            }).done(function (result) {
                $(this).fileupload('option', 'done')
                .call(this, $.Event('done'), {result: result});
            });
        }

    };

}();