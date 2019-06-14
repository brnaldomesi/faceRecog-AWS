function validatePhoto1() {
    return new Promise((resolve, reject) => {
        if($('#portraitDiv1')[0].childElementCount === 0) {
            bootbox.alert({
                message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you imported PHOTO1 image file.'
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

function validatePhoto2() {
    return new Promise((resolve, reject) => {
        if($('#portraitDiv2')[0].childElementCount === 0) {
            bootbox.alert({
                message: '<h4 style="color: #f00;">Failure<br></h4>' + 'Please make sure you imported PHOTO2 image file.'
            });
            reject();
        } else {
            validateImageFile($('#portraitInput2').prop('files')[0]).then((resultCode) => { 
                resolve();
        
            }).catch((resultCode) => {
                notifyInvalidImage(resultCode, "PHOTO2");
                reject();
        
            });
        }
    });
}

function showCompareResult(data) {
    if(data > 0) {
        $("#compareResultCaption").attr("style", "display:show");
        $("#compareResultValue").attr("style", "display:show");
        $("#compareResultCaption").html("Similarity between these faces is: ");
        $("#similarityValue").html(data + '%');
    } else {
        $("#compareResultValue").attr("style", "display:show");
        $("#similarityValue").html('No match');
    }
}

function hideCompareResult() {
    $("#compareResultCaption").attr("style", "display:none");
    $("#compareResultValue").attr("style", "display:none");
}

function setStateActive() {
    $("#resultProcessButtons").attr("style", "display:none");
    $(".btn.default").removeAttr("disabled");
    $("#compareButton").removeAttr("disabled");
}

function setStateInactive() {
    $("#resultProcessButtons").attr("style", "display:show");
    $(".btn.default").attr("disabled", "disabled");
    $("#compareButton").attr("disabled", "disabled");
}

var table_compare_history;

$(document).ready(function () {
    initEvent();
});

function initEvent() {
    $("#portraitInput1").on("change", function() {
        hideCompareResult();
    });

    $("#portraitInput2").on("change", function() {
        hideCompareResult();
    });
    
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
		"order" : [[2, 'desc']],
        "searching": false
    };
    
    table_compare_history = $('#table-compare-history').DataTable(
		$.extend(datatable_option, {
			"ajax": {
				"url": $("#route-compare-history").val(),
				"type": "POST",
				"timeout": 20000,
				"data": function (data) {
					Metronic.blockUI({
						animate: true,
						target: $('#table-compare-history'),
						overlayColor: 'none',
						cenrerY: true,
					});
				},
				"dataSrc": function(res) {
					Metronic.unblockUI($('#table-compare-history'));
					return res.data.map(function (val, key) {
						return [
							key + 1,
							'<a href="' + val[0] + '" class="fancybox-button" data-rel="fancybox-button">' + 
                            '<img src="' + val[0] + '" style="width:50%;max-width:96px;margin:1px;"/></a>' +
                            '<a href="' + val[1] + '" class="fancybox-button" data-rel="fancybox-button">' + 
							'<img src="' + val[1] + '" style="width:50%;max-width:96px;"/></a>',
							val[2],
							val[3] + '%'
						];
                    });
                },
				"error": function () {
					Metronic.unblockUI($('#table-compare-history'));
				}
            },
            "rowCallback": function( row, data ) {
                $(row).find('td a.fancybox-button').fancybox();
            }
		}, true)
    ); 
}

function discardCompareResult() {
    setStateActive();
}

function saveCompareResult() {
    validatePhoto1().then(() => {
        validatePhoto2().then(() => {
            Metronic.blockUI({
                animate: true,
            });
            
            var form = $('#compareCreateForm')[0];
            var formData = new FormData(form);
            var apiUrl = $('#route-compare-save').val();
            formData.append('similarity', $('#compare-similarity-score').val());

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
                        table_compare_history.ajax.reload();
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
        }).catch(() => {
    
        })
    }).catch(() => {

    })
}

function compareFaces() {
    validatePhoto1().then(() => {
        validatePhoto2().then(() => {
            Metronic.blockUI({
                animate: true,
            });
            
            var form = $('#compareCreateForm')[0];
            var formData = new FormData(form);
            var apiUrl = $('#route-compare-create').val();
            
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
                        if(data.msg > 0)
                            setStateInactive();
                        showCompareResult(data.msg);
                        $('#compare-similarity-score').val(data.msg);
                    } else {
                        bootbox.alert({
                            message: '<h4 style="color: #f00;">Error<br></h4>' + 'No face is found in at least one of these images'
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

        }).catch(() => {
    
        })
    }).catch(() => {

    })
}

