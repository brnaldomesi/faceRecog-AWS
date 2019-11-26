
$(document).ready(function () {
    initEvent();
});

function quickSearch()
{
	
	var form = $('#quickSearchForm')[0];
	var formData = new FormData(form);
	
	validatePhoto().then(() => {
		validateGender().then(() => {
			validateReference().then(() => {
				
				Metronic.blockUI({
					animate: true,
				});
				
				$.ajax({
					url: $('#hidden-search-url').val(),
					type: 'post',
					dataType: 'json',
					data: formData,
					contentType: false,
					processData: false,
					success: function (response) {
						Metronic.unblockUI();
						// We encountered an error during the search
						if(response.status == 'faild') {
							
							bootbox.alert({
								message: '<h4 style="color: #f00;">Error<br></h4>' + response.msg
							});
							
							return;
						}
						
						var needle_image_src = $('.fileinput-preview img').attr('src');
						
						var match_count = showSearchResultDialog(response, needle_image_src);

						$("#portraitDiv1").empty();
						$("#gender")[0].selectedIndex = 0;
						$('#reference').val("");
						
					},
					error: function (jqXHR,status,error) {
						Metronic.unblockUI();
						bootbox.alert({
							message: '<h4 style="color: #f00;">Error<br></h4>'+error
						});
					}
				});
			})
		})
	});
}

var showSearchResultDialog = function (data, needle_image_src) {
		var match_count = 0;
		var title = body = '(Images depicted below are not positive identifications. They are to be used only as investigative leads)<br><br>';
		var body_no_result = '<div style="font-size:200px; color:lightgray; text-align:center; font-family:\'Lucida Sans Unicode\', \'Lucida Grande\', sans-serif">:(</div>';

		if (data.status == 204) {
			title = data.msg;
			body = body_no_result;
		} else {
			var flat = []; 

			body += '<div class="clearfix">'
			body += '	<div class="needle-side-bar col-md-2 col-sm-3 col-xs-6 col-xs-offset-3 col-sm-offset-0">';
			body += '		<a href="' + needle_image_src + '" class="fancybox-button" data-rel="fancybox-button">';
			body += '		<img src="' + needle_image_src + '" class="img-thumbnail" alt="" onerror="this.src=\'https://afrengine-images.s3.us-west-2.amazonaws.com/removed.jpg\'";/></a>';
			body += '	</div>';
			
			for (var i = 0, len = data.data_list.length; i < len; i++) {
				flat.push(data.data_list[i]);
				match_count += 1;
			}

			body += '<div class="my-bootbox-body col-md-10 col-sm-9 col-xs-12">';
			body += '<ul class="list-new ext1" style="padding-inline-start:0px;">';

			$.each(flat, function (index, value) {
				var image_url = value.image;
                if(image_url.substr(0, 7) == 'storage'){
                	image_url = s3_base_image_url + image_url;
				}
				
				similarity = Math.round(value.similarity);
				
				body += '<li style="margin: 10px 0;list-style:none;">';
				body += '	<div>';
				body += '		<a href="' + image_url + '" class="fancybox-button" data-rel="fancybox-button">';
				body += '		<img src="' + image_url + '" class="img-thumbnail" alt="" onerror="this.src=\'https://afrengine-images.s3.us-west-2.amazonaws.com/removed.jpg\'";/></a>';
				body += '	</div>';
				body += '	<div style="margin-top:20px; line-height:20px">'
				body += '		<div class="field">';
				body += '			<div><b>Similarity:</b></div>';
				body += '			<div>' + value.similarity.toFixed() + '%</div>';
				body += '			<div class="field" id="details_link_'+value.face_id+'"><a href="#" onclick="showFaceDetail(\''+value.face_id+'\')">(Click for details)</a></div>';
				body += '			<div class="field hidden" id="details_loading_'+value.face_id+'"><img src="https://www.afrengine.com/engine/img/input-spinner.gif"></div>';
				body += '		</div>';				
                body += '		<div class="field hidden" id="id_dv_face_detail_'+value.face_id+'">';
                body += '			<div><b>Identifiers:</b></div>';
                body += '			<div class="txt-identifiers"></div>';
                body += '		</div>';
                body += '		<div class="field hidden" id="id_dv_face_detail_organ_'+value.face_id+'">';
                body += '			<div><b>Organization:</b></div>';
                body += '			<div class="txt-organization"></div>';
                body += '		</div>';
                body += '		<div class="field hidden" id="id_dv_gallery_'+value.face_id+'">';
                body += '			<div><b>Photo Gallery:</b></div>';
                body += '			<div class="txt-gallery"></div>';
                body += '		</div>';
				body += '	</div>';
				body += '</li>';
			});

			body += '</ul>';
			body += '</div>';
			body += '</div>';

			if (match_count == 0) {
				title = 'No similar faces';
				body = body_no_result;
			} else if (match_count == 1) {
				title = '1 similar face was found';
			} else {
				title = match_count + ' similar faces were found';
			}
		}

		bootbox.dialog({
			title: title,
			message: body,
			onEscape: true,
			buttons: {
			    close: {
			        label: "Close",
			        className: 'blue',
			    }
			},
			className: match_count > 0 ? "wide" : ''
		});

		return match_count;
	};

function validatePhoto() {
    return new Promise((resolve, reject) => {
        if($('#portraitDiv1')[0].childElementCount === 0) {
            bootbox.alert({
                message: '<h4 style="color: #f00;">Error<br></h4>' + 'Please select an image to search'
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

function validateGender() {
	
	return new Promise((resolve,reject) => {
		if($('#gender').val() === '') {
			bootbox.alert({
			message: '<h4 style="color: #f00;">Error<br></h4>' + 'Select a gender'});
			$('#gender').focus();
			reject();
		} else {
			resolve();
		}
	})
}

function validateReference() {
	
	return new Promise((resolve,reject) => {
		if($('#reference').val() === '') {
			bootbox.alert({
			message: '<h4 style="color: #f00;">Error<br></h4>' + 'Please enter a reference number such as an event number or case number.'});
			$('#reference').focus();
			reject();
		} else {
			resolve();
		}
	})
}

function showFaceDetail(aws_face_id) {
    Metronic.blockUI({
        animate: true,
        overlayColor: 'none',
        centerY: true,
    });

	$("#details_link_"+aws_face_id).addClass('hidden');
	$('#details_loading_'+aws_face_id).removeClass('hidden');
	
    $.ajax({
        url: url_getfacedetailinfo,
        type: 'post',
        data: { 'aws_face_id' : aws_face_id },
        success: function (response) {
            Metronic.unblockUI();

            $('#id_dv_face_detail_'+aws_face_id).removeClass('hidden');
            $('#id_dv_face_detail_'+aws_face_id + ' .txt-identifiers').html(response.identifiers);

            $('#id_dv_face_detail_organ_'+aws_face_id).removeClass('hidden');
            $('#id_dv_face_detail_organ_'+aws_face_id + ' .txt-organization').html(response.organ_name);
			
			$('#details_loading_'+aws_face_id).addClass('hidden');
			
			if (response.galleryCount > 0) {
				$('#id_dv_gallery_'+aws_face_id).removeClass('hidden');
				$('#id_dv_gallery_'+aws_face_id+' .txt-gallery').html('<a href="#" onclick="showGallery(\''+aws_face_id+'\')">View ' + response.galleryCount + ' Photo(s)</a>');
			}

        },
        error: function (jqXHR, status, error) {
			
			$("#details_link_"+aws_face_id).removeClass('hidden');
			$('#details_loading_'+aws_face_id).addClass('hidden');			
			
			Metronic.unblockUI();
			bootbox.alert({
				message: '<h4 style="color: #f00;">Error<br></h4>' + error
			});
        }
    });
}

function showGallery(aws_face_id) {
	
	Metronic.blockUI({
        animate: true,
        overlayColor: 'none',
        centerY: true,
    });
	
	var flat = [];
	var match_count = 0;
	
	$.ajax({
		url: url_getpersongallery,
		type: 'post',
		data: { 'aws_face_id' : aws_face_id },
		success: function (response) {
			Metronic.unblockUI();
			
			body = '<div class="clearfix">'
			
			for (var i = 0, len = response.length; i < len; i++) {
				flat.push(response[i]);
				match_count += 1;
			}

			$.each(flat, function (index, value) {
				var image_url = value.savedPath;
                if(image_url.substr(0, 7) == 'storage'){
                	image_url = s3_base_image_url + image_url;
				}
				
				body += '<a href="' + image_url + '" class="fancybox-button" title="Photo Date: '+value.photoDate+'" data-rel="fancybox-button">';
				body += '<img src="' + image_url + '" class="img-thumbnail" alt="'+value.photoDate+'" onerror="this.src=\'https://afrengine-images.s3.us-west-2.amazonaws.com/removed.jpg\'";/></a>';
			});
			
			body += '</div>';
			
			bootbox.dialog({
				title: 'Photo Gallery',
				message: body,
				onEscape: true,
				buttons: {
					close: {
						label: "Close Gallery",
						className: 'red',
					}
				},
				className: match_count > 0 ? "wide" : ''
			});
			
		},
		error: function (jqXHR,status,error) {
			Metronic.unblockUI();
			bootbox.alert({
				message: '<h4 style="color: #f00;">Error<br></h4>' + error
			});
		}
	});
}

function initEvent() {

    $.ajaxSetup({
		headers: {
		  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
		}
    });
    
    var datatable_option = {
		"language": {
			"aria": {
				"sortAscending": ": activate to sort column ascending",
				"sortDescending": ": activate to sort column descending"
			},
			"emptyTable": "No data available in table",
			"info": "Showing _START_ to _END_ of _TOTAL_ entries",
			"infoEmpty": "No entries found",
			"infoFiltered": "(filtered from _MAX_ total entries)",
            "lengthMenu": "Show _MENU_ entries",
            "search": "Filter: ",
			"zeroRecords": "No matching records found"
		},
		"bStateSave": true, // save datatable state(pagination, sort, etc) in cookie.
		"lengthMenu": [
			[5, 15, 20, -1],
			[5, 15, 20, "All"] // change per page values here
		],
		"pageLength": 5,
		"order" : [[3, 'desc']],
        "searching": true
    };
    
	var table_quicksearch_history = $("#table-quicksearch-history").DataTable(datatable_option);
		
	$('#table-quicksearch-history').on('click', 'tbody tr', function (e) {
		if ($(this).find('td.dataTables_empty').length || e.target.tagName.toUpperCase() == "IMG") {
			return;
		}
		Metronic.blockUI({
			animate: true,
			overlayColor: 'none',
			cenrerY: true,
		});

		var needle_image_src = $(this).find('img').attr('src');
		$.post($("#route-quicksearch-history").val(), { 'history' : $(this).attr('history-no') }, function (response) {
			Metronic.unblockUI();
			showSearchResultDialog(response, needle_image_src);
		});
		
		gtag('event', 'page_view');
	});
}

