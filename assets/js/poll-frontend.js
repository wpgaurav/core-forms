(function($) {
    'use strict';

    $(document).on('submit', '.cf-poll-form', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $poll = $form.closest('.cf-poll');
        var $message = $form.find('.cf-poll-message');
        var $submit = $form.find('.cf-poll-submit');

        var selected = $form.find('input[name="cf_poll_option"]:checked, input[name="cf_poll_option[]"]:checked');

        if (selected.length === 0) {
            showMessage($message, cf_poll.strings.error, 'error');
            return;
        }

        $poll.addClass('cf-poll-loading');
        $submit.prop('disabled', true).text(cf_poll.strings.loading);
        $message.hide();

        var formData = $form.serialize();
        formData += '&action=cf_poll_vote';

        $.ajax({
            url: cf_poll.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $poll.replaceWith(response.data.html);
                } else {
                    showMessage($message, response.data.message || cf_poll.strings.error, 'error');
                    $poll.removeClass('cf-poll-loading');
                    $submit.prop('disabled', false).text('Vote');
                }
            },
            error: function() {
                showMessage($message, cf_poll.strings.error, 'error');
                $poll.removeClass('cf-poll-loading');
                $submit.prop('disabled', false).text('Vote');
            }
        });
    });

    function showMessage($element, message, type) {
        $element
            .removeClass('cf-poll-success cf-poll-error')
            .addClass('cf-poll-' + type)
            .text(message)
            .show();
    }

})(jQuery);
