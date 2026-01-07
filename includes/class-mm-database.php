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
     * Nome tabella catalogo servizi
     */
    private static $table_catalogo_servizi = 'mm_catalogo_servizi';

    /**
     * Nome tabella categorie
     */
    private static $table_categorie = 'mm_categorie_preventivi';

    /**
     * Crea tabelle database
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_servizi = $wpdb->prefix . self::$table_servizi;
        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;
        $table_categorie = $wpdb->prefix . self::$table_categorie;

        // Tabella categorie
        $sql_categorie = "CREATE TABLE IF NOT EXISTS $table_categorie (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nome varchar(100) NOT NULL,
            descrizione text DEFAULT NULL,
            colore varchar(7) DEFAULT '#e91e63',
            icona varchar(50) DEFAULT '📋',
            ordinamento int DEFAULT 0,
            attivo tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nome (nome),
            KEY attivo (attivo)
        ) $charset_collate;";

        // Tabella preventivi
        $sql_preventivi = "CREATE TABLE IF NOT EXISTS $table_preventivi (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            numero_preventivo varchar(50) NOT NULL,
            categoria_id bigint(20) UNSIGNED DEFAULT NULL,
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
            KEY categoria_id (categoria_id),
            KEY data_evento (data_evento),
            KEY stato (stato),
            KEY created_by (created_by),
            CONSTRAINT fk_categoria FOREIGN KEY (categoria_id)
                REFERENCES $table_categorie(id) ON DELETE SET NULL
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

        // Tabella catalogo servizi
        $sql_catalogo = "CREATE TABLE IF NOT EXISTS $table_catalogo (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nome_servizio varchar(255) NOT NULL,
            descrizione text DEFAULT NULL,
            prezzo_default decimal(10,2) DEFAULT 0,
            categoria varchar(100) DEFAULT NULL,
            attivo tinyint(1) DEFAULT 1,
            ordinamento int DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY attivo (attivo),
            KEY categoria (categoria)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_categorie);
        dbDelta($sql_preventivi);
        dbDelta($sql_servizi);
        dbDelta($sql_catalogo);

        // Aggiungi colonna sconto se non esiste (migrazione)
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_servizi LIKE 'sconto'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_servizi ADD COLUMN sconto decimal(10,2) DEFAULT 0 AFTER prezzo");
        }

        // Aggiungi colonne sconto al preventivo se non esistono
        $column_sconto = $wpdb->get_results("SHOW COLUMNS FROM $table_preventivi LIKE 'sconto'");
        if (empty($column_sconto)) {
            $wpdb->query("ALTER TABLE $table_preventivi ADD COLUMN sconto decimal(10,2) DEFAULT 0 AFTER totale_servizi");
        }

        $column_sconto_perc = $wpdb->get_results("SHOW COLUMNS FROM $table_preventivi LIKE 'sconto_percentuale'");
        if (empty($column_sconto_perc)) {
            $wpdb->query("ALTER TABLE $table_preventivi ADD COLUMN sconto_percentuale decimal(5,2) DEFAULT 0 AFTER sconto");
        }

        $column_enpals = $wpdb->get_results("SHOW COLUMNS FROM $table_preventivi LIKE 'applica_enpals'");
        if (empty($column_enpals)) {
            $wpdb->query("ALTER TABLE $table_preventivi ADD COLUMN applica_enpals tinyint(1) DEFAULT 1 AFTER sconto_percentuale");
        }

        $column_iva = $wpdb->get_results("SHOW COLUMNS FROM $table_preventivi LIKE 'applica_iva'");
        if (empty($column_iva)) {
            $wpdb->query("ALTER TABLE $table_preventivi ADD COLUMN applica_iva tinyint(1) DEFAULT 1 AFTER applica_enpals");
        }

        // Aggiungi colonna categoria_id se non esiste
        $column_categoria = $wpdb->get_results("SHOW COLUMNS FROM $table_preventivi LIKE 'categoria_id'");
        if (empty($column_categoria)) {
            $wpdb->query("ALTER TABLE $table_preventivi ADD COLUMN categoria_id bigint(20) UNSIGNED DEFAULT NULL AFTER numero_preventivo");
            $wpdb->query("ALTER TABLE $table_preventivi ADD KEY categoria_id (categoria_id)");
        }

        // Inserisci categorie predefinite se la tabella è vuota
        $count_categorie = $wpdb->get_var("SELECT COUNT(*) FROM $table_categorie");
        if ($count_categorie == 0) {
            $wpdb->insert($table_categorie, array(
                'nome' => 'Matrimonio',
                'descrizione' => 'Eventi matrimoniali',
                'colore' => '#e91e63',
                'icona' => '💒',
                'ordinamento' => 1,
                'attivo' => 1
            ));
            $wpdb->insert($table_categorie, array(
                'nome' => 'Compleanno',
                'descrizione' => 'Feste di compleanno',
                'colore' => '#2196f3',
                'icona' => '🎂',
                'ordinamento' => 2,
                'attivo' => 1
            ));
            $wpdb->insert($table_categorie, array(
                'nome' => 'Evento Aziendale',
                'descrizione' => 'Eventi aziendali e corporate',
                'colore' => '#ff9800',
                'icona' => '💼',
                'ordinamento' => 3,
                'attivo' => 1
            ));
            $wpdb->insert($table_categorie, array(
                'nome' => 'Festa Privata',
                'descrizione' => 'Feste private ed eventi privati',
                'colore' => '#9c27b0',
                'icona' => '🎉',
                'ordinamento' => 4,
                'attivo' => 1
            ));
            $wpdb->insert($table_categorie, array(
                'nome' => 'Altro',
                'descrizione' => 'Altri tipi di eventi',
                'colore' => '#607d8b',
                'icona' => '📋',
                'ordinamento' => 5,
                'attivo' => 1
            ));
        }

        // Salva versione database
        update_option('mm_preventivi_db_version', MM_PREVENTIVI_VERSION);
    }
    
    /**
     * Salva preventivo
     */
    public static function save_preventivo($data) {
        global $wpdb;
        
        // Validazione dati
        $validation = MM_Security::validate_preventivo_data($data);
        if (!$validation) {
            // Log per debug
            error_log('MM Preventivi - Validazione fallita per: ' . print_r($data, true));
            return new WP_Error('invalid_data', __('Dati non validi. Controlla i campi obbligatori.', 'mm-preventivi'));
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
            'categoria_id' => isset($data['categoria_id']) && !empty($data['categoria_id']) ? intval($data['categoria_id']) : null,
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
            'applica_enpals' => isset($data['applica_enpals']) ? (empty($data['applica_enpals']) ? 0 : 1) : 1,
            'applica_iva' => isset($data['applica_iva']) ? (empty($data['applica_iva']) ? 0 : 1) : 1,
            'enpals' => $data['enpals'],
            'iva' => $data['iva'],
            'totale' => $data['totale'],
            'data_acconto' => $data['data_acconto'],
            'importo_acconto' => $data['importo_acconto'],
            'stato' => 'attivo',
            'created_by' => get_current_user_id()
        );

        // Inserisci preventivo
        $result = $wpdb->insert(
            $table_preventivi,
            $preventivo_data,
            array(
                '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d',
                '%f', '%f', '%f', '%s', '%f', '%s', '%d'
            )
        );

        // Log dettagliato per debug
        if ($result === false) {
            error_log('MM Preventivi - Errore INSERT preventivo: ' . $wpdb->last_error);
            error_log('MM Preventivi - Last query: ' . $wpdb->last_query);
            error_log('MM Preventivi - Data: ' . print_r($preventivo_data, true));
            return new WP_Error('db_error', sprintf(
                __('Errore database nel salvataggio del preventivo: %s', 'mm-preventivi'),
                $wpdb->last_error
            ));
        }

        $preventivo_id = $wpdb->insert_id;

        if (!$preventivo_id) {
            error_log('MM Preventivi - Insert ID = 0. Last error: ' . $wpdb->last_error);
            return new WP_Error('db_error', __('Errore nel salvataggio del preventivo: ID non generato', 'mm-preventivi'));
        }
        
        // Inserisci servizi
        if (isset($data['servizi']) && is_array($data['servizi'])) {
            foreach ($data['servizi'] as $servizio) {
                $servizio_result = $wpdb->insert(
                    $table_servizi,
                    array(
                        'preventivo_id' => $preventivo_id,
                        'nome_servizio' => $servizio['nome'],
                        'prezzo' => $servizio['prezzo'],
                        'sconto' => isset($servizio['sconto']) ? $servizio['sconto'] : 0
                    ),
                    array('%d', '%s', '%f', '%f')
                );

                if ($servizio_result === false) {
                    error_log('MM Preventivi - Errore INSERT servizio: ' . $wpdb->last_error);
                    error_log('MM Preventivi - Servizio data: ' . print_r($servizio, true));
                }
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
        $table_categorie = $wpdb->prefix . self::$table_categorie;

        $where = array('1=1');
        $where_values = array();

        // Filtri
        if (isset($filters['stato']) && !empty($filters['stato'])) {
            $where[] = 'p.stato = %s';
            $where_values[] = $filters['stato'];
        }

        if (isset($filters['categoria_id']) && !empty($filters['categoria_id'])) {
            $where[] = 'p.categoria_id = %d';
            $where_values[] = intval($filters['categoria_id']);
        }

        if (isset($filters['data_da']) && !empty($filters['data_da'])) {
            $where[] = 'p.data_evento >= %s';
            $where_values[] = $filters['data_da'];
        }

        if (isset($filters['data_a']) && !empty($filters['data_a'])) {
            $where[] = 'p.data_evento <= %s';
            $where_values[] = $filters['data_a'];
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = '(p.sposi LIKE %s OR p.email LIKE %s OR p.numero_preventivo LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT p.*, c.nome as categoria_nome, c.colore as categoria_colore, c.icona as categoria_icona
                  FROM $table_preventivi p
                  LEFT JOIN $table_categorie c ON p.categoria_id = c.id
                  WHERE $where_clause
                  ORDER BY p.data_evento DESC";

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
            'preventivi_rifiutati' => 0,
            'totale_fatturato' => 0,
            'fatturato_mese' => 0,
            'valore_medio_preventivo' => 0,
            'tasso_conversione' => 0
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

        // Preventivi rifiutati
        $stats['preventivi_rifiutati'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_preventivi WHERE stato = 'rifiutato'"
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

        // Valore medio preventivo
        $stats['valore_medio_preventivo'] = $wpdb->get_var(
            "SELECT AVG(totale) FROM $table_preventivi WHERE stato IN ('accettato', 'completato')"
        );

        // Tasso di conversione (preventivi accettati / totali * 100)
        if ($stats['totale_preventivi'] > 0) {
            $stats['tasso_conversione'] = ($stats['preventivi_accettati'] / $stats['totale_preventivi']) * 100;
        }

        return $stats;
    }

    /**
     * Ottieni attività recenti
     */
    public static function get_recent_activity($limit = 10) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        $query = $wpdb->prepare(
            "SELECT * FROM $table_preventivi
             ORDER BY id DESC
             LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        if (!$results) {
            return array();
        }

        return $results;
    }

    /**
     * Aggiorna stato preventivo
     */
    public static function update_preventivo_status($id, $nuovo_stato) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        // Verifica che il preventivo esista
        $preventivo = self::get_preventivo($id);
        if (!$preventivo) {
            return new WP_Error('preventivo_not_found', __('Preventivo non trovato.', 'mm-preventivi'));
        }

        // Aggiorna lo stato (updated_at si aggiorna automaticamente)
        $result = $wpdb->update(
            $table_preventivi,
            array(
                'stato' => $nuovo_stato
            ),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('update_failed', __('Errore durante l\'aggiornamento dello stato.', 'mm-preventivi'));
        }

        return true;
    }

    /**
     * Aggiorna preventivo completo
     */
    public static function update_preventivo($id, $data) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_servizi = $wpdb->prefix . self::$table_servizi;

        // Verifica che il preventivo esista
        $preventivo = self::get_preventivo($id);
        if (!$preventivo) {
            return new WP_Error('preventivo_not_found', __('Preventivo non trovato.', 'mm-preventivi'));
        }

        // Sanitizzazione
        $data = MM_Security::sanitize_preventivo_data($data);

        // Prepara dati preventivo (stessa struttura di save_preventivo, SENZA campo servizi)
        $preventivo_data = array(
            'categoria_id' => isset($data['categoria_id']) && !empty($data['categoria_id']) ? intval($data['categoria_id']) : null,
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
            'applica_enpals' => isset($data['applica_enpals']) ? (empty($data['applica_enpals']) ? 0 : 1) : 1,
            'applica_iva' => isset($data['applica_iva']) ? (empty($data['applica_iva']) ? 0 : 1) : 1,
            'enpals' => $data['enpals'],
            'iva' => $data['iva'],
            'totale' => $data['totale'],
            'data_acconto' => $data['data_acconto'],
            'importo_acconto' => $data['importo_acconto'],
            'stato' => isset($data['stato']) ? $data['stato'] : $preventivo['stato']
        );

        // Aggiorna preventivo
        $result = $wpdb->update(
            $table_preventivi,
            $preventivo_data,
            array('id' => $id),
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d',
                '%f', '%f', '%f', '%s', '%f', '%s'
            ),
            array('%d')
        );

        if ($result === false) {
            error_log('MM Preventivi - Errore UPDATE preventivo: ' . $wpdb->last_error);
            return new WP_Error('db_error', __('Errore nell\'aggiornamento del preventivo.', 'mm-preventivi'));
        }

        // Elimina servizi esistenti dalla tabella separata
        $wpdb->delete($table_servizi, array('preventivo_id' => $id), array('%d'));

        // Inserisci servizi aggiornati nella tabella separata
        if (isset($data['servizi']) && is_array($data['servizi'])) {
            foreach ($data['servizi'] as $servizio) {
                $wpdb->insert(
                    $table_servizi,
                    array(
                        'preventivo_id' => $id,
                        'nome_servizio' => $servizio['nome_servizio'],
                        'prezzo' => $servizio['prezzo'],
                        'sconto' => isset($servizio['sconto']) ? $servizio['sconto'] : 0
                    ),
                    array('%d', '%s', '%f', '%f')
                );
            }
        }

        return true;
    }

    /**
     * CRUD Catalogo Servizi
     */

    /**
     * Ottieni tutti i servizi dal catalogo
     */
    public static function get_catalogo_servizi($filters = array()) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        $where = array('1=1');
        $where_values = array();

        if (isset($filters['attivo'])) {
            $where[] = 'attivo = %d';
            $where_values[] = $filters['attivo'];
        }

        if (isset($filters['categoria']) && !empty($filters['categoria'])) {
            $where[] = 'categoria = %s';
            $where_values[] = $filters['categoria'];
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT * FROM $table_catalogo WHERE $where_clause ORDER BY ordinamento ASC, nome_servizio ASC";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Ottieni singolo servizio dal catalogo
     */
    public static function get_catalogo_servizio($id) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_catalogo WHERE id = %d",
            $id
        ), ARRAY_A);
    }

    /**
     * Salva servizio nel catalogo (nuovo)
     */
    public static function save_catalogo_servizio($data) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        $servizio_data = array(
            'nome_servizio' => sanitize_text_field($data['nome_servizio']),
            'descrizione' => isset($data['descrizione']) ? sanitize_textarea_field($data['descrizione']) : '',
            'prezzo_default' => isset($data['prezzo_default']) ? floatval($data['prezzo_default']) : 0,
            'categoria' => isset($data['categoria']) ? sanitize_text_field($data['categoria']) : '',
            'attivo' => isset($data['attivo']) ? intval($data['attivo']) : 1,
            'ordinamento' => isset($data['ordinamento']) ? intval($data['ordinamento']) : 0
        );

        $result = $wpdb->insert(
            $table_catalogo,
            $servizio_data,
            array('%s', '%s', '%f', '%s', '%d', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore nel salvataggio del servizio.', 'mm-preventivi'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Aggiorna servizio nel catalogo
     */
    public static function update_catalogo_servizio($id, $data) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        $servizio_data = array(
            'nome_servizio' => sanitize_text_field($data['nome_servizio']),
            'descrizione' => isset($data['descrizione']) ? sanitize_textarea_field($data['descrizione']) : '',
            'prezzo_default' => isset($data['prezzo_default']) ? floatval($data['prezzo_default']) : 0,
            'categoria' => isset($data['categoria']) ? sanitize_text_field($data['categoria']) : '',
            'attivo' => isset($data['attivo']) ? intval($data['attivo']) : 1,
            'ordinamento' => isset($data['ordinamento']) ? intval($data['ordinamento']) : 0
        );

        $result = $wpdb->update(
            $table_catalogo,
            $servizio_data,
            array('id' => $id),
            array('%s', '%s', '%f', '%s', '%d', '%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore nell\'aggiornamento del servizio.', 'mm-preventivi'));
        }

        return true;
    }

    /**
     * Elimina servizio dal catalogo
     */
    public static function delete_catalogo_servizio($id) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        return $wpdb->delete(
            $table_catalogo,
            array('id' => $id),
            array('%d')
        );
    }

    // ===================================
    // GESTIONE CATEGORIE
    // ===================================

    /**
     * Ottieni tutte le categorie
     */
    public static function get_categorie($filters = array()) {
        global $wpdb;

        $table_categorie = $wpdb->prefix . self::$table_categorie;

        $where = array('1=1');

        // Filtro attivo
        if (isset($filters['attivo'])) {
            $where[] = $wpdb->prepare('attivo = %d', $filters['attivo']);
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT * FROM $table_categorie WHERE $where_clause ORDER BY ordinamento ASC, nome ASC";

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Ottieni singola categoria
     */
    public static function get_categoria($id) {
        global $wpdb;

        $table_categorie = $wpdb->prefix . self::$table_categorie;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_categorie WHERE id = %d", $id),
            ARRAY_A
        );
    }

    /**
     * Salva nuova categoria
     */
    public static function save_categoria($data) {
        global $wpdb;

        $table_categorie = $wpdb->prefix . self::$table_categorie;

        // Validazione
        if (empty($data['nome'])) {
            return new WP_Error('invalid_data', __('Il nome della categoria è obbligatorio.', 'mm-preventivi'));
        }

        // Verifica che il nome non esista già
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_categorie WHERE nome = %s",
            $data['nome']
        ));

        if ($exists) {
            return new WP_Error('duplicate_name', __('Esiste già una categoria con questo nome.', 'mm-preventivi'));
        }

        $insert_data = array(
            'nome' => sanitize_text_field($data['nome']),
            'descrizione' => isset($data['descrizione']) ? sanitize_textarea_field($data['descrizione']) : '',
            'colore' => isset($data['colore']) ? sanitize_hex_color($data['colore']) : '#e91e63',
            'icona' => isset($data['icona']) ? sanitize_text_field($data['icona']) : '📋',
            'ordinamento' => isset($data['ordinamento']) ? intval($data['ordinamento']) : 0,
            'attivo' => isset($data['attivo']) ? intval($data['attivo']) : 1
        );

        $result = $wpdb->insert($table_categorie, $insert_data);

        if ($result === false) {
            return new WP_Error('db_error', __('Errore nel salvataggio della categoria.', 'mm-preventivi'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Aggiorna categoria
     */
    public static function update_categoria($id, $data) {
        global $wpdb;

        $table_categorie = $wpdb->prefix . self::$table_categorie;

        // Validazione
        if (empty($data['nome'])) {
            return new WP_Error('invalid_data', __('Il nome della categoria è obbligatorio.', 'mm-preventivi'));
        }

        // Verifica che il nome non esista già (escludendo l'ID corrente)
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_categorie WHERE nome = %s AND id != %d",
            $data['nome'],
            $id
        ));

        if ($exists) {
            return new WP_Error('duplicate_name', __('Esiste già una categoria con questo nome.', 'mm-preventivi'));
        }

        $update_data = array(
            'nome' => sanitize_text_field($data['nome']),
            'descrizione' => isset($data['descrizione']) ? sanitize_textarea_field($data['descrizione']) : '',
            'colore' => isset($data['colore']) ? sanitize_hex_color($data['colore']) : '#e91e63',
            'icona' => isset($data['icona']) ? sanitize_text_field($data['icona']) : '📋',
            'ordinamento' => isset($data['ordinamento']) ? intval($data['ordinamento']) : 0,
            'attivo' => isset($data['attivo']) ? intval($data['attivo']) : 1
        );

        $result = $wpdb->update(
            $table_categorie,
            $update_data,
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%d', '%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore nell\'aggiornamento della categoria.', 'mm-preventivi'));
        }

        return true;
    }

    /**
     * Elimina categoria
     */
    public static function delete_categoria($id) {
        global $wpdb;

        $table_categorie = $wpdb->prefix . self::$table_categorie;
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        // Verifica se ci sono preventivi che usano questa categoria
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_preventivi WHERE categoria_id = %d",
            $id
        ));

        if ($count > 0) {
            // Imposta categoria_id a NULL per i preventivi che la usano
            $wpdb->update(
                $table_preventivi,
                array('categoria_id' => null),
                array('categoria_id' => $id),
                array('%d'),
                array('%d')
            );
        }

        return $wpdb->delete(
            $table_categorie,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Conta preventivi per categoria
     */
    public static function count_preventivi_by_categoria($categoria_id = null) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        if ($categoria_id === null) {
            return $wpdb->get_var("SELECT COUNT(*) FROM $table_preventivi WHERE categoria_id IS NULL");
        }

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_preventivi WHERE categoria_id = %d",
            $categoria_id
        ));
    }
}
