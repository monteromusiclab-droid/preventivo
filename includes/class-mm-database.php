<?php
/**
 * Gestione Database
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Database {
    
    /**
     * Nome tabella preventivi
     */
    private static $table_preventivi = 'mm_preventivi';
    
    /**
     * Nome tabella servizi
     */
    private static $table_servizi = 'mm_preventivi_servizi';
    
    /**
     * Crea tabelle database
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_servizi = $wpdb->prefix . self::$table_servizi;
        
        // Tabella preventivi
        $sql_preventivi = "CREATE TABLE IF NOT EXISTS $table_preventivi (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            numero_preventivo varchar(50) NOT NULL,
            data_preventivo date NOT NULL,
            sposi varchar(255) NOT NULL,
            email varchar(100) DEFAULT NULL,
            telefono varchar(50) DEFAULT NULL,
            data_evento date NOT NULL,
            location varchar(255) DEFAULT NULL,
            tipo_evento varchar(50) DEFAULT NULL,
            cerimonia text DEFAULT NULL,
            servizi_extra text DEFAULT NULL,
            note text DEFAULT NULL,
            totale_servizi decimal(10,2) NOT NULL DEFAULT 0,
            sconto decimal(10,2) DEFAULT 0,
            sconto_percentuale decimal(5,2) DEFAULT 0,
            applica_enpals tinyint(1) DEFAULT 1,
            applica_iva tinyint(1) DEFAULT 1,
            enpals decimal(10,2) NOT NULL DEFAULT 0,
            iva decimal(10,2) NOT NULL DEFAULT 0,
            totale decimal(10,2) NOT NULL DEFAULT 0,
            data_acconto date DEFAULT NULL,
            importo_acconto decimal(10,2) DEFAULT NULL,
            stato varchar(50) DEFAULT 'bozza',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY numero_preventivo (numero_preventivo),
            KEY data_evento (data_evento),
            KEY stato (stato),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Tabella servizi
        $sql_servizi = "CREATE TABLE IF NOT EXISTS $table_servizi (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            preventivo_id bigint(20) UNSIGNED NOT NULL,
            nome_servizio varchar(255) NOT NULL,
            prezzo decimal(10,2) NOT NULL DEFAULT 0,
            sconto decimal(10,2) DEFAULT 0,
            PRIMARY KEY (id),
            KEY preventivo_id (preventivo_id),
            CONSTRAINT fk_preventivo FOREIGN KEY (preventivo_id)
                REFERENCES $table_preventivi(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_preventivi);
        dbDelta($sql_servizi);
        
        // Salva versione database
        update_option('mm_preventivi_db_version', MM_PREVENTIVI_VERSION);
    }
    
    /**
     * Salva preventivo
     */
    public static function save_preventivo($data) {
        global $wpdb;
        
        // Validazione dati
        if (!MM_Security::validate_preventivo_data($data)) {
            return new WP_Error('invalid_data', __('Dati non validi', 'mm-preventivi'));
        }
        
        // Sanitizzazione
        $data = MM_Security::sanitize_preventivo_data($data);
        
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_servizi = $wpdb->prefix . self::$table_servizi;
        
        // Genera numero preventivo
        $numero_preventivo = self::generate_numero_preventivo();
        
        // Prepara dati preventivo
        $preventivo_data = array(
            'numero_preventivo' => $numero_preventivo,
            'data_preventivo' => $data['data_preventivo'],
            'sposi' => $data['sposi'],
            'email' => $data['email'],
            'telefono' => $data['telefono'],
            'data_evento' => $data['data_evento'],
            'location' => $data['location'],
            'tipo_evento' => $data['tipo_evento'],
            'cerimonia' => isset($data['cerimonia']) ? json_encode($data['cerimonia']) : null,
            'servizi_extra' => isset($data['servizi_extra']) ? json_encode($data['servizi_extra']) : null,
            'note' => $data['note'],
            'totale_servizi' => $data['totale_servizi'],
            'sconto' => isset($data['sconto']) ? $data['sconto'] : 0,
            'sconto_percentuale' => isset($data['sconto_percentuale']) ? $data['sconto_percentuale'] : 0,
            'applica_enpals' => isset($data['applica_enpals']) ? ($data['applica_enpals'] ? 1 : 0) : 1,
            'applica_iva' => isset($data['applica_iva']) ? ($data['applica_iva'] ? 1 : 0) : 1,
            'enpals' => $data['enpals'],
            'iva' => $data['iva'],
            'totale' => $data['totale'],
            'data_acconto' => $data['data_acconto'],
            'importo_acconto' => $data['importo_acconto'],
            'stato' => 'attivo',
            'created_by' => get_current_user_id()
        );
        
        // Inserisci preventivo
        $wpdb->insert(
            $table_preventivi,
            $preventivo_data,
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d',
                '%f', '%f', '%f', '%s', '%f', '%s', '%d'
            )
        );
        
        $preventivo_id = $wpdb->insert_id;
        
        if (!$preventivo_id) {
            return new WP_Error('db_error', __('Errore nel salvataggio del preventivo', 'mm-preventivi'));
        }
        
        // Inserisci servizi
        if (isset($data['servizi']) && is_array($data['servizi'])) {
            foreach ($data['servizi'] as $servizio) {
                $wpdb->insert(
                    $table_servizi,
                    array(
                        'preventivo_id' => $preventivo_id,
                        'nome_servizio' => $servizio['nome'],
                        'prezzo' => $servizio['prezzo'],
                        'sconto' => isset($servizio['sconto']) ? $servizio['sconto'] : 0
                    ),
                    array('%d', '%s', '%f', '%f')
                );
            }
        }
        
        return $preventivo_id;
    }
    
    /**
     * Ottieni preventivo
     */
    public static function get_preventivo($id) {
        global $wpdb;
        
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_servizi = $wpdb->prefix . self::$table_servizi;
        
        $preventivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_preventivi WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if (!$preventivo) {
            return null;
        }
        
        // Decodifica JSON
        if ($preventivo['cerimonia']) {
            $preventivo['cerimonia'] = json_decode($preventivo['cerimonia'], true);
        }
        if ($preventivo['servizi_extra']) {
            $preventivo['servizi_extra'] = json_decode($preventivo['servizi_extra'], true);
        }
        
        // Ottieni servizi
        $servizi = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_servizi WHERE preventivo_id = %d",
            $id
        ), ARRAY_A);
        
        $preventivo['servizi'] = $servizi;
        
        return $preventivo;
    }
    
    /**
     * Ottieni tutti i preventivi
     */
    public static function get_all_preventivi($filters = array()) {
        global $wpdb;
        
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        
        $where = array('1=1');
        $where_values = array();
        
        // Filtri
        if (isset($filters['stato']) && !empty($filters['stato'])) {
            $where[] = 'stato = %s';
            $where_values[] = $filters['stato'];
        }
        
        if (isset($filters['data_da']) && !empty($filters['data_da'])) {
            $where[] = 'data_evento >= %s';
            $where_values[] = $filters['data_da'];
        }
        
        if (isset($filters['data_a']) && !empty($filters['data_a'])) {
            $where[] = 'data_evento <= %s';
            $where_values[] = $filters['data_a'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = '(sposi LIKE %s OR email LIKE %s OR numero_preventivo LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM $table_preventivi WHERE $where_clause ORDER BY data_evento DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Elimina preventivo
     */
    public static function delete_preventivo($id) {
        global $wpdb;
        
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        
        // I servizi verranno eliminati automaticamente per CASCADE
        return $wpdb->delete(
            $table_preventivi,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Aggiorna stato preventivo
     */
    public static function update_stato($id, $stato) {
        global $wpdb;
        
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        
        $stati_validi = array('bozza', 'attivo', 'accettato', 'rifiutato', 'completato');
        
        if (!in_array($stato, $stati_validi)) {
            return false;
        }
        
        return $wpdb->update(
            $table_preventivi,
            array('stato' => $stato),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Genera numero preventivo
     */
    private static function generate_numero_preventivo() {
        $anno = date('Y');
        $mese = date('m');
        
        global $wpdb;
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        
        // Conta preventivi del mese
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_preventivi 
             WHERE numero_preventivo LIKE %s",
            $anno . $mese . '%'
        ));
        
        $progressivo = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        return $anno . $mese . $progressivo;
    }
    
    /**
     * Ottieni statistiche
     */
    public static function get_statistics() {
        global $wpdb;
        
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        
        $stats = array(
            'totale_preventivi' => 0,
            'preventivi_attivi' => 0,
            'preventivi_accettati' => 0,
            'totale_fatturato' => 0,
            'fatturato_mese' => 0
        );
        
        // Totale preventivi
        $stats['totale_preventivi'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_preventivi"
        );
        
        // Preventivi attivi
        $stats['preventivi_attivi'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_preventivi WHERE stato = 'attivo'"
        );
        
        // Preventivi accettati
        $stats['preventivi_accettati'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_preventivi WHERE stato = 'accettato'"
        );
        
        // Totale fatturato
        $stats['totale_fatturato'] = $wpdb->get_var(
            "SELECT SUM(totale) FROM $table_preventivi WHERE stato IN ('accettato', 'completato')"
        );
        
        // Fatturato mese corrente
        $stats['fatturato_mese'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(totale) FROM $table_preventivi 
             WHERE stato IN ('accettato', 'completato') 
             AND MONTH(data_evento) = %d 
             AND YEAR(data_evento) = %d",
            date('m'),
            date('Y')
        ));
        
        return $stats;
    }
}
