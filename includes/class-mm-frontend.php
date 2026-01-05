<?php
/**
 * Gestione Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Frontend {
    
    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_mm_save_preventivo', array($this, 'ajax_save_preventivo'));
        add_action('wp_ajax_nopriv_mm_save_preventivo', array($this, 'ajax_save_preventivo'));
        add_action('wp_ajax_mm_view_pdf', array($this, 'ajax_view_pdf'));
        add_action('wp_ajax_nopriv_mm_view_pdf', array($this, 'ajax_view_pdf'));
    }
    
    /**
     * Render form preventivo
     */
    public static function render_form() {
        include MM_PREVENTIVI_PLUGIN_DIR . 'templates/form-preventivo.php';
    }
    
    /**
     * AJAX: Salva preventivo
     */
    public function ajax_save_preventivo() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !MM_Security::verify_nonce($_POST['nonce'])) {
            wp_send_json_error(array(
                'message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')
            ));
        }
        
        // Rate limiting
        $ip = MM_Security::get_client_ip();
        if (!MM_Security::check_rate_limit($ip, 5, 300)) {
            wp_send_json_error(array(
                'message' => __('Troppe richieste. Riprova tra qualche minuto.', 'mm-preventivi')
            ));
        }
        
        // Prepara dati
        $data = array(
            'data_preventivo' => sanitize_text_field($_POST['data_preventivo']),
            'sposi' => sanitize_text_field($_POST['sposi']),
            'email' => sanitize_email($_POST['email']),
            'telefono' => sanitize_text_field($_POST['telefono']),
            'data_evento' => sanitize_text_field($_POST['data_evento']),
            'location' => sanitize_text_field($_POST['location']),
            'tipo_evento' => sanitize_text_field($_POST['tipo_evento']),
            'cerimonia' => isset($_POST['cerimonia']) ? array_map('sanitize_text_field', $_POST['cerimonia']) : array(),
            'servizi_extra' => isset($_POST['servizi_extra']) ? array_map('sanitize_text_field', $_POST['servizi_extra']) : array(),
            'note' => sanitize_textarea_field($_POST['note']),
            'totale_servizi' => floatval($_POST['totale_servizi']),
            'sconto' => isset($_POST['sconto']) ? floatval($_POST['sconto']) : 0,
            'sconto_percentuale' => isset($_POST['sconto_percentuale']) ? floatval($_POST['sconto_percentuale']) : 0,
            'applica_enpals' => isset($_POST['applica_enpals']) ? (bool)$_POST['applica_enpals'] : true,
            'applica_iva' => isset($_POST['applica_iva']) ? (bool)$_POST['applica_iva'] : true,
            'enpals' => floatval($_POST['enpals']),
            'iva' => floatval($_POST['iva']),
            'totale' => floatval($_POST['totale']),
            'data_acconto' => sanitize_text_field($_POST['data_acconto']),
            'importo_acconto' => floatval($_POST['importo_acconto']),
            'servizi' => array()
        );
        
        // Processa servizi
        if (isset($_POST['servizi']) && is_array($_POST['servizi'])) {
            foreach ($_POST['servizi'] as $servizio) {
                $data['servizi'][] = array(
                    'nome' => sanitize_text_field($servizio['nome']),
                    'prezzo' => floatval($servizio['prezzo'])
                );
            }
        }
        
        // Salva nel database
        $preventivo_id = MM_Database::save_preventivo($data);
        
        if (is_wp_error($preventivo_id)) {
            MM_Security::log_security_event('preventivo_save_failed', array(
                'error' => $preventivo_id->get_error_message()
            ));
            
            wp_send_json_error(array(
                'message' => $preventivo_id->get_error_message()
            ));
        }
        
        MM_Security::log_security_event('preventivo_saved', array(
            'preventivo_id' => $preventivo_id
        ));
        
        wp_send_json_success(array(
            'message' => __('Preventivo salvato con successo!', 'mm-preventivi'),
            'preventivo_id' => $preventivo_id
        ));
    }

    /**
     * AJAX: Visualizza PDF
     */
    public function ajax_view_pdf() {
        // Verifica nonce
        if (!isset($_GET['nonce']) || !MM_Security::verify_nonce($_GET['nonce'], 'mm_preventivi_view_pdf')) {
            wp_die(__('Verifica di sicurezza fallita.', 'mm-preventivi'));
        }

        if (!isset($_GET['id'])) {
            wp_die(__('ID preventivo mancante.', 'mm-preventivi'));
        }

        $id = intval($_GET['id']);
        $preventivo = MM_Database::get_preventivo($id);

        if (!$preventivo) {
            wp_die(__('Preventivo non trovato.', 'mm-preventivi'));
        }

        // Genera PDF
        MM_PDF_Generator::generate_pdf($preventivo);
        exit;
    }
}

// Inizializza
new MM_Frontend();
