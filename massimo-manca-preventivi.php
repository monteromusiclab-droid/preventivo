<?php
/**
 * Plugin Name: Massimo Manca - Generatore Preventivi
 * Plugin URI: https://massimomanca.it
 * Description: Sistema professionale per la creazione e gestione di preventivi per eventi con DJ, animazione, scenografie e photo booth. Include database sicuro e pannello amministratore.
 * Version: 1.0.0
 * Author: Massimo Manca
 * Author URI: https://massimomanca.it
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mm-preventivi
 * Domain Path: /languages
 */

// Impedisci accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti del plugin
define('MM_PREVENTIVI_VERSION', '1.0.0');
define('MM_PREVENTIVI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MM_PREVENTIVI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MM_PREVENTIVI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale del plugin
 */
class MM_Preventivi {
    
    /**
     * Istanza singleton
     */
    private static $instance = null;
    
    /**
     * Ottieni istanza singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Costruttore
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Carica dipendenze
     */
    private function load_dependencies() {
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'includes/class-mm-database.php';
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'includes/class-mm-security.php';
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'includes/class-mm-frontend.php';
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'admin/class-mm-admin.php';
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'includes/class-mm-pdf-generator.php';
    }
    
    /**
     * Inizializza hooks
     */
    private function init_hooks() {
        // Attivazione/Disattivazione plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Init
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Enqueue scripts e styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Shortcode
        add_shortcode('mm_preventivo_form', array($this, 'render_form_shortcode'));
    }
    
    /**
     * Attivazione plugin
     */
    public function activate() {
        MM_Database::create_tables();
        flush_rewrite_rules();
    }
    
    /**
     * Disattivazione plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Inizializzazione
     */
    public function init() {
        load_plugin_textdomain('mm-preventivi', false, dirname(MM_PREVENTIVI_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Inizializzazione admin
     */
    public function admin_init() {
        // Verifica permessi
        if (!current_user_can('manage_options')) {
            return;
        }
    }
    
    /**
     * Enqueue assets frontend
     */
    public function enqueue_frontend_assets() {
        if (is_page() && has_shortcode(get_post()->post_content, 'mm_preventivo_form')) {
            wp_enqueue_style(
                'mm-preventivi-frontend',
                MM_PREVENTIVI_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                MM_PREVENTIVI_VERSION
            );
            
            wp_enqueue_script(
                'mm-preventivi-frontend',
                MM_PREVENTIVI_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                MM_PREVENTIVI_VERSION,
                true
            );
            
            // Localizza script
            wp_localize_script('mm-preventivi-frontend', 'mmPreventivi', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mm_preventivi_nonce'),
                'pdfNonce' => wp_create_nonce('mm_preventivi_view_pdf'),
                'strings' => array(
                    'error' => __('Si è verificato un errore. Riprova.', 'mm-preventivi'),
                    'success' => __('Preventivo salvato con successo!', 'mm-preventivi'),
                )
            ));
        }
    }
    
    /**
     * Enqueue assets admin
     */
    public function enqueue_admin_assets($hook) {
        // Carica solo nelle pagine del plugin
        if (strpos($hook, 'mm-preventivi') === false) {
            return;
        }

        // Enqueue WordPress Media Library
        wp_enqueue_media();

        wp_enqueue_style(
            'mm-preventivi-admin',
            MM_PREVENTIVI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MM_PREVENTIVI_VERSION
        );

        wp_enqueue_script(
            'mm-preventivi-admin',
            MM_PREVENTIVI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            MM_PREVENTIVI_VERSION,
            true
        );

        wp_localize_script('mm-preventivi-admin', 'mmPreventiviAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mm_preventivi_admin_nonce'),
        ));
    }
    
    /**
     * Render shortcode form
     */
    public function render_form_shortcode($atts) {
        ob_start();
        MM_Frontend::render_form();
        return ob_get_clean();
    }
}

/**
 * Inizializza plugin
 */
function mm_preventivi_init() {
    return MM_Preventivi::get_instance();
}

// Avvia plugin
mm_preventivi_init();
