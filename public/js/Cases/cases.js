$(document).ready(function () {
	initEvent();

});

function showFaceDetail(aws_face_id){
    Metronic.blockUI({
        animate: true,
        overlayColor: 'none',
        cenrerY: true,
    });
    $.ajax({
        url: base_url + 'cases/getDetailFaceInfo',
        type: 'post',
        data: { 'aws_face_id' : aws_face_id },
        success: function (response) {
        	console.log(response);
            Metronic.unblockUI();

            $('#id_dv_face_detail_'+aws_face_id).removeClass('hidden');
            $('#id_dv_face_detail_'+aws_face_id + ' .txt-identifiers').html(response.identifiers);

        },
        error: function (jqXHR, status, error) {
            Metronic.unblockUI();
            bootbox.alert(status + "<br>" + error);
        }
    });
}

function initEvent() {

	$(".fileupload-buttonbar button.clean").click(function () {
		$("#enrollForm table tbody").empty();
	});

	$('#enrollForm').bind('fileuploadsubmit', function (e, data) {
		data.formData = data.context.find('select').serializeArray();
	});

	$.ajaxSetup({
		headers: {
		  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
		}
	});

	$("select[name=status]").change(function () {
		if ($(this).val() == 'CLOSED') {
			$("textarea[name=dispo]").prop('disabled', false)
				.parents(".form-group").removeClass('hidden');
		} else {
			$("textarea[name=dispo]").prop('disabled', true)
				.parents(".form-group").addClass('hidden');
		}
	
	});

	$("a.fancybox-button").fancybox();
	var cases_status = $("#hidden-cases-status").val();
	var ajaxParams = {};
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
			"search": "Search : ",
			"zeroRecords": "No matching records found"
		},

		"bStateSave": true, // save datatable state(pagination, sort, etc) in cookie.

		"lengthMenu": [
			[5, 15, 20, -1],
			[5, 15, 20, "All"] // change per page values here
		],

		"pageLength": 5,

		"order" : [[0, 'desc']],

		"dom" : 'lfrt<"row"<"col-xs-12"i><"col-xs-12"p>>'
	};

	var table_search_history = $("#table-search-history").DataTable(datatable_option);

	var table_image_list = $('#table-image-list').DataTable(

		$.extend(datatable_option, {
			"ajax": { // define ajax settings
				"url": $("#hidden-image-list-url").val(), // ajax URL
				"type": "POST", // request type
				"timeout": 20000,
				"data": function (data) {
					$.each(ajaxParams, function(key, value) {
						data[key] = value;
					});
					Metronic.blockUI({
						animate: true,
						target: $('#table-image-list_wrapper'),
						overlayColor: 'none',
						cenrerY: true,
					});
				},
				"dataSrc": function(res) { // Manipulate the data returned from the server
					Metronic.unblockUI($('#table-image-list_wrapper'));
					return res.data.map(function (val, key) {
						return [
							key + 1,
							'<a href="' + val[0] + '" class="fancybox-button" data-rel="fancybox-button">' + 
							//'<img src="' + val[1] + '" style="width:96px"/><div>' + val[2] + '</div></a>',
							'<img src="' + val[1] + '" style="width:96px"/></a>',
							val[3],
							'<button class="btn btn-sm blue search" image-no="' + val[4] + '"><i class="fa fa-search"></i> Search</button>' +
							(
							cases_status == 'ACTIVE' ? 
								'<div class="clearfix margin-bottom-10"></div>' +
								'<button class="btn btn-sm red delete" image-no="' + val[4] +'"><i class="fa fa-trash"></i> Remove</button>'
							:
								''
							)
						];
					});
				},
				"error": function () {
					Metronic.unblockUI($('#table-image-list_wrapper'));
				}
			}
		}, true)
	);

	FormFileUpload.init(function () {
		ajaxParams = {};
		table_image_list.ajax.reload();
	});

	$('#table-image-list').on('click', '.delete', function () {
		ajaxParams = {'delete' : $(this).attr('image-no')};
		table_image_list.ajax.reload();
	});
	
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
			body += '		<img src="' + needle_image_src + '" class="img-thumbnail fanc1ybox-button" data-rel="fancybox-button">';
			body += '	</div>';
			
			for (var i = 0, len = data.data_list.length; i < len; i++) {
				flat.push(data.data_list[i]);
				match_count += 1;
			}

			flat.sort(function (a, b) {
		        var x = a['similarity']; var y = b['similarity'];
       			return ((x > y) ? -1 : ((x < y) ? 1 : 0));
			});

			body += '<div class="my-bootbox-body col-md-10 col-sm-9 col-xs-12">';
			body += '<ul class="list-new ext1">';

			$.each(flat, function (index, value) {
				let image_url = value.image;
                if(image_url.substr(0, 7) == 'storage'){
                	image_url = s3_base_image_url + image_url;
				}
				body += '<li style="margin: 10px 0;">';
				body += '	<div>';
				body += '		<a href="' + image_url + '" class="fancybox-button" data-rel="fancybox-button">';
				body += '		<img src="' + image_url + '" class="img-thumbnail" alt="Can not load image"></a>';
				body += '	</div>';
				body += '	<div style="margin-top:20px; line-height:20px">'
				body += '		<div class="field">';
				body += '			<div><b>Similiarity:</b></div>';
				body += '			<div>' + value.similarity + '%</div>';
				body += '			<div><a href="#" onclick="showFaceDetail(\''+value.face_id+'\')">Detail</a></div>';
				body += '		</div>';
                body += '		<div class="field hidden" id="id_dv_face_detail_'+value.face_id+'">';
                body += '			<div><b>Identifiers:</b></div>';
                body += '			<div class="txt-identifiers"></div>';
                body += '		</div>';
				// body += '		<div class="field">';
				// body += '			<div><b>Source:</b></div>';
				// body += '			<div>' + 'value.organization' + '</div>';
				// body += '		</div>';
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

	$('#table-search-history').on('click', 'tbody tr', function (e) {
		if ($(this).find('td.dataTables_empty').length || e.target.tagName.toUpperCase() == "IMG") {
			return;
		}
		Metronic.blockUI({
			animate: true,
			overlayColor: 'none',
			cenrerY: true,
		});

		var needle_image_src = $(this).find('img').attr('src');
		$.post($("#hidden-search-history-url").val(), { 'history' : $(this).attr('history-no') }, function (response) {
			Metronic.unblockUI();
			showSearchResultDialog(response, needle_image_src);
		});
	});

	$('#table-image-list').on('click', '.search', function () {

		var updateCol = $(this).closest('td').prev('td');
		var needle_image_src = $(this).closest('tr').find('img').attr('src');

		Metronic.blockUI({
			animate: true,
			overlayColor: 'none',
			cenrerY: true,
		});
		
		$.ajax({
			url: $("#hidden-search-url").val(),
			type: 'post',
			data: { 'image' : $(this).attr('image-no') },
			success: function (response) {
                Metronic.unblockUI();
				if(response.status == 'faild'){
					bootbox.alert(response.msg);
					return;
				}

				var last_index = table_search_history.data().length;
				var match_count = showSearchResultDialog(response, needle_image_src);
	
				table_image_list.cell(updateCol).data(response.time).draw();
				$(table_search_history.row.add([
					last_index + 1,
					updateCol.prev('td').html(),
					response.time,
					match_count
				]).draw().node()).attr('history-no', response.history_no);
			},
			error: function (jqXHR, status, error) {
				Metronic.unblockUI();
				bootbox.alert(status + "<br>" + error);
			}
		});
	});

}


