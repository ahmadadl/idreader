var UIConfirmationsBlackList = function () {

    var handleSampleBlacklist = function () {

        let $removeButton = $('#remove');
        let $addButton = $('#add');

        $removeButton.on('confirmed.bs.confirmation', function () {
            add_remove_blacklist(this, 'remove', visitorId);
        });

        $addButton.on('confirmed.bs.confirmation', function () {
            add_remove_blacklist(this, 'add', visitorId);
        })

    };

    return {
        init: function () {
            handleSampleBlacklist();
        }
    };
}();

function add_remove_blacklist(button, option, visitorId) {

    let url = '/api/blacklist/' + option + '/' + visitorId;
    let $alertDialog = $('#blacklist-alert');
    let $messageContainer = $('#error-text-blacklist');

    $.ajax({
        url: url,
        dataType: 'json',
        success: function (response) {
            if (response !== null) {
                if (response.success === 'yes') {
                    $alertDialog.removeClass('hidden');

                    if ($alertDialog.hasClass('alert-danger'))
                        $alertDialog.removeClass('alert-danger');

                    $alertDialog.addClass('alert-success');

                    let message = option === 'add' ? 'Added to blacklist.' : 'Removed from blacklist';
                    $messageContainer.html(message);

                    let textButton = option === 'add' ? 'Added' : 'Removed';
                    $(button).html(textButton);

                    location.href = 'http://localhost:8000/visitor/' + visitorId + '/settings#blacklist';
                    setInterval(function () {
                        location.reload();
                    }, 1000);

                } else if (response.success === 'no') {

                    $alertDialog.removeClass('hidden');

                    if ($alertDialog.hasClass('alert-success'))
                        $alertDialog.removeClass('alert-success');

                    $alertDialog.addClass('alert-danger');
                    $messageContainer.html('There is an error deleting the account.');

                    let textButton = option === 'add' ? 'Add' : 'Remove';
                    $(button).html(textButton);
                }
            }
        },
        beforeSend: function () {
            let textButton = option === 'add' ? 'Add' : 'Remove';
            $(button).html(textButton);
        }
    });
}

jQuery(document).ready(function() {
    UIConfirmationsBlackList.init();
});
