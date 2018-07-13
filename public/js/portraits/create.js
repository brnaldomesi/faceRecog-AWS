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
  if($('#portraitDiv')[0].childElementCount === 0 && !$('[name=csv]').val()) { //Not choose portrait
    bootbox.alert('Select portrait or csv');
    return false;
  }

  if($('[name=name]').val() === '' && !$('[name=csv]').val()) {
    bootbox.alert('Fill the name')
    $('[name=name]').focus()
    return false;
  }
  if($('[name=dob]').val() === '' && !$('[name=csv]').val()) {
    bootbox.alert('Fill the birthday')
    $('[name=dob]').focus()
    return false;
  }
  return true;
}


function uploadPortrait() {

  if(validateEnrollForm()){

    Metronic.blockUI({
        animate: true,
    });

    var form = $('#enrollForm')[0]; // You need to use standard javascript object here
    var formData = new FormData(form);
    //formData.append('_token', $("input:hidden[name=_token]").val())
    formData.append('isCsv', $('[name=csv]').val())
    $.ajax({
      url : '/portraits',
      type : 'post',
      dataType : 'json',
      //data: {portraitType : 'image_base64', portraitData : portraitData, name: $('[name=name]').val(), dob : $('[name=dob]').val()},
      data: formData,
      contentType: false,
      processData: false,
      success: function(data) {
        Metronic.unblockUI();
        bootbox.alert(data.msg);
      }
    });
  }
}