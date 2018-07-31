
$(document).ready(function () {
	$('#table-user').dataTable({

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

        "order" : [[1, 'desc']]
	});

    $('#table-user tbody tr .delete').click(function () {
        $('.form-delete').attr('action', $(this).attr('url')).submit();
        return false;
    });
});