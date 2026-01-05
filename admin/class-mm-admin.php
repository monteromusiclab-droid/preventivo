<?php
/**
 * Pannello Amministratore
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_mm_delete_preventivo', array($this, 'ajax_delete_preventivo'));
        add_action('wp_ajax_mm_update_stato', array($this, 'ajax_update_stato'));
        add_action('wp_ajax_mm_export_pdf', array($this, 'ajax_export_pdf'));
    }
    
    /**
     * Aggiungi menu amministratore
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Preventivi', 'mm-preventivi'),
            __('Preventivi', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi',
            array($this, 'render_dashboard'),
            'dashicons-media-spreadsheet',
            30
        );
        
        add_submenu_page(
            'mm-preventivi',
            __('Tutti i Preventivi', 'mm-preventivi'),
            __('Tutti i Preventivi', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'mm-preventivi',
            __('Nuovo Preventivo', 'mm-preventivi'),
            __('Nuovo Preventivo', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi-new',
            array($this, 'render_new_preventivo')
        );

        add_submenu_page(
            'mm-preventivi',
            __('Statistiche', 'mm-preventivi'),
            __('Statistiche', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi-stats',
            array($this, 'render_statistics')
        );

        add_submenu_page(
            'mm-preventivi',
            __('Impostazioni', 'mm-preventivi'),
            __('Impostazioni', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi-settings',
            array($this, 'render_settings')
        );
    }
    
    /**
     * Registra impostazioni
     */
    public function register_settings() {
        register_setting('mm_preventivi_settings', 'mm_preventivi_logo');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_name');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_address');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_phone');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_email');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_piva');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_cf');
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        MM_Security::check_admin_permission();
        
        // Gestisci azioni
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            $this->render_view_preventivo(intval($_GET['id']));
            return;
        }
        
        // Ottieni preventivi
        $filters = array();
        
        if (isset($_GET['stato']) && !empty($_GET['stato'])) {
            $filters['stato'] = sanitize_text_field($_GET['stato']);
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = sanitize_text_field($_GET['search']);
        }
        
        $preventivi = MM_Database::get_all_preventivi($filters);
        
        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render dettaglio preventivo
     */
    private function render_view_preventivo($id) {
        MM_Security::check_admin_permission();
        
        $preventivo = MM_Database::get_preventivo($id);
        
        if (!$preventivo) {
            wp_die(__('Preventivo non trovato.', 'mm-preventivi'));
        }
        
        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/view-preventivo.php';
    }
    
    /**
     * Render nuovo preventivo
     */
    public function render_new_preventivo() {
        MM_Security::check_admin_permission();

        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/new-preventivo.php';
    }

    /**
     * Render statistiche
     */
    public function render_statistics() {
        MM_Security::check_admin_permission();

        $stats = MM_Database::get_statistics();

        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/statistics.php';
    }
    
    /**
     * Render impostazioni
     */
    public function render_settings() {
        MM_Security::check_admin_permission();
        
        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * AJAX: Elimina preventivo
     */
    public function ajax_delete_preventivo() {
        MM_Security::check_admin_permission();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }
        
        $id = intval($_POST['id']);
        
        $result = MM_Database::delete_preventivo($id);
        
        if ($result) {
            MM_Security::log_security_event('preventivo_deleted', array('id' => $id));
            wp_send_json_success(array('message' => __('Preventivo eliminato con successo.', 'mm-preventivi')));
        } else {
            wp_send_json_error(array('message' => __('Errore nell\'eliminazione del preventivo.', 'mm-preventivi')));
        }
    }
    
    /**
     * AJAX: Aggiorna stato
     */
    public function ajax_update_stato() {
        MM_Security::check_admin_permission();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }
        
        $id = intval($_POST['id']);
        $stato = sanitize_text_field($_POST['stato']);
        
        $result = MM_Database::update_stato($id, $stato);
        
        if ($result) {
            MM_Security::log_security_event('stato_updated', array('id' => $id, 'stato' => $stato));
            wp_send_json_success(array('message' => __('Stato aggiornato con successo.', 'mm-preventivi')));
        } else {
            wp_send_json_error(array('message' => __('Errore nell\'aggiornamento dello stato.', 'mm-preventivi')));
        }
    }
    
    /**
     * AJAX: Esporta PDF
     */
    public function ajax_export_pdf() {
        MM_Security::check_admin_permission();
        
        if (!isset($_GET['id'])) {
            wp_die(__('ID preventivo mancante.', 'mm-preventivi'));
        }
        
        $id = intval($_GET['id']);
        $preventivo = MM_Database::get_preventivo($id);
        
        if (!$preventivo) {
            wp_die(__('Preventivo non trovato.', 'mm-preventivi'));
        }
        
        MM_PDF_Generator::generate_pdf($preventivo);
        exit;
    }
}

// Inizializza
new MM_Admin();
