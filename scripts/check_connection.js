function check_connection() {
    $('#obula_show_pgm').prop("disabled", true);
    $('#obula_show_stud_pgm').prop("disabled", true);
    $('#obula_show_advisees').prop("disabled", true);

    return $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/check_connection.php",
        dataType: 'json'

    })
    .then(function(resp) {
        if (!resp || typeof resp.success === 'undefined') {
            console.error('Unexpected payload:', resp);
            $('#obula_cc_errordiv')
                .html('Unexpected response from server; check browser console.')
                .show();
            // re-enable buttons so the user can try again
            $('#obula_show_pgm, #obula_show_stud_pgm, #obula_show_advisees').prop('disabled', false);
            return $.Deferred().reject().promise();
        }

        if (resp.success === false) {
            const err = resp.ccStatus || {};
            $('#obula_cc_errordiv')
                .html((err.problemMessageSml || 'Connection failed') + (err.popup || ''))
                .show();
            $('#obula_show_pgm, #obula_show_stud_pgm, #obula_show_advisees').prop('disabled', false);
            return $.Deferred().reject(err).promise();
        }

        if (resp.ccStatus.Status === "OK") {
            $('#obula_cc_errordiv').hide();
            $('#obula_show_pgm').prop("disabled", false);
            $('#obula-show-tf-med').prop("disabled", false);
            $('#obula_show_stud_no').prop("disabled", false);
            $('#obula_show_stud_pgm').prop("disabled", false);
            $('#obula_show_advisees').prop("disabled", false);
            $('#obula_show_stud_pgm').show();
            // Resolve with the response if connection is OK.
            return resp;
        } else {
            $('#obula_cc_errordiv').html(resp.ccStatus.problemMessageSml + resp.ccStatus.popup);
            $('#obula_cc_errordiv').show();
            $('#obula_show_pgm').prop("disabled", false);
            $('#obula_show_stud_pgm').prop("disabled", false);
            $('#obula_show_advisees').prop("disabled", false);
            // Return a rejected promise to stop further processing.
            return $.Deferred().reject(resp).promise();
        }
    }, function(jqXHR, textStatus, errorThrown) {
        alert('check_connection post failed:' + errorThrown);
        // Optionally, reject the promise here as well.
        return $.Deferred().reject(errorThrown).promise();
    }).fail(function(jqXHR, textStatus, errorThrown) {
        // network or parse error
        alert('Connection check failed: ' + errorThrown);
        $('#obula_show_pgm, #obula_show_stud_pgm, #obula_show_advisees').prop('disabled', false);
        return $.Deferred().reject(errorThrown).promise();
    });;
}