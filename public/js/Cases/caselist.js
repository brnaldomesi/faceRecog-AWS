
$(document).ready(function () {
	
	var table = $('#table-case-list').DataTable({
		buttons: [
			'excel','pdf'
		],
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
            [10, 15, 20, -1],
            [10, 15, 20, "All"] // change per page values here
        ],

        "pageLength": 10,

        "order" : [[4, 'desc']]
	});
	
	table.buttons().container()
		.appendTo( document.getElementById("report-bar"));
	
	var alltable = $('#table-allcase-list').DataTable({
		buttons: [
			'excel','pdf'
		],
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
            [10, 15, 20, -1],
            [10, 15, 20, "All"] // change per page values here
        ],

        "pageLength": 10,

        "order" : [[5, 'desc'],[2,'asc']]
	});
	
	alltable.buttons().container()
		.appendTo( document.getElementById("all-report-bar"));
});