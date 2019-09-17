$(document).ready(function () {

  $('.input-group.date').datepicker({format: "mm/dd/yyyy", autoclose: true}); 
  $(this).find("#fromstorage_gender").select2({
    placeholder: "Select Gender",
    allowClear: true,
    minimumResultsForSearch: -1
  });
  $(this).find("#fromcamera_gender").select2({
    placeholder: "Select Gender",
    allowClear: true,
    minimumResultsForSearch: -1
  });

  // Set active tab based on the device
  var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
  if (isMobile) {
    $('.nav-tabs a[href="#portlet_fromcamera"]').tab('show');    
  } else {
    $('.nav-tabs a[href="#portlet_fromstorage"]').tab('show');
  }  

});

function validateStorageEnrollForm() {
  return new Promise((resolve, reject) => {
    if($('#portraitDiv')[0].childElementCount === 0) {
      bootbox.alert({
        message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you imported an image file to enroll.'
      });
      reject();
  
    } else {
      validateImageFile($('#portraitInput').prop('files')[0]).then((resultCode) => { 
        if($('[name=fromstorage_name]').val() === '') {
          bootbox.alert({
            message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you entered the name.'
          });
          $('[name=fromstorage_name]').focus();
          reject();
        } else if($('[name=fromstorage_dob]').val() === '') {
          bootbox.alert({
            message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you entered the date of birth.'
          });
          $('[name=fromstorage_dob]').focus();
          reject();
        } else if($('[name=fromstorage_gender]').val() === '') {
          bootbox.alert({
            message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you selected a gender.'
          });
          $('[name=fromstorage_gender]').focus();
          reject();
        } else {
          resolve();
        }
  
      }).catch((resultCode) => {
        notifyInvalidImage(resultCode);
        reject();
  
      });
    }
  });
}

function validateCameraEnrollForm() {
  return new Promise((resolve, reject) => {
    if($('#portraitCamera').val() == '') {
      bootbox.alert({
        message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you took a photo to enroll.'
      });
      reject();
    } else {
      if($('[name=fromcamera_name]').val() === '') {
        bootbox.alert({
          message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you entered the name.'
        });
        $('[name=fromcamera_name]').focus();
        reject();
      } else if($('[name=fromcamera_dob]').val() === '') {
        bootbox.alert({
          message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you entered the date of birth.'
        });
        $('[name=fromcamera_dob]').focus();
        reject();
      } else if($('[name=fromcamera_gender]').val() === '') {
        bootbox.alert({
          message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you selected a gender.'
        });
        $('[name=fromcamera_gender]').focus();
        reject();
      } else {
        resolve();
      }
    }
  });
}

function enrollFromStorage() {
  
}

function enrollFromCamera() {
  
}


var takeSnapshotUI = createClickFeedbackUI();

var video;
var takePhotoButton;
var toggleFullScreenButton;
var switchCameraButton;
var amountOfCameras = 0;
var currentFacingMode = 'environment';


// Switch to camera enroll form
$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
  if($(e.target).attr("href") == '#portlet_fromcamera') {
    // do some WebRTC checks before creating the interface
    DetectRTC.load(function() {
      // do some checks
      if (DetectRTC.isWebRTCSupported == false) {
          alert('Please use Chrome, Firefox, iOS 11, Android 5 or higher, Safari 11 or higher');
      }
      else {
          if (DetectRTC.hasWebcam == false) {
              alert('Please install an external webcam device.');
          }
          else {
              amountOfCameras = DetectRTC.videoInputDevices.length;                   
              initCameraUI();
              initCameraStream();
          } 
      }
    });    
  }  
});

function initCameraUI() {
    
    video = document.getElementById('video');

    // camera UI buttons
    takePhotoButton = document.getElementById('takePhotoButton');
    switchCameraButton = document.getElementById('switchCameraButton');
    retakePhotoButton = document.getElementById('retakePhotoButton');
    
    takePhotoButton.addEventListener("click", function() {
        takeSnapshotUI();
        takeSnapshot();        
    });

    retakePhotoButton.addEventListener("click", function() {
        retakeSnapshotUI();
    });
        
    // -- switch camera part
    if(amountOfCameras > 1) {
        
        switchCameraButton.style.display = 'block';
        
        switchCameraButton.addEventListener("click", function() {

            if(currentFacingMode === 'environment') currentFacingMode = 'user';
            else                                    currentFacingMode = 'environment';

            initCameraStream();

        });  
    }    
}

function initCameraStream() {

    // stop any active streams in the window
    if (window.stream) {
        window.stream.getTracks().forEach(function(track) {
            track.stop();
        });
    }

    var constraints = { 
        audio: false, 
        video: {
            width: { min: 640, max: 640 },
            height: { min: 480, max: 480 },
            facingMode: currentFacingMode
        }
    };

    navigator.mediaDevices.getUserMedia(constraints).
    then(handleSuccess).catch(handleError);   

    function handleSuccess(stream) {

        window.stream = stream; // make stream available to browser console
        video.srcObject = stream;

        if(constraints.video.facingMode) {

            if(constraints.video.facingMode === 'environment') {
                switchCameraButton.setAttribute("aria-pressed", true);
            }
            else {
                switchCameraButton.setAttribute("aria-pressed", false);
            }
        }

        return navigator.mediaDevices.enumerateDevices();
    }

    function handleError(error) {
        if(error === 'PermissionDeniedError') {
            alert("Permission denied. Please refresh and give permission.");
        }        
    }
}

function takeSnapshot() {
    
    // if you'd like to show the canvas add it to the DOM
    var canvas = document.createElement('canvas');

    var width = video.videoWidth;
    var height = video.videoHeight;

    canvas.width = width;
    canvas.height = height;

    context = canvas.getContext('2d');
    context.drawImage(video, 0, 0, width, height);

    // change UI
    document.getElementById('imageSnapshot').setAttribute('src', canvas.toDataURL());
    document.getElementById('portraitCamera').value = canvas.toDataURL();
}

function createClickFeedbackUI() {

    // in order to give feedback that we actually pressed a button. 
    // we trigger a almost black overlay
    var overlay = document.getElementById("video_overlay");//.style.display;

    // sound feedback
    var sndClick = new Howl({ src: ['snd/click.mp3'] });

    var overlayVisibility = false;
    var timeOut = 150;

    function setFalseAgain() {
        overlayVisibility = false;	
        overlay.style.display = 'none';
        document.getElementById('imageSnapshot').style.display = 'block';
        document.getElementById('retakePhotoButton').style.display = 'block';
        document.getElementById('takePhotoButton').style.display = 'none';
    }

    return function() {
        if(overlayVisibility == false) {
            sndClick.play();
            overlayVisibility = true;
            overlay.style.display = 'block';
            setTimeout(setFalseAgain, timeOut);
        }   
    }
}

function retakeSnapshotUI() {

  // in order to give feedback that we actually pressed a button. 
  // we trigger a almost black overlay
  var overlay = document.getElementById("video_overlay");//.style.display;

  var overlayVisibility = false;
  var timeOut = 150;

  function setFalseAgain() {
      overlayVisibility = false;	
      overlay.style.display = 'none';
  }
  
  document.getElementById('imageSnapshot').style.display = 'none';
  document.getElementById('retakePhotoButton').style.display = 'none';
  document.getElementById('takePhotoButton').style.display = 'block';
  document.getElementById('portraitCamera').value = "";

  return function() {
      if(overlayVisibility == false) {
          overlayVisibility = true;
          overlay.style.display = 'block';
          setTimeout(setFalseAgain, timeOut);
      }   
  }
}