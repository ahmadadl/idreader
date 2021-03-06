let columns;
if (r) {
    columns = [
        {data: 'visitorName'},
        {data: 'office'},
        {data: 'building'},
        {data: 'date'},
        {data: 'timeEntered'},
        {data: 'checkInGuard'},
        {data: 'checkInGate'},
        {data: 'leftOfficeTime'},
        {data: 'timeLeft'},
        {data: 'checkOutGuard'},
        {data: 'checkOutGate'},
        {data: 'estimatedTime'},
    ];
} else {
    columns = [
        {data: 'visitorName'},
        {data: 'office'},
        {data: 'building'},
        {data: 'date'},
        {data: 'leftOfficeTime'},
    ];
}


$('#logs').dataTable({

    "language": {
        "aria": {
            "sortAscending": ": activate to sort column ascending",
            "sortDescending": ": activate to sort column descending"
        },
        "emptyTable": "No data available in table",
        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
        "infoEmpty": "No entries found",
        "infoFiltered": "(filtered1 from _MAX_ total entries)",
        "lengthMenu": "_MENU_ entries",
        "search": "Search:",
        "zeroRecords": "No matching records found"
    },
    ajax: '/logs/get',
    columns: columns,
    buttons: [
        {extend: 'print', className: 'btn dark btn-outline'},
        {extend: 'pdf', className: 'btn green btn-outline'},
        {extend: 'csv', className: 'btn purple btn-outline '}
    ],
    "order": [
        [3, 'desc']
    ],
    "lengthMenu": [
        [5, 10, 15, 20, -1],
        [5, 10, 15, 20, "All"]
    ],
    "pageLength": 10,
    "dom": "<'row' <'col-md-12'B>><'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r><'table-scrollable't><'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>", // horizobtal scrollable datatable

});
