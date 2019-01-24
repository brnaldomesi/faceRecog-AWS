$(document).ready(function () {
    initEvent();
    
    // create a collection.
    $('#id_btn_create_collection').on('click',function(){
        let name = $('#id_collection_name').val();
        $.ajax({
            url: base_url + '/awstest_createCollection',
            type: 'post',
            data: { 'name' : name },
            success: function (response) {
    
                bootbox.alert("Success!");            
            },
            error: function (jqXHR, status, error) {
                bootbox.alert(status + "<br>" + error);
            }
        });
    })

    // indexing faces.
    $('#id_btn_indexing_face').on('click',function(){
        var key = $('#id_txt_key_name').val();
        $.ajax({
            url: base_url + '/awstest_faceindexing',
            type: 'post',
            data: { 'key': key },
            success: function (response) {
                
                bootbox.alert("Success!");            
            },
            error: function (jqXHR, status, error) {
                bootbox.alert(status + "<br>" + error);
            }
        });
    })

    // search image.
    $('#id_btn_search_face').on('click',function(){
		
		var startTime;
		var endTime;
		
		startTime = new Date();
		
        $.ajax({
            url: base_url + '/awstest_searchface',
            type: 'post',
            data: {  },
            success: function (response) {
                if(response == 'faild'){
                    bootbox.alert("Faild!");                
                    return;
                }

                // show the images on the search result.
                var html_str = '';
                var images = response;
                for(var i = 0; i < images.length; i++){
                    html_str += '<div class="col-md-6"><img src="'+images[i].image+'" width="250" height="auto"><br/><span>'+images[i].similarity+'</span></div>';
                }
                $('#id_dv_search_result').html(html_str);
				
				endTime = new Date();
				
				bootbox.alert("Success! [Started: " + startTime + "] [Ended: " + endTime + "]");            

            },
            error: function (jqXHR, status, error) {
                bootbox.alert(status + "<br>" + error);
            }
        });
    })


    // delete face from the collection.
    $('#id_btn_delete_face').on('click',function(){
        var face_id = $('#id_txt_del_face_id').val();
        $.ajax({
            url: base_url + '/awstest_delete_face',
            type: 'post',
            data: { 'face_id': face_id },
            success: function (response) {
    
                bootbox.alert("Success!");            
            },
            error: function (jqXHR, status, error) {
                bootbox.alert(status + "<br>" + error);
            }
        });
    })
});

function initEvent(){
    $.ajaxSetup({
		headers: {
		  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
		}
	});
}