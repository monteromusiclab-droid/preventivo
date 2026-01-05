<?php
/**
 * Admin View: Nuovo Preventivo
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mm-admin-page">

    <div class="mm-admin-header">
        <h1>➕ Nuovo Preventivo</h1>
        <p>Crea un nuovo preventivo dal pannello di amministrazione</p>
    </div>

    <!-- Include il form del frontend in stile admin -->
    <div class="mm-preventivi-container" style="max-width: 100%; margin: 0; padding: 0;">
        <?php
        // Usa il template del frontend
        include MM_PREVENTIVI_PLUGIN_DIR . 'templates/form-preventivo.php';
        ?>
    </div>

</div>

<style>
/* Adatta lo stile del form per l'admin */
.mm-admin-page .mm-preventivi-form {
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mm-admin-page .mm-form-header {
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
}

.mm-admin-page .mm-preventivi-container {
    margin-top: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Override del successo per admin
    if (typeof MMPreventivi !== 'undefined') {
        const originalHandleSubmit = MMPreventivi.handleSubmit;

        MMPreventivi.handleSubmit = function(e) {
            e.preventDefault();

            // Remove previous messages
            $('.mm-message').remove();

            // Validate
            const errors = this.validateForm();
            if (errors.length > 0) {
                this.showMessage(errors.join('<br>'), 'error');
                return;
            }

            // Get totals
            const totals = $('#mm-preventivo-form').data('totals');

            // Prepare data
            const formData = {
                action: 'mm_save_preventivo',
                nonce: mmPreventivi.nonce,
                data_preventivo: $('#data_preventivo').val(),
                sposi: $('#sposi').val(),
                email: $('#email').val(),
                telefono: $('#telefono').val(),
                data_evento: $('#data_evento').val(),
                location: $('#location').val(),
                tipo_evento: $('input[name="tipo_evento"]:checked').val() || '',
                cerimonia: this.getSelectedCerimonie(),
                servizi_extra: this.getSelectedExtras(),
                note: $('#note').val(),
                totale_servizi: totals.totaleServizi,
                sconto: totals.sconto,
                sconto_percentuale: totals.scontoPercentuale,
                applica_enpals: totals.applicaEnpals,
                applica_iva: totals.applicaIva,
                enpals: totals.enpals,
                iva: totals.iva,
                totale: totals.totale,
                data_acconto: $('#data_acconto').val(),
                importo_acconto: $('#importo_acconto').val() || 0,
                servizi: this.getSelectedServices()
            };

            // Disable submit button
            const $submitBtn = $('.mm-btn-primary');
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true)
                     .html('<span class="mm-loading"></span> Salvataggio...');

            // Send AJAX request
            $.ajax({
                url: mmPreventivi.ajaxurl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        // Redirect to view preventivo
                        window.location.href = 'admin.php?page=mm-preventivi&action=view&id=' + response.data.preventivo_id;
                    } else {
                        this.showMessage(response.data.message, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Errore di connessione. Riprova.', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        };
    }
});
</script>
