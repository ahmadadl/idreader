let TableDatatablesRowreorder = function () {

    let initTable1 = function () {
        let table = $('#all_buildings');

        let oTable = table.dataTable({

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

            buttons: [
                { extend: 'print', className: 'btn dark btn-outline' },
                { extend: 'pdf', className: 'btn green btn-outline' },
                { extend: 'csv', className: 'btn purple btn-outline ' }
            ],

            rowReorder: true,

            ajax: {
                url: "/api/getAllBuildings",
                dataSrc: "buildings"
            },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'location' },
                { data: 'admin' },
                { data: 'dateCreated.date' }
            ],
            columnDefs: [{
                targets: 5,
                data: "id",
                render: function (data) {
                    return '<a href="/manage-buildings/building/' + data + '">View</a> | <a href="' + data + '">Edit</a>'
                }
            }],

            "order": [
                [0, 'asc']
            ],
            
            "lengthMenu": [
                [5, 10, 15, 20, -1],
                [5, 10, 15, 20, "All"]
            ],

            "pageLength": 5,

            "dom": "<'row' <'col-md-12'B>><'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r><'table-scrollable't><'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>",
        });
    };

    return {
        init: function () {

            if (!jQuery().dataTable) {
                return;
            }
            initTable1();
        }
    };

}();

jQuery(document).ready(function() {
    TableDatatablesRowreorder.init();
});