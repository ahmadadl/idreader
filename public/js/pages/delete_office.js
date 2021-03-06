var UIConfirmations = function () {

    var handleSample = function () {

        let $deleteButton = $('#delete_office_btn');
        let $alertDialog = $('.alert');
        let $messageContainer = $('#error-text');

        $deleteButton.on('confirmed.bs.confirmation', function () {
            $.ajax({
                url: '/manageOffice/office/' + $deleteButton.data('office') + '/edit/delete',
                dataType: 'json',
                success: function (response) {
                    if (response !== null) {
                        if (response.success === 'yes') {
                            $alertDialog.removeClass('hidden');

                            if ($alertDialog.hasClass('alert-danger'))
                                $alertDialog.removeClass('alert-danger');

                            $alertDialog.addClass('alert-success');
                            $messageContainer.html('Office deleted successfully.');
                            $deleteButton.html('Deleted');

                            setInterval(function () {
                                location.href = 'http://localhost:8000/manageOffices/offices'
                            }, 2000);

                        } else if (response.success === 'no') {
                            $('.alert').removeClass('hidden');

                            if ($alertDialog.hasClass('alert-success'))
                                $alertDialog.removeClass('alert-success');

                            $alertDialog.addClass('alert-danger');
                            $messageContainer.html('There is an error deleting the office.');
                            $deleteButton.html('Delete Office');
                        }
                    }
                },
                beforeSend: function () {
                    $deleteButton.html('Deleting...');
                }
            })
        });

    };

    return {
        init: function () {
            handleSample();
        }
    };
}();

jQuery(document).ready(function() {
    UIConfirmations.init();
});
