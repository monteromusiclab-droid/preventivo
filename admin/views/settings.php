<?php
/**
 * Admin View: Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Salva impostazioni
if (isset($_POST['mm_save_settings'])) {
    check_admin_referer('mm_preventivi_settings');

    update_option('mm_preventivi_company_name', sanitize_text_field($_POST['company_name']));
    update_option('mm_preventivi_company_address', sanitize_text_field($_POST['company_address']));
    update_option('mm_preventivi_company_phone', sanitize_text_field($_POST['company_phone']));
    update_option('mm_preventivi_company_email', sanitize_email($_POST['company_email']));
    update_option('mm_preventivi_company_piva', sanitize_text_field($_POST['company_piva']));
    update_option('mm_preventivi_company_cf', sanitize_text_field($_POST['company_cf']));
    update_option('mm_preventivi_logo', esc_url_raw($_POST['company_logo']));

    echo '<div class="mm-notice mm-notice-success">Impostazioni salvate con successo!</div>';
}

// Carica impostazioni correnti
$company_name = get_option('mm_preventivi_company_name', 'MONTERO MUSIC di Massimo Manca');
$company_address = get_option('mm_preventivi_company_address', 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)');
$company_phone = get_option('mm_preventivi_company_phone', '333-7512343');
$company_email = get_option('mm_preventivi_company_email', 'info@massimomanca.it');
$company_piva = get_option('mm_preventivi_company_piva', 'P.I. 04867450753');
$company_cf = get_option('mm_preventivi_company_cf', 'C.F. MNCMSM79E01119H');
$company_logo = get_option('mm_preventivi_logo', '');
?>

<div class="wrap mm-admin-page">
    
    <div class="mm-admin-header">
        <h1>⚙️ Impostazioni</h1>
        <p>Configura le informazioni aziendali per i preventivi e i PDF</p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('mm_preventivi_settings'); ?>
        
        <!-- Company Info -->
        <div class="mm-settings-section">
            <h3>🏢 Informazioni Aziendali</h3>
            <p style="color: #666; margin-bottom: 20px;">
                Queste informazioni verranno utilizzate nei PDF dei preventivi.
            </p>
            
            <div class="mm-setting-row">
                <label for="company_name">Nome Azienda</label>
                <input type="text" 
                       id="company_name" 
                       name="company_name" 
                       value="<?php echo esc_attr($company_name); ?>" 
                       required>
            </div>
            
            <div class="mm-setting-row">
                <label for="company_address">Indirizzo</label>
                <input type="text" 
                       id="company_address" 
                       name="company_address" 
                       value="<?php echo esc_attr($company_address); ?>">
            </div>
            
            <div class="mm-setting-row">
                <label for="company_phone">Telefono</label>
                <input type="text" 
                       id="company_phone" 
                       name="company_phone" 
                       value="<?php echo esc_attr($company_phone); ?>">
            </div>
            
            <div class="mm-setting-row">
                <label for="company_email">Email</label>
                <input type="email" 
                       id="company_email" 
                       name="company_email" 
                       value="<?php echo esc_attr($company_email); ?>">
            </div>
            
            <div class="mm-setting-row">
                <label for="company_piva">Partita IVA</label>
                <input type="text" 
                       id="company_piva" 
                       name="company_piva" 
                       value="<?php echo esc_attr($company_piva); ?>">
            </div>
            
            <div class="mm-setting-row">
                <label for="company_cf">Codice Fiscale</label>
                <input type="text"
                       id="company_cf"
                       name="company_cf"
                       value="<?php echo esc_attr($company_cf); ?>">
            </div>

            <div class="mm-setting-row">
                <label for="company_logo">Logo Aziendale</label>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <input type="url"
                           id="company_logo"
                           name="company_logo"
                           value="<?php echo esc_url($company_logo); ?>"
                           placeholder="https://esempio.it/logo.png"
                           style="flex: 1;">
                    <button type="button" class="button mm-upload-logo-btn">
                        🖼️ Carica Immagine
                    </button>
                </div>
                <?php if (!empty($company_logo)): ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo esc_url($company_logo); ?>"
                         alt="Logo Preview"
                         style="max-height: 80px; border: 1px solid #ddd; padding: 5px; border-radius: 5px; background: white;">
                </div>
                <?php endif; ?>
                <p style="color: #999; font-size: 12px; margin-top: 5px;">
                    Il logo verrà mostrato nell'header dei PDF dei preventivi. Dimensione consigliata: 200x60px.
                </p>
            </div>
        </div>
        
        <!-- Shortcode Info -->
        <div class="mm-settings-section">
            <h3>📝 Come Usare il Plugin</h3>
            <p style="color: #666; margin-bottom: 15px;">
                Per mostrare il form di preventivo sul tuo sito, inserisci questo shortcode in una pagina o post:
            </p>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 14px;">
                [mm_preventivo_form]
            </div>
            <p style="color: #999; margin-top: 10px; font-size: 13px;">
                💡 Crea una nuova pagina, incolla lo shortcode e pubblica. Il form apparirà sulla pagina.
            </p>
        </div>
        
        <!-- Database Info -->
        <div class="mm-settings-section">
            <h3>💾 Informazioni Database</h3>
            <?php
            global $wpdb;
            $table_preventivi = $wpdb->prefix . 'mm_preventivi';
            $table_servizi = $wpdb->prefix . 'mm_preventivi_servizi';
            
            $count_preventivi = $wpdb->get_var("SELECT COUNT(*) FROM $table_preventivi");
            $count_servizi = $wpdb->get_var("SELECT COUNT(*) FROM $table_servizi");
            ?>
            <table class="mm-services-table">
                <tr>
                    <td><strong>Tabella Preventivi:</strong></td>
                    <td><?php echo esc_html($table_preventivi); ?></td>
                    <td><?php echo esc_html($count_preventivi); ?> record</td>
                </tr>
                <tr>
                    <td><strong>Tabella Servizi:</strong></td>
                    <td><?php echo esc_html($table_servizi); ?></td>
                    <td><?php echo esc_html($count_servizi); ?> record</td>
                </tr>
                <tr>
                    <td><strong>Versione Plugin:</strong></td>
                    <td colspan="2"><?php echo MM_PREVENTIVI_VERSION; ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Save Button -->
        <div style="margin-top: 30px;">
            <button type="submit" name="mm_save_settings" class="mm-btn-admin-primary" style="font-size: 16px; padding: 12px 24px;">
                💾 Salva Impostazioni
            </button>
        </div>
        
    </form>
    
</div>
