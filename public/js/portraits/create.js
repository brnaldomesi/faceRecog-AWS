$(document).ready(function () {
	
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  initEvent()
});

function initEvent() {
	
  $("[name=portraitInput]").on('change', function (e) {
    var file = $(this)[0].files[0];
    var thisObj = this;
    if(file) {
      orientation(file, function(base64img, value) {
        
        var imgTag = $('#portraitDiv').children('img');
        if(value) {
          $(imgTag).css('transform', rotation[value]);
        }

        // resetOrientation(base64img, value,  function(resetBase64Image) {      
        //   $(imgTag).attr('src', resetBase64Image);
        //   $(thisObj)[0].files[0].result = resetBase64Image
        // })

      });
    }
  })

  ComponentsPickers.init();
}

function validateEnrollForm() {
  return new Promise((resolve, reject) => {
    if($('#portraitDiv')[0].childElementCount === 0) {
      bootbox.alert({
        message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please import a photo to be enrolled.'
      });
      reject();
  
    } else {
      validateImageFile($('#portraitInput').prop('files')[0]).then((resultCode) => { 
        if($('[name=identifiers]').val() === '') {
          bootbox.alert({
            message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please enter the identifier of the photo.'
          });
          $('[name=identifiers]').focus();
          reject();
        }
        else if($('[name=gender]').val() === '') {
          bootbox.alert({
            message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please select the perceived gender.'
          });
          $('[name=gender]').focus();
          reject();
        }
        else {
          resolve();
        }

      }).catch((resultCode) => {
        notifyInvalidImage(resultCode);
        reject();
  
      });
    }
  });
}


function uploadPortrait() {
  validateEnrollForm().then(() => {
    Metronic.blockUI({
        animate: true,
    });

    var form = $('#enrollForm')[0]; // You need to use standard javascript object here
    var formData = new FormData(form);
    
    var pathArray = window.location.href.split( '/' );
    
    var pathStr = '';
    for (var i = 0; i < pathArray.length - 1; i++)
    {
      pathStr = pathStr + pathArray[i];
      if (i < pathArray.length - 2) {
        pathStr = pathStr + '/';
      }
    }

    $.ajax({
      url : pathStr,
      type : 'post',
      dataType : 'json',
      data: formData,
      contentType: false,
      processData: false,
      success: function(data) {
        Metronic.unblockUI();
        bootbox.alert({
            message: '<h4 style="color: DodgerBlue;">Success<br></h4>' + data.msg
        });
      },
      error: function (jqXHR, status, error) {
        Metronic.unblockUI();
        bootbox.alert({
          message: '<h4 style="color: #f00;">Error<br></h4>' + error
        });
      }
    });
  }).catch(() => {

  });
}