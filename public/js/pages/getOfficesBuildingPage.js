function getOffices(buildingId, page) {

    let url = '/api/offices/' + buildingId + '/' + page;

    $.ajax({
        url: url,
        type: 'get',
        dataType: 'json',
        success: function (data) {
            renderOfficesRecords(data.offices);
            renderPaginationOffices(data.maxPages);
        },
        beforeSend: function () {
            $('#offices').html('Loading...');
        }
    })
}

function renderOfficesRecords(offices) {
    let $rows = '';
    let $officeContainer = $('#offices');

    if (offices.length === 0) {
        $officeContainer.html('No Offices');
    } else {
        $.each(offices, function (index, office) {
            $rows +=
                '<li class="mt-list-item">' +
                '<div class="list-icon-container"><i class="icon-check"></i></div>' +
                '<div class="list-item-content">' +
                '<h3 class="uppercase">' +
                '<a href="/manageOffices/office/' + office.id + '">' + office.officeNumber + '</a>' +
                '</h3>' +
                '</div>' +
                '</li>';
        });
        $officeContainer.empty();
        $officeContainer.html($rows);
    }
}

function renderPaginationOffices(maxPages) {
    let $paginationOffices = $('#pagination-offices');
    let currentPage = $paginationOffices.twbsPagination('getCurrentPage');

    $paginationOffices.twbsPagination('destroy');
    $paginationOffices.twbsPagination($.extend({}, {
        totalPages: maxPages,
        startPage: currentPage,
        visiblePages: 1,
        initiateStartPageClick: false,
        prev: '<i class="fa fa-angle-left"></i>',
        next: '<i class="fa fa-angle-right"></i>',
        first: '<i class="fa fa-angle-double-left"></i>',
        last: '<i class="fa fa-angle-double-right"></i>',
        onPageClick: function (evt, page) {
            getOffices(building, page);
        }
    }));
}

getOffices(building, 1);