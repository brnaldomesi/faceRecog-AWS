var filteredResultArray = []
var filteredResult_per_faceSet = []
var filteredResultObject
var rotation = {
  1: 'rotate(0deg)',
  3: 'rotate(180deg)',
  6: 'rotate(90deg)',
  8: 'rotate(270deg)'
};

$(document).ready(function () {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  $("[name=searchPortraitInput]").on('change', function (e) {
    var file = $(this)[0].files[0];
    var thisObj = this;
    if(file) {
      orientation(file, function(base64img, value) {
        $('#placeholder1').attr('src', base64img);
        
        var imgTag = $('#searchPortraitDiv').children('img');
        //var rotated = $(imgTag).attr('src', base64img);
        if(value) {
          //$(imgTag).css('transform', rotation[value]);
        }

        resetOrientation(base64img, value,  function(resetBase64Image) {      
          $(imgTag).attr('src', resetBase64Image);
          $(thisObj)[0].files[0].result = resetBase64Image
        })


      });
    }
  })
});

function resetOrientation(srcBase64, srcOrientation, callback) {
  var img = new Image();  

  img.onload = function() {
    var width = img.width,
        height = img.height,
        canvas = document.createElement('canvas'),
        ctx = canvas.getContext("2d");
    
    // set proper canvas dimensions before transform & export
    if (4 < srcOrientation && srcOrientation < 9) {
      canvas.width = height;
      canvas.height = width;
    } else {
      canvas.width = width;
      canvas.height = height;
    }
  
    // transform context before drawing image
    switch (srcOrientation) {
      case 2: ctx.transform(-1, 0, 0, 1, width, 0); break;
      case 3: ctx.transform(-1, 0, 0, -1, width, height ); break;
      case 4: ctx.transform(1, 0, 0, -1, 0, height ); break;
      case 5: ctx.transform(0, 1, 1, 0, 0, 0); break;
      case 6: ctx.transform(0, 1, -1, 0, height , 0); break;
      case 7: ctx.transform(0, -1, -1, 0, height , width); break;
      case 8: ctx.transform(0, -1, 1, 0, 0, width); break;
      default: break;
    }

    // draw image
    ctx.drawImage(img, 0, 0);

    // export base64
    callback(canvas.toDataURL());
  };

  img.src = srcBase64;
}

function _arrayBufferToBase64( buffer ) {
  var binary = ''
  var bytes = new Uint8Array( buffer )
  var len = bytes.byteLength;
  for (var i = 0; i < len; i++) {
    binary += String.fromCharCode( bytes[ i ] )
  }
  return window.btoa( binary );
}

function validateEnrollForm() {
  if($('#portraitDiv')[0].childElementCount === 0) { //Not choose portrait
    bootbox.alert('Select portrait');
    return false;
  }
  if($('[name=identifiers]').val() === '') {
    bootbox.alert('Please enter some identifiers for this image')
    $('[name=identifiers]').focus()
    return false;
  }
  if($('[name=gender]').val() === '') {
    bootbox.alert('Select a perceived gender')
    $('[name=gender]').focus()
    return false;
  }
  return true;
}

function validateSearchForm() {
  if($('#searchPortraitDiv')[0].childElementCount === 0) { //Not choose portrait
    bootbox.alert('Select portrait');
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
    
    $.ajax({
      url : '/portraits',
      type : 'post',
      dataType : 'json',
      data: formData,
      contentType: false,
      processData: false,
      success: function(data) {
        Metronic.unblockUI();
        bootbox.alert(data.msg);
      },
	  error: function(data) {
		Metronic.unblockUI();
		bootbox.alert("Something went wrong.");
	  }
    });
  }
}

var orientation = function (file, callback) {
    var fileReader = new FileReader();
    fileReader.onloadend = function () {
        var base64img = "data:" + file.type + ";base64," + _arrayBufferToBase64(fileReader.result);
        var scanner = new DataView(fileReader.result);
        var idx = 0;
        var value = 1; // Non-rotated is the default
        if (fileReader.result.length < 2 || scanner.getUint16(idx) != 0xFFD8) {
            // Not a JPEG
            if (callback) {
                callback(base64img, value);
            }
            return;
        }
        idx += 2;
        var maxBytes = scanner.byteLength;
        var littleEndian = false;
        while (idx < maxBytes - 2) {
            var uint16 = scanner.getUint16(idx, littleEndian);
            idx += 2;
            switch (uint16) {
                case 0xFFE1: // Start of EXIF
                    var endianNess = scanner.getUint16(idx + 8);
                    // II (0x4949) Indicates Intel format - Little Endian
                    // MM (0x4D4D) Indicates Motorola format - Big Endian
                    if (endianNess === 0x4949) {
                        littleEndian = true;
                    }
                    var exifLength = scanner.getUint16(idx, littleEndian);
                    maxBytes = exifLength - idx;
                    idx += 2;
                    break;
                case 0x0112: // Orientation tag
                    // Read the value, its 6 bytes further out
                    // See page 102 at the following URL
                    // http://www.kodak.com/global/plugins/acrobat/en/service/digCam/exifStandard2.pdf
                    value = scanner.getUint16(idx + 6, littleEndian);
                    maxBytes = 0; // Stop scanning
                    break;
            }
        }
        if (callback) {
            callback(base64img, value);
        }
    }
    fileReader.readAsArrayBuffer(file);
};

function search() {
  if(validateSearchForm()){

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
                    '<td>' + record.identifiers + '</td>' +
                    '<td>' + record.gender + '</td>' +
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

// $("#enrollForm").submit(function(event){
//  event.preventDefault();
//  var formData = $(this).serialize();
//  $.ajax({
//      url: '/portraits',
//      type: 'POST',
//      data: formData,
//      async: false,
//       cache: false,
//       contentType: false,
//       processData: false,
//      success: function (data) {
//         bootbox.alert(data)
//      }
//  });
//  return false;
// });
/* ===========================================================
 * Bootstrap: fileinput.js v3.1.3
 * http://jasny.github.com/bootstrap/javascript/#fileinput
 * ===========================================================
 * Copyright 2012-2014 Arnold Daniels
 *
 * Licensed under the Apache License, Version 2.0 (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */

+function ($) { "use strict";

  var isIE = window.navigator.appName == 'Microsoft Internet Explorer'

  // FILEUPLOAD PUBLIC CLASS DEFINITION
  // =================================

  var Fileinput = function (element, options) {
    this.$element = $(element)
    
    this.$input = this.$element.find(':file')
    if (this.$input.length === 0) return

    this.name = this.$input.attr('name') || options.name

    this.$hidden = this.$element.find('input[type=hidden][name="' + this.name + '"]')
    if (this.$hidden.length === 0) {
      this.$hidden = $('<input type="hidden">').insertBefore(this.$input)
    }

    this.$preview = this.$element.find('.fileinput-preview')
    var height = this.$preview.css('height')
    if (this.$preview.css('display') !== 'inline' && height !== '0px' && height !== 'none') {
      this.$preview.css('line-height', height)
    }
        
    this.original = {
      exists: this.$element.hasClass('fileinput-exists'),
      preview: this.$preview.html(),
      hiddenVal: this.$hidden.val()
    }
    
    this.listen()
  }
  
  Fileinput.prototype.listen = function() {
    this.$input.on('change.bs.fileinput', $.proxy(this.change, this))
    $(this.$input[0].form).on('reset.bs.fileinput', $.proxy(this.reset, this))
    
    this.$element.find('[data-trigger="fileinput"]').on('click.bs.fileinput', $.proxy(this.trigger, this))
    this.$element.find('[data-dismiss="fileinput"]').on('click.bs.fileinput', $.proxy(this.clear, this))
  },

  Fileinput.prototype.change = function(e) {
    var files = e.target.files === undefined ? (e.target && e.target.value ? [{ name: e.target.value.replace(/^.+\\/, '')}] : []) : e.target.files
    
    e.stopPropagation()

    if (files.length === 0) {
      this.clear()
      return
    }

    this.$hidden.val('')
    this.$hidden.attr('name', '')
    this.$input.attr('name', this.name)

    var file = files[0]

    if (this.$preview.length > 0 && (typeof file.type !== "undefined" ? file.type.match(/^image\/(gif|png|jpeg)$/) : file.name.match(/\.(gif|png|jpe?g)$/i)) && typeof FileReader !== "undefined") {
      var reader = new FileReader()
      var preview = this.$preview
      var element = this.$element

      function resetOrientation(srcBase64, srcOrientation, callback) {
        var img = new Image();  

        img.onload = function() {
          var width = img.width,
              height = img.height,
              canvas = document.createElement('canvas'),
              ctx = canvas.getContext("2d");
          
          // set proper canvas dimensions before transform & export
          if (4 < srcOrientation && srcOrientation < 9) {
            canvas.width = height;
            canvas.height = width;
          } else {
            canvas.width = width;
            canvas.height = height;
          }
        
          // transform context before drawing image
          switch (srcOrientation) {
            case 2: ctx.transform(-1, 0, 0, 1, width, 0); break;
            case 3: ctx.transform(-1, 0, 0, -1, width, height ); break;
            case 4: ctx.transform(1, 0, 0, -1, 0, height ); break;
            case 5: ctx.transform(0, 1, 1, 0, 0, 0); break;
            case 6: ctx.transform(0, 1, -1, 0, height , 0); break;
            case 7: ctx.transform(0, -1, -1, 0, height , width); break;
            case 8: ctx.transform(0, -1, 1, 0, 0, width); break;
            default: break;
          }

          // draw image
          ctx.drawImage(img, 0, 0);

          // export base64
          callback(canvas.toDataURL());
        };

        img.src = srcBase64;
      }

      function getOrientation(file, callback) {
          var reader = new FileReader();
          reader.onload = function(e) {

              var view = new DataView(e.target.result);
              if (view.getUint16(0, false) != 0xFFD8)
              {
                  return callback(-2);
              }
              var length = view.byteLength, offset = 2;
              while (offset < length) 
              {
                  if (view.getUint16(offset+2, false) <= 8) return callback(-1);
                  var marker = view.getUint16(offset, false);
                  offset += 2;
                  if (marker == 0xFFE1) 
                  {
                      if (view.getUint32(offset += 2, false) != 0x45786966) 
                      {
                          return callback(-1);
                      }

                      var little = view.getUint16(offset += 6, false) == 0x4949;
                      offset += view.getUint32(offset + 4, little);
                      var tags = view.getUint16(offset, little);
                      offset += 2;
                      for (var i = 0; i < tags; i++)
                      {
                          if (view.getUint16(offset + (i * 12), little) == 0x0112)
                          {
                              return callback(view.getUint16(offset + (i * 12) + 8, little));
                          }
                      }
                  }
                  else if ((marker & 0xFF00) != 0xFF00)
                  {
                      break;
                  }
                  else
                  { 
                      offset += view.getUint16(offset, false);
                  }
              }
              return callback(-1);
          };
          reader.readAsArrayBuffer(file);
      }


      reader.onload = function(re) {
        var $img = $('<img>')

        getOrientation(files[0], function(orientation) {
            resetOrientation(re.target.result, orientation, function(resetBase64Image) {
              $img[0].src = resetBase64Image
              files[0].result = resetBase64Image
              
              element.find('.fileinput-filename').text(file.name)
              
              // if parent has max-height, using `(max-)height: 100%` on child doesn't take padding and border into account
              if (preview.css('max-height') != 'none') $img.css('max-height', parseInt(preview.css('max-height'), 10) - parseInt(preview.css('padding-top'), 10) - parseInt(preview.css('padding-bottom'), 10)  - parseInt(preview.css('border-top'), 10) - parseInt(preview.css('border-bottom'), 10))
              $img.css('vertical-align', 'middle')
              preview.html($img)
              element.addClass('fileinput-exists').removeClass('fileinput-new')

              element.trigger('change.bs.fileinput', files)
            });
        })
      }

      reader.readAsDataURL(file)
    } else {
      this.$element.find('.fileinput-filename').text(file.name)
      this.$preview.text(file.name)
      
      this.$element.addClass('fileinput-exists').removeClass('fileinput-new')
      
      this.$element.trigger('change.bs.fileinput')
    }
  },

  Fileinput.prototype.clear = function(e) {
    if (e) e.preventDefault()
    
    this.$hidden.val('')
    this.$hidden.attr('name', this.name)
    this.$input.attr('name', '')

    //ie8+ doesn't support changing the value of input with type=file so clone instead
    if (isIE) { 
      var inputClone = this.$input.clone(true);
      this.$input.after(inputClone);
      this.$input.remove();
      this.$input = inputClone;
    } else {
      this.$input.val('')
    }

    this.$preview.html('')
    this.$element.find('.fileinput-filename').text('')
    this.$element.addClass('fileinput-new').removeClass('fileinput-exists')
    
    if (e !== undefined) {
      this.$input.trigger('change')
      this.$element.trigger('clear.bs.fileinput')
    }
  },

  Fileinput.prototype.reset = function() {
    this.clear()

    this.$hidden.val(this.original.hiddenVal)
    this.$preview.html(this.original.preview)
    this.$element.find('.fileinput-filename').text('')

    if (this.original.exists) this.$element.addClass('fileinput-exists').removeClass('fileinput-new')
     else this.$element.addClass('fileinput-new').removeClass('fileinput-exists')
    
    this.$element.trigger('reset.bs.fileinput')
  },

  Fileinput.prototype.trigger = function(e) {
    this.$input.trigger('click')
    e.preventDefault()
  }

  
  // FILEUPLOAD PLUGIN DEFINITION
  // ===========================

  var old = $.fn.fileinput
  
  $.fn.fileinput = function (options) {
    return this.each(function () {
      var $this = $(this),
          data = $this.data('bs.fileinput')
      if (!data) $this.data('bs.fileinput', (data = new Fileinput(this, options)))
      if (typeof options == 'string') data[options]()
    })
  }

  $.fn.fileinput.Constructor = Fileinput


  // FILEINPUT NO CONFLICT
  // ====================

  $.fn.fileinput.noConflict = function () {
    $.fn.fileinput = old
    return this
  }


  // FILEUPLOAD DATA-API
  // ==================

  $(document).on('click.fileinput.data-api', '[data-provides="fileinput"]', function (e) {
    var $this = $(this)
    if ($this.data('bs.fileinput')) return
    $this.fileinput($this.data())
      
    var $target = $(e.target).closest('[data-dismiss="fileinput"],[data-trigger="fileinput"]');
    if ($target.length > 0) {
      e.preventDefault()
      $target.trigger('click.bs.fileinput')
    }
  })

}(window.jQuery);