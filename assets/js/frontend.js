/**
 * Massimo Manca Preventivi - Frontend JavaScript
 * Handles form interactions, calculations, and AJAX submissions
 */

(function($) {
    'use strict';

    const MMPreventivi = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.setDefaultDate();
            this.calculateTotals();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Calculate on service change
            $(document).on('change input', '.mm-service-item input', this.calculateTotals.bind(this));

            // Calculate on discount or options change
            $(document).on('change input', '#sconto, #sconto_percentuale, #applica_enpals, #applica_iva', this.calculateTotals.bind(this));

            // Form submission
            $('#mm-preventivo-form').on('submit', this.handleSubmit.bind(this));

            // Reset button
            $('.mm-btn-reset').on('click', this.handleReset.bind(this));

            // Clear discount percentuale when discount fisso is used
            $('#sconto').on('input', function() {
                if ($(this).val() > 0) {
                    $('#sconto_percentuale').val(0);
                }
            });

            // Clear discount fisso when discount percentuale is used
            $('#sconto_percentuale').on('input', function() {
                if ($(this).val() > 0) {
                    $('#sconto').val(0);
                }
            });
        },
        
        /**
         * Set default date
         */
        setDefaultDate: function() {
            const today = new Date().toISOString().split('T')[0];
            $('#data_preventivo').val(today);
        },
        
        /**
         * Calculate totals
         */
        calculateTotals: function() {
            let totaleServizi = 0;

            // Sum all checked services with individual discounts
            $('.mm-service-item').each(function() {
                const checkbox = $(this).find('input[type="checkbox"]');
                const priceInput = $(this).find('.mm-price-input');
                const discountInput = $(this).find('.mm-discount-input');

                if (checkbox.is(':checked') && priceInput.val()) {
                    const price = parseFloat(priceInput.val()) || 0;
                    const discount = parseFloat(discountInput.val()) || 0;
                    const finalPrice = Math.max(0, price - discount); // Non può essere negativo
                    totaleServizi += finalPrice;
                }
            });

            // Get discount values
            const scontoFisso = parseFloat($('#sconto').val()) || 0;
            const scontoPercentuale = parseFloat($('#sconto_percentuale').val()) || 0;

            // Calculate discount
            let importoSconto = 0;
            if (scontoPercentuale > 0) {
                importoSconto = totaleServizi * (scontoPercentuale / 100);
            } else if (scontoFisso > 0) {
                importoSconto = scontoFisso;
            }

            // Subtotal after discount
            const totaleDopoSconto = totaleServizi - importoSconto;

            // Check if taxes should be applied
            const applicaEnpals = $('#applica_enpals').is(':checked');
            const applicaIva = $('#applica_iva').is(':checked');

            // Calculate taxes
            const enpals = applicaEnpals ? (totaleDopoSconto * 0.33) : 0;
            const iva = applicaIva ? (totaleDopoSconto * 0.22) : 0;
            const totale = totaleDopoSconto + enpals + iva;

            // Update display
            $('#subtotal').text('€ ' + this.formatCurrency(totaleServizi));

            // Show/hide discount row
            if (importoSconto > 0) {
                $('#sconto-row').show();
                let scontoLabel = 'Sconto';
                if (scontoPercentuale > 0) {
                    scontoLabel = 'Sconto (' + scontoPercentuale.toFixed(0) + '%)';
                }
                $('#sconto-row .label').text('- ' + scontoLabel + ':');
                $('#sconto-display').text('€ ' + this.formatCurrency(importoSconto));
                $('#subtotal-sconto-row').show();
                $('#subtotal-sconto').text('€ ' + this.formatCurrency(totaleDopoSconto));
            } else {
                $('#sconto-row').hide();
                $('#subtotal-sconto-row').hide();
            }

            // Show/hide tax rows
            if (applicaEnpals) {
                $('#enpals-row').show();
                $('#enpals').text('€ ' + this.formatCurrency(enpals));
            } else {
                $('#enpals-row').hide();
            }

            if (applicaIva) {
                $('#iva-row').show();
                $('#iva').text('€ ' + this.formatCurrency(iva));
            } else {
                $('#iva-row').hide();
            }

            $('#total').text('€ ' + this.formatCurrency(totale));

            // Store values in hidden fields or data attributes
            $('#mm-preventivo-form').data('totals', {
                totaleServizi: totaleServizi,
                sconto: scontoFisso,
                scontoPercentuale: scontoPercentuale,
                applicaEnpals: applicaEnpals,
                applicaIva: applicaIva,
                enpals: enpals,
                iva: iva,
                totale: totale
            });
        },
        
        /**
         * Format currency
         */
        formatCurrency: function(amount) {
            return parseFloat(amount).toFixed(2).replace('.', ',');
        },
        
        /**
         * Get selected services
         */
        getSelectedServices: function() {
            const services = [];

            $('.mm-service-item').each(function() {
                const checkbox = $(this).find('input[type="checkbox"]');
                const label = $(this).find('label').text().trim();
                const priceInput = $(this).find('.mm-price-input');
                const discountInput = $(this).find('.mm-discount-input');

                if (checkbox.is(':checked') && priceInput.val()) {
                    const price = parseFloat(priceInput.val()) || 0;
                    const discount = parseFloat(discountInput.val()) || 0;

                    services.push({
                        nome: label,
                        prezzo: price,
                        sconto: discount
                    });
                }
            });

            return services;
        },
        
        /**
         * Get selected extras
         */
        getSelectedExtras: function() {
            const extras = [];
            
            $('input[name="servizi_extra[]"]:checked').each(function() {
                extras.push($(this).val());
            });
            
            return extras;
        },
        
        /**
         * Get selected cerimonie
         */
        getSelectedCerimonie: function() {
            const cerimonie = [];
            
            $('input[name="cerimonia[]"]:checked').each(function() {
                cerimonie.push($(this).val());
            });
            
            return cerimonie;
        },
        
        /**
         * Validate form
         */
        validateForm: function() {
            const errors = [];
            
            // Check required fields
            const requiredFields = {
                'data_preventivo': 'Data preventivo',
                'sposi': 'Nome sposi/cliente',
                'data_evento': 'Data evento'
            };
            
            $.each(requiredFields, function(field, label) {
                if (!$('#' + field).val()) {
                    errors.push(label + ' è obbligatorio');
                }
            });
            
            // Check if at least one service is selected
            const services = this.getSelectedServices();
            if (services.length === 0) {
                errors.push('Seleziona almeno un servizio');
            }
            
            return errors;
        },
        
        /**
         * Show message
         */
        showMessage: function(message, type) {
            const messageHtml = `
                <div class="mm-message mm-message-${type}">
                    ${type === 'success' ? '✓' : '✗'} ${message}
                </div>
            `;
            
            $('.mm-form-body').prepend(messageHtml);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $('.mm-message').offset().top - 100
            }, 300);
            
            // Remove after 5 seconds
            setTimeout(function() {
                $('.mm-message').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Handle form submit
         */
        handleSubmit: function(e) {
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
                        // Show success with PDF button
                        const preventivoId = response.data.preventivo_id;
                        const pdfUrl = mmPreventivi.ajaxurl + '?action=mm_view_pdf&id=' + preventivoId + '&nonce=' + mmPreventivi.pdfNonce;

                        const successHtml = `
                            <div class="mm-message mm-message-success">
                                ✓ ${response.data.message}
                                <div style="margin-top: 15px;">
                                    <a href="${pdfUrl}" target="_blank" class="mm-btn mm-btn-primary" style="display: inline-block; text-decoration: none; padding: 12px 24px; border-radius: 8px;">
                                        📄 Visualizza PDF
                                    </a>
                                </div>
                            </div>
                        `;

                        $('.mm-form-body').prepend(successHtml);

                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $('.mm-message').offset().top - 100
                        }, 300);

                        // Reset form after 5 seconds
                        setTimeout(() => {
                            this.handleReset();
                        }, 5000);
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
        },
        
        /**
         * Handle reset
         */
        handleReset: function() {
            if (confirm('Sei sicuro di voler resettare il form?')) {
                $('#mm-preventivo-form')[0].reset();
                this.setDefaultDate();
                this.calculateTotals();
                $('.mm-message').remove();
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#mm-preventivo-form').length) {
            MMPreventivi.init();
        }
    });

})(jQuery);
