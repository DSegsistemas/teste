<script type="text/javascript">
    $('#edit_transfer_ownwership select[name="client_id"]').on('change', function() {
        "use strict";

        var client_id = $('#edit_transfer_ownwership select[name="client_id"]').val();

        requestGet('workshop/get_client_data/' + client_id).done(function(response) {
            response = JSON.parse(response);
            if (response.success || response.success == true) {
                console.log(response.client_address);
                $('#edit_transfer_ownwership .client_address').html(response.client_address);
                $('#edit_transfer_ownwership .client_phone').html(response.client_phone);
                $('#edit_transfer_ownwership .contact_phone').html(response.contact_phone);
                $('#edit_transfer_ownwership .contact_email').html(response.contact_email);

            }
        }).fail(function(error) {
            alert_float('danger', error.responseText);
        });

    });
</script>