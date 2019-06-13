function notifyInvalidImage(invalidCode, invalidTarget="") {
    switch(invalidCode) {
        case 0:
            bootbox.alert({
                message: '<h4 style="color: #f00;">Failure<br></h4>' + invalidTarget + ' Invalid image resolution.<br>Please make sure your image has resolution of larger than 80 * 80.'
            });
            break;
        case -1:
            bootbox.alert({
                message: '<h4 style="color: #f00;">Failure<br></h4>' + invalidTarget + ' Invalid image file.'
            });
            break;
        case -2:
            bootbox.alert({
                message: '<h4 style="color: #f00;">Failure<br></h4>' + invalidTarget + ' Invalid file size.<br>Please make sure the image file you use is not larger than 5MB.'
            });
            break;
        case -3:
            bootbox.alert({
                message: '<h4 style="color: #f00;">Failure<br></h4>' + invalidTarget + ' Invalid file type.<br>Please import image files with only PNG or JPG extension.'
            });
            break;
        default:
            break;
    }
}

function validateImageFile(file) {
    return new Promise((resolve, reject) => {
        // validation of file format
        const fileName = file.name;
        let idxDot = fileName.lastIndexOf(".") + 1;
        let extFile = fileName.substr(idxDot, fileName.length).toLowerCase();
        if(extFile!="jpg" && extFile!="jpeg" && extFile!="png") {
            // invalid file type 
            reject(-3);  
        }

        // validation of file size
        const maxFileSize = 5242880; //5MB
        if(file.size > maxFileSize) {
            // invalid file size
            reject(-2);
        }

        // validation of image resolution
        let img = new Image();
        const minWidth = 80;
        const minHeight = 80;
        
        img.onload = function() {
            const imgWidth = this.width;
            const imgHeight = this.height;
            if(minWidth > imgWidth || minHeight > imgHeight) {
                // invalid resolution
                reject(0); 
            } else {
                resolve(1);
            }
        };
        img.onerror = function() {
            // invalid file
            reject(-1); 
        }
        img.src = window.URL.createObjectURL(file);
    });    
}

function validateCSVFile(fileName) {
    let idxDot = fileName.lastIndexOf(".") + 1;
    let extFile = fileName.substr(idxDot, fileName.length).toLowerCase();
    if (extFile=="csv") {
        return true;
    } else {
        return false;
    }   
}