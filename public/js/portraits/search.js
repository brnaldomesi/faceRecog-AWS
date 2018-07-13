var filteredResultArray = []
var filteredResult_per_faceSet = []
var filteredResultObject

$(document).ready(function () {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  initEvent()
});

function initEvent() {
  $("[name=searchPortraitInput]").on('change', function (e) {
    var file = $(this)[0].files[0];
    var thisObj = this;
    if(file) {
      orientation(file, function(base64img, value) {
        
        var imgTag = $('#searchPortraitDiv').children('img');
        //var rotated = $(imgTag).attr('src', base64img);
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

  $('#filteredCountLabel').text('')
}

function search() {
  if(validateSearchForm()){
    var htmlStr = '<tr><td class="text-center" colspan="3">Search results</td></tr>';
    $('#searchResultTable tbody').html(htmlStr);

    let portraitData = $('#searchPortraitDiv').children()[0].src;
    portraitData = portraitData.split(",")[1]
    let faceSetVal = $('#facesetSelect').val()
    Metronic.blockUI({
        animate:true,
        overlayColor: 'none'
    });
    var form = $('#searchForm')[0]; // You need to use standard javascript object here
    var formData = new FormData(form);
    $.ajax({
      url : '/portraits/search',
      type : 'POST',
      dataType : 'json',
      //data : {portraitType : 'image_base64', portraitData : portraitData},
      data : formData,
      //data: {portraitType : 'image_base64', portraitData : portraitData, name: $('[name=name]').val(), dob : $('[name=dob]').val()},
      contentType: false,
      processData: false,
      success: function(data) {
        Metronic.unblockUI();
        if (data['status'] == 201) {
          bootbox.alert(data['msg']);
          return;
        }

        var optionValues = [];
        filteredResultObject = data;
        $('#facesetSelect option').each(function() {
            if($(this).val() != '')
             optionValues.push($(this).val());
        });

        filteredResultArray = []
        var i
        for(i = 0; i < optionValues.length; i++) {
          filteredResultArray = filteredResultArray.concat(data[optionValues[i]])
        }

        if(faceSetVal != '') {
          faceSetVal = parseInt(faceSetVal);
          filteredResult_per_faceSet = data[faceSetVal]
        }
        else
          filteredResult_per_faceSet = filteredResultArray
        make_searchResult_table()
      }
    });
  }
}

$("#facesetSelect").live('change', function(){
  var value = this.value;
  if(typeof filteredResultObject !== 'undefined') {
    if(value != '') {
      filteredResult_per_faceSet = filteredResultObject[value]
    }
    else {
      filteredResult_per_faceSet = filteredResultArray  
    }
    make_searchResult_table()
  }
});

function make_searchResult_table() {
  var htmlStr = '';
  var count = filteredResult_per_faceSet.length
  if(count > 0) {
    var i
    for(i = 0; i < count; i++) {
      var record = filteredResult_per_faceSet[i]
      var savedPath = record.savedPath.replace('public/', '');

      htmlStr += '<tr>' + 
                    '<td>' +
                      '<a href="' + savedPath + '" class="fancybox-button" data-rel="fancybox-button">' +
                        '<img src="' + savedPath + '" style="height:45px;"></img>' +
                      '</a' +
                    '</td>' +
                    '<td>' + record.name + '</td>' +
                    '<td>' + record.dob + '</td>' +
                  '</tr>'
    }
  }
  else{
    htmlStr += '<tr><td class="text-center" colspan="3">No portrait matched.</td></tr>';
  }
  $('#searchResultTable tbody').html(htmlStr);
  $('#filteredCountLabel').text('Total count : ' + count)
  handleFancybox()
}

var handleFancybox = function() {
  if (!jQuery.fancybox) {
    return;
  }

  if ($(".fancybox-button").size() > 0) {
    $(".fancybox-button").fancybox({
      groupAttr: 'data-rel',
      prevEffect: 'none',
      nextEffect: 'none',
      closeBtn: true,
      helpers: {
        title: {
          type: 'inside'
        }
      }
    });
  }
};

function validateSearchForm() {
  if($('#searchPortraitDiv')[0].childElementCount === 0) { //Not choose portrait
    bootbox.alert('Select portrait');
    return false;
  }
  return true;
}
