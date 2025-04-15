function check_connection() {
    $('#obula_show_pgm').prop("disabled", true);
    $('#obula_show_stud_pgm').prop("disabled", true);

    return $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/check_connection.php"
    })
    .then(function(resp) {
        if (resp.ccStatus.Status === "OK") {
            $('#obula_cc_errordiv').hide();
            $('#obula_show_pgm').prop("disabled", false);
            $('#obula-show-tf-med').prop("disabled", false);
            $('#obula_show_stud_no').prop("disabled", false);
            $('#obula_show_stud_pgm').prop("disabled", false);
            $('#obula_show_stud_pgm').show();
            // Resolve with the response if connection is OK.
            return resp;
        } else {
            $('#obula_cc_errordiv').html(resp.ccStatus.problemMessageSml + resp.ccStatus.popup);
            $('#obula_cc_errordiv').show();
            $('#obula_show_pgm').prop("disabled", true);
            $('#obula_show_stud_pgm').prop("disabled", true);
            // Return a rejected promise to stop further processing.
            return $.Deferred().reject(resp).promise();
        }
    }, function(jqXHR, textStatus, errorThrown) {
        alert('check_connection post failed:' + errorThrown);
        // Optionally, reject the promise here as well.
        return $.Deferred().reject(errorThrown).promise();
    });
}