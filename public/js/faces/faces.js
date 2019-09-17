$(document).ready(function () {
	$.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
});

function validateCSVForm() {
  if(!$('[name=csv]').val()) {
    bootbox.alert({
      message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you imported a CSV file.'
    });
    return false;
  } else {
    if(!validateCSVFile($('#csvInput').val())) {
      bootbox.alert({
        message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please import the file with only CSV extension.'
      });
      return false;
    }
  }

  const organization = $("#organizationCSV").val();
  if(!organization) {
    bootbox.alert({
      message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you selected an organization.'
    });
    $('[name=organizationCSV]').focus();
    return false;
  }
  return true;
}

function validateManualReviewForm() {
  if($('[name=faceToken]').val() === '') {
    bootbox.alert({
      message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you entered a facetoken.'
    });
    return false;
  }
  return true;
}

function importCSV() {
  if(validateCSVForm()){
    Metronic.blockUI({
        animate: true,
    });

    var form = $('#csvForm')[0];
    var formData = new FormData(form);
    var apiUrl = $('#route-face-importcsv').val();
    //var returnUrl = $('#route-face-index').val();

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
            message: '<h4 style="color: #f00;">Error<br></h4>' + data.msg
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
  }
}

function setStateActive() {
  $('#faceImage').attr("src", '');
  $('#faceToken').attr("readonly", null);
  $('#btnSearch').attr("style", "display:show");
  $('#btnDiscard').attr("style", "display:none");
  $('#btnRemove').attr("style", "display:none");
}

function setStateInactive() {
  $('#faceToken').attr("readonly", "readonly");
  $('#btnSearch').attr("style", "display:none");
  $('#btnDiscard').attr("style", "display:show");
  $('#btnRemove').attr("style", "display:show");
}

function searchFaceImage() {
  if(validateManualReviewForm()){
    Metronic.blockUI({
        animate: true,
    });

    var form = $('#manualForm')[0];
    var formData = new FormData(form);
    var searchApiUrl = $('#route-face-searchimage').val();
    $.ajax({
      url : searchApiUrl,
      type : 'post',
      dataType : 'json',
      data: formData,
      contentType: false,
      processData: false,
      success: function(data) {
        Metronic.unblockUI();
        if(data.status == 200) {
          $('#faceImage').attr("src", data.msg);
          setStateInactive();
        } else {
          $('#faceImage').attr("src", '');
          bootbox.alert({
            message: '<h4 style="color: #f00;">Failure<br></h4>' + 'No image can be found matching with this token.<br>Please make sure you entered a valid facetoken.'
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
  }
}

function discardFaceImage() {
  if($('#faceImage').attr("src")){
    setStateActive();
  }
}

function removeFace() {
  if($('#faceImage').attr("src")){
    bootbox.confirm({
      message: '<h4 style="color: #f00;">Warning<br></h4>' + 'Are you sure to remove this image from database?',
      buttons: {
        confirm: {
            label: 'Yes',
            className: 'btn-success'
        },
        cancel: {
            label: 'No'
        }
      },
      callback: function(result){
        if(result) {
          Metronic.blockUI({
            animate: true
          });

          var removeApiUrl = $('#route-face-removeface').val();
          $.ajax({
            url : removeApiUrl,
            type : 'post',
            dataType: 'json',
            data: {
              'faceToken': $('#faceToken').val()
            },
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
        }
      }
    });
  }
}