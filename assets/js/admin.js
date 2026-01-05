/**
 * Massimo Manca Preventivi - Admin JavaScript
 * Handles admin panel interactions and AJAX operations
 */

(function($) {
    'use strict';

    const MMPreventiviAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Delete preventivo
            $(document).on('click', '.mm-btn-delete', this.handleDelete.bind(this));

            // Update status
            $(document).on('change', '.mm-status-select', this.handleStatusUpdate.bind(this));

            // Export PDF
            $(document).on('click', '.mm-btn-pdf', this.handlePDFExport.bind(this));

            // Filters
            $('.mm-filter-btn').on('click', this.handleFilter.bind(this));
            $('.mm-reset-filter').on('click', this.resetFilters.bind(this));

            // Logo upload
            $('.mm-upload-logo-btn').on('click', this.handleLogoUpload.bind(this));
        },
        
        /**
         * Handle delete
         */
        handleDelete: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const preventivoId = $button.data('id');
            const preventivoNumero = $button.data('numero');
            
            if (!confirm(`Sei sicuro di voler eliminare il preventivo ${preventivoNumero}?`)) {
                return;
            }
            
            // Show loading
            $button.prop('disabled', true).html('⏳');
            
            $.ajax({
                url: mmPreventiviAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mm_delete_preventivo',
                    nonce: mmPreventiviAdmin.nonce,
                    id: preventivoId
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                        
                        // Remove row with animation
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if table is empty
                            if ($('.mm-table tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        this.showNotice(response.data.message, 'error');
                        $button.prop('disabled', false).html('🗑️');
                    }
                },
                error: () => {
                    this.showNotice('Errore di connessione', 'error');
                    $button.prop('disabled', false).html('🗑️');
                }
            });
        },
        
        /**
         * Handle status update
         */
        handleStatusUpdate: function(e) {
            const $select = $(e.currentTarget);
            const preventivoId = $select.data('id');
            const newStatus = $select.val();
            const oldStatus = $select.data('old-status');
            
            if (!confirm('Confermi il cambio di stato?')) {
                $select.val(oldStatus);
                return;
            }
            
            $.ajax({
                url: mmPreventiviAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mm_update_stato',
                    nonce: mmPreventiviAdmin.nonce,
                    id: preventivoId,
                    stato: newStatus
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                        $select.data('old-status', newStatus);
                        
                        // Update badge in row
                        const $badge = $select.closest('tr').find('.mm-status-badge');
                        $badge.removeClass().addClass('mm-status-badge ' + newStatus).text(newStatus);
                    } else {
                        this.showNotice(response.data.message, 'error');
                        $select.val(oldStatus);
                    }
                },
                error: () => {
                    this.showNotice('Errore di connessione', 'error');
                    $select.val(oldStatus);
                }
            });
        },
        
        /**
         * Handle PDF export
         */
        handlePDFExport: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const preventivoId = $button.data('id');
            
            // Show loading
            const originalHtml = $button.html();
            $button.html('⏳').prop('disabled', true);
            
            // Open PDF in new window
            const pdfUrl = mmPreventiviAdmin.ajaxurl + '?action=mm_export_pdf&id=' + preventivoId;
            window.open(pdfUrl, '_blank');
            
            // Reset button after delay
            setTimeout(() => {
                $button.html(originalHtml).prop('disabled', false);
            }, 1000);
        },
        
        /**
         * Handle filter
         */
        handleFilter: function(e) {
            e.preventDefault();
            
            const stato = $('#filter-stato').val();
            const search = $('#filter-search').val();
            const dataD a = $('#filter-data-da').val();
            const dataA = $('#filter-data-a').val();
            
            // Build URL with parameters
            let url = window.location.pathname + '?page=mm-preventivi';
            
            if (stato) {
                url += '&stato=' + encodeURIComponent(stato);
            }
            
            if (search) {
                url += '&search=' + encodeURIComponent(search);
            }
            
            if (dataDa) {
                url += '&data_da=' + encodeURIComponent(dataDa);
            }
            
            if (dataA) {
                url += '&data_a=' + encodeURIComponent(dataA);
            }
            
            window.location.href = url;
        },
        
        /**
         * Reset filters
         */
        resetFilters: function() {
            window.location.href = window.location.pathname + '?page=mm-preventivi';
        },
        
        /**
         * Show notice
         */
        showNotice: function(message, type) {
            const noticeHtml = `
                <div class="mm-notice mm-notice-${type}">
                    ${message}
                </div>
            `;
            
            $('.wrap').prepend(noticeHtml);
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: 0
            }, 300);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $('.mm-notice').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Confirm action
         */
        confirmAction: function(message) {
            return confirm(message);
        },

        /**
         * Handle logo upload using WordPress Media Library
         */
        handleLogoUpload: function(e) {
            e.preventDefault();

            // Se il media frame esiste già, aprilo
            if (this.mediaFrame) {
                this.mediaFrame.open();
                return;
            }

            // Crea il media frame
            this.mediaFrame = wp.media({
                title: 'Seleziona o Carica il Logo',
                button: {
                    text: 'Usa questo Logo'
                },
                library: {
                    type: 'image'
                },
                multiple: false
            });

            // Quando un'immagine viene selezionata
            this.mediaFrame.on('select', () => {
                const attachment = this.mediaFrame.state().get('selection').first().toJSON();

                // Inserisci l'URL nel campo
                $('#company_logo').val(attachment.url);

                // Mostra preview
                const previewHtml = `
                    <div style="margin-top: 10px;">
                        <img src="${attachment.url}"
                             alt="Logo Preview"
                             style="max-height: 80px; border: 1px solid #ddd; padding: 5px; border-radius: 5px; background: white;">
                    </div>
                `;

                // Rimuovi preview esistente e aggiungi la nuova
                $('#company_logo').siblings('div').remove();
                $('#company_logo').parent().after(previewHtml);

                this.showNotice('Logo selezionato! Ricorda di salvare le impostazioni.', 'success');
            });

            // Apri il media frame
            this.mediaFrame.open();
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.mm-admin-page').length) {
            MMPreventiviAdmin.init();
        }
    });

})(jQuery);
