function validatePhoto1() {
    return new Promise((resolve, reject) => {
        if($('#portraitDiv1')[0].childElementCount === 0) {
            bootbox.alert({
                message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you imported PHOTO1 image file.'
            });
            reject();
        } else {
            validateImageFile($('#portraitInput1').prop('files')[0]).then((resultCode) => { 
                resolve();
        
            }).catch((resultCode) => {
                notifyInvalidImage(resultCode, "PHOTO1");
                reject();
        
            });
        }
    });
}

function validatePhoto2() {
    return new Promise((resolve, reject) => {
        if($('#portraitDiv2')[0].childElementCount === 0) {
            bootbox.alert({
                message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you imported PHOTO2 image file.'
            });
            reject();
        } else {
            validateImageFile($('#portraitInput2').prop('files')[0]).then((resultCode) => { 
                resolve();
        
            }).catch((resultCode) => {
                notifyInvalidImage(resultCode, "PHOTO2");
                reject();
        
            });
        }
    });
}

function showCompareResult(data) {
    if(data > 0) {
        $("#compareResultCaption").attr("style", "display:show");
        $("#compareResultValue").attr("style", "display:show");
        $("#compareResultCaption").html("Similarity between these faces is: ");
        $("#similarityValue").html(data + '%');
    } else {
        $("#compareResultValue").attr("style", "display:show");
        $("#similarityValue").html('No match');
    }
}

function hideCompareResult() {
    $("#compareResultCaption").attr("style", "display:none");
    $("#compareResultValue").attr("style", "display:none");
}

function setStateActive() {
    $("#resultProcessButtons").attr("style", "display:none");
    $(".btn.default").removeAttr("disabled");
    $("#compareButton").removeAttr("disabled");
}

function setStateInactive() {
    $("#resultProcessButtons").attr("style", "display:show");
    $(".btn.default").attr("disabled", "disabled");
    $("#compareButton").attr("disabled", "disabled");
}

$(document).ready(function () {
    $("#portraitInput1").on("change", function() {
        hideCompareResult();
    });

    $("#portraitInput2").on("change", function() {
        hideCompareResult();
    });
});

function compareFaces() {
    validatePhoto1().then(() => {
        validatePhoto2().then(() => {
            Metronic.blockUI({
                animate: true,
            });
            
            var form = $('#compareCreateForm')[0];
            var formData = new FormData(form);
            var apiUrl = $('#route-compare-create').val();
            
            $.ajax({
                url : apiUrl,
                type : 'post',
                dataType : 'json',
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    Metronic.unblockUI();
                    if(data.status == 200) {
                        if(data.msg > 0)
                            setStateInactive();
                        showCompareResult(data.msg);
                        $('#compare-similarity-score').val(data.msg);
                    } else {
                        bootbox.alert({
                            message: '<h4 style="color: #f00;">Error<br></h4>' + 'No face is found in at least one of these images'
                        });
                    }
                },
                error: function (jqXHR, status, error) {
                    Metronic.unblockUI();
                    bootbox.alert({
                        message: '<h4 style="color: #f00;">Error<br></h4>' + error
                    });
                }
            });

        }).catch(() => {
    
        })
    }).catch(() => {

    })
}

function discardCompareResult() {
    setStateActive();
}

function saveCompareResult() {
    validatePhoto1().then(() => {
        validatePhoto2().then(() => {
            Metronic.blockUI({
                animate: true,
            });
            
            var form = $('#compareCreateForm')[0];
            var formData = new FormData(form);
            var apiUrl = $('#route-compare-save').val();
            formData.append('similarity', $('#compare-similarity-score').val());

            $.ajax({
                url : apiUrl,
                type : 'post',
                dataType : 'json',
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    Metronic.unblockUI();
                    if(data.status == 200) {
                        bootbox.alert({
                            message: '<h4 style="color: DodgerBlue;">Success<br></h4>' + data.msg
                        });
                    } else {
                        bootbox.alert({
                            message: '<h4 style="color: #f00;">Failure<br></h4>' + data.msg
                        });
                    }
                    setStateActive();
                },
                error: function (jqXHR, status, error) {
                    Metronic.unblockUI();
                    bootbox.alert({
                        message: '<h4 style="color: #f00;">Error<br></h4>' + error
                    });
                    setStateActive();
                }
            });
        }).catch(() => {
    
        })
    }).catch(() => {

    })
}