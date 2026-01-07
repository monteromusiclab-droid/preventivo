<?php
/**
 * Gestione Sicurezza
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Security {
    
    /**
     * Verifica nonce
     */
    public static function verify_nonce($nonce, $action = 'mm_preventivi_nonce') {
        return wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Verifica permessi amministratore
     */
    public static function check_admin_permission() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'mm-preventivi'));
        }
    }
    
    /**
     * Valida dati preventivo
     */
    public static function validate_preventivo_data($data) {
        $errors = array();
        
        // Campi obbligatori
        $required_fields = array(
            'data_preventivo' => 'Data preventivo',
            'sposi' => 'Nome sposi/cliente',
            'data_evento' => 'Data evento',
            'totale_servizi' => 'Totale servizi',
            'totale' => 'Totale'
        );
        
        foreach ($required_fields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = sprintf(__('Il campo %s è obbligatorio.', 'mm-preventivi'), $label);
            }
        }
        
        // Valida date
        if (!empty($data['data_preventivo']) && !self::validate_date($data['data_preventivo'])) {
            $errors[] = __('Data preventivo non valida.', 'mm-preventivi');
        }
        
        if (!empty($data['data_evento']) && !self::validate_date($data['data_evento'])) {
            $errors[] = __('Data evento non valida.', 'mm-preventivi');
        }
        
        if (!empty($data['data_acconto']) && !self::validate_date($data['data_acconto'])) {
            $errors[] = __('Data acconto non valida.', 'mm-preventivi');
        }
        
        // Valida email
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors[] = __('Email non valida.', 'mm-preventivi');
        }
        
        // Valida numeri
        $numeric_fields = array('totale_servizi', 'enpals', 'iva', 'totale');
        foreach ($numeric_fields as $field) {
            if (!empty($data[$field]) && !is_numeric($data[$field])) {
                $errors[] = sprintf(__('Il campo %s deve essere un numero.', 'mm-preventivi'), $field);
            }
        }

        // Valida numeri opzionali
        $optional_numeric_fields = array('importo_acconto', 'sconto', 'sconto_percentuale');
        foreach ($optional_numeric_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field]) && !is_numeric($data[$field])) {
                $errors[] = sprintf(__('Il campo %s deve essere un numero.', 'mm-preventivi'), $field);
            }
        }
        
        // Valida servizi
        if (isset($data['servizi']) && !is_array($data['servizi'])) {
            $errors[] = __('Formato servizi non valido.', 'mm-preventivi');
        }
        
        if (!empty($errors)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitizza dati preventivo
     */
    public static function sanitize_preventivo_data($data) {
        $sanitized = array();
        
        // Testo semplice
        $text_fields = array('sposi', 'location', 'tipo_evento', 'telefono');
        foreach ($text_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
        }
        
        // Email
        $sanitized['email'] = isset($data['email']) ? sanitize_email($data['email']) : '';
        
        // Date
        $date_fields = array('data_preventivo', 'data_evento', 'data_acconto');
        foreach ($date_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
        }
        
        // Textarea
        $sanitized['note'] = isset($data['note']) ? sanitize_textarea_field($data['note']) : '';
        
        // Numeri
        $numeric_fields = array('totale_servizi', 'enpals', 'iva', 'totale', 'importo_acconto', 'sconto', 'sconto_percentuale');
        foreach ($numeric_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? floatval($data[$field]) : 0;
        }

        // Boolean fields - gestisce correttamente true/false/1/0/"true"/"false"
        $sanitized['applica_enpals'] = isset($data['applica_enpals']) ?
            (($data['applica_enpals'] === true || $data['applica_enpals'] === 1 || $data['applica_enpals'] === '1' || $data['applica_enpals'] === 'true') ? true : false) :
            true;
        $sanitized['applica_iva'] = isset($data['applica_iva']) ?
            (($data['applica_iva'] === true || $data['applica_iva'] === 1 || $data['applica_iva'] === '1' || $data['applica_iva'] === 'true') ? true : false) :
            true;
        
        // Array
        if (isset($data['cerimonia']) && is_array($data['cerimonia'])) {
            $sanitized['cerimonia'] = array_map('sanitize_text_field', $data['cerimonia']);
        }
        
        if (isset($data['servizi_extra']) && is_array($data['servizi_extra'])) {
            $sanitized['servizi_extra'] = array_map('sanitize_text_field', $data['servizi_extra']);
        }
        
        // Servizi
        if (isset($data['servizi']) && is_array($data['servizi'])) {
            $sanitized['servizi'] = array();
            foreach ($data['servizi'] as $servizio) {
                $sanitized['servizi'][] = array(
                    'nome' => sanitize_text_field($servizio['nome']),
                    'prezzo' => floatval($servizio['prezzo']),
                    'sconto' => isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0
                );
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Valida data
     */
    private static function validate_date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Previeni SQL Injection
     */
    public static function escape_sql($value) {
        global $wpdb;
        return $wpdb->_real_escape($value);
    }
    
    /**
     * Previeni XSS
     */
    public static function escape_output($text) {
        return esc_html($text);
    }
    
    /**
     * Previeni XSS in attributi
     */
    public static function escape_attr($text) {
        return esc_attr($text);
    }
    
    /**
     * Previeni XSS in URL
     */
    public static function escape_url($url) {
        return esc_url($url);
    }
    
    /**
     * Sanitizza filename
     */
    public static function sanitize_filename($filename) {
        return sanitize_file_name($filename);
    }
    
    /**
     * Rate limiting per prevenire spam
     */
    public static function check_rate_limit($identifier, $max_requests = 10, $time_window = 3600) {
        $transient_key = 'mm_rate_limit_' . md5($identifier);
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            set_transient($transient_key, 1, $time_window);
            return true;
        }
        
        if ($requests >= $max_requests) {
            return false;
        }
        
        set_transient($transient_key, $requests + 1, $time_window);
        return true;
    }
    
    /**
     * Genera token sicuro
     */
    public static function generate_secure_token($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Log attività per sicurezza
     */
    public static function log_security_event($event, $details = array()) {
        if (!WP_DEBUG) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'user_ip' => self::get_client_ip(),
            'event' => $event,
            'details' => $details
        );
        
        error_log('[MM_PREVENTIVI_SECURITY] ' . json_encode($log_entry));
    }
    
    /**
     * Ottieni IP cliente
     */
    public static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
    
    /**
     * Verifica CSRF token
     */
    public static function verify_csrf_token($token, $action = 'mm_preventivi_csrf') {
        $stored_token = get_transient($action . '_' . get_current_user_id());
        
        if ($stored_token === false || $token !== $stored_token) {
            return false;
        }
        
        delete_transient($action . '_' . get_current_user_id());
        return true;
    }
    
    /**
     * Genera CSRF token
     */
    public static function generate_csrf_token($action = 'mm_preventivi_csrf') {
        $token = self::generate_secure_token();
        set_transient($action . '_' . get_current_user_id(), $token, 3600);
        return $token;
    }
    
    /**
     * Sanitizza input ricorsivamente
     */
    public static function sanitize_recursive($data) {
        if (is_array($data)) {
            return array_map(array(__CLASS__, 'sanitize_recursive'), $data);
        }
        
        return sanitize_text_field($data);
    }
    
    /**
     * Valida permessi upload file
     */
    public static function validate_file_upload($file) {
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'application/pdf');
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!isset($file['error']) || is_array($file['error'])) {
            return new WP_Error('invalid_file', __('Errore nel caricamento del file.', 'mm-preventivi'));
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('Errore durante l\'upload.', 'mm-preventivi'));
        }
        
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('Il file è troppo grande. Massimo 5MB.', 'mm-preventivi'));
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, $allowed_types)) {
            return new WP_Error('invalid_type', __('Tipo di file non consentito.', 'mm-preventivi'));
        }
        
        return true;
    }
}
