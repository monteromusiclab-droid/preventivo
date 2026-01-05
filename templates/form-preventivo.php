<?php
/**
 * Template: Form Preventivo Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mm-preventivi-container">
    <form id="mm-preventivo-form" class="mm-preventivi-form" method="post">
        
        <!-- Header -->
        <div class="mm-form-header">
            <h1>✨ Richiedi il Tuo Preventivo</h1>
            <p>DJ • Animazione • Scenografie • Photo Booth</p>
        </div>
        
        <!-- Form Body -->
        <div class="mm-form-body">
            
            <!-- Dati Cliente -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📋 Dati Cliente</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Data Preventivo <span class="required">*</span></label>
                        <input type="date" id="data_preventivo" name="data_preventivo" required>
                    </div>
                    <div class="mm-form-group">
                        <label>Sposi / Cliente <span class="required">*</span></label>
                        <input type="text" id="sposi" name="sposi" placeholder="Nome e cognome" required>
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Email</label>
                        <input type="email" id="email" name="email" placeholder="email@esempio.it">
                    </div>
                    <div class="mm-form-group">
                        <label>Telefono</label>
                        <input type="tel" id="telefono" name="telefono" placeholder="333-7512343">
                    </div>
                </div>
            </div>
            
            <!-- Dati Evento -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📅 Dati Evento</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Data Evento <span class="required">*</span></label>
                        <input type="date" id="data_evento" name="data_evento" required>
                    </div>
                    <div class="mm-form-group">
                        <label>Location</label>
                        <input type="text" id="location" name="location" placeholder="Nome location">
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Tipo Evento</label>
                        <div class="mm-radio-group">
                            <div class="mm-radio-item">
                                <input type="radio" id="pranzo" name="tipo_evento" value="Pranzo">
                                <label for="pranzo">Pranzo</label>
                            </div>
                            <div class="mm-radio-item">
                                <input type="radio" id="cena" name="tipo_evento" value="Cena" checked>
                                <label for="cena">Cena</label>
                            </div>
                        </div>
                    </div>
                    <div class="mm-form-group">
                        <label>Cerimonia</label>
                        <div class="mm-checkbox-group">
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="rito" name="cerimonia[]" value="Rito">
                                <label for="rito">Rito</label>
                            </div>
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="violino" name="cerimonia[]" value="Violino">
                                <label for="violino">Violino</label>
                            </div>
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="piano" name="cerimonia[]" value="Piano">
                                <label for="piano">Piano</label>
                            </div>
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="arpa" name="cerimonia[]" value="Arpa">
                                <label for="arpa">Arpa</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Servizi -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">🎉 Servizi Richiesti</h2>
                <div class="mm-services-list">
                    <?php
                    $servizi = array(
                        'dj' => array('label' => 'DJ + ANIMATORE', 'price' => 800),
                        'strumento' => array('label' => 'STRUMENTO (SAX/VIOLINO)', 'price' => 300),
                        'altrostr' => array('label' => 'ALTRO STRUMENTO', 'price' => 0),
                        'cantante' => array('label' => 'CANTANTE', 'price' => 0),
                        'band' => array('label' => 'BAND LIVE', 'price' => 0),
                        '2imp' => array('label' => '2° IMPIANTO', 'price' => 0),
                        'luci' => array('label' => 'IMPIANTO LUCI', 'price' => 0),
                        'proiezioni' => array('label' => 'PROIEZIONI', 'price' => 0),
                        'photobooth' => array('label' => 'PHOTOBOOTH', 'price' => 0),
                        'fontane' => array('label' => 'FONTANE FREDDE', 'price' => 0),
                        'fumo' => array('label' => 'FUMO BASSO', 'price' => 0),
                        'macchina' => array('label' => 'MACCHINA BOLLE', 'price' => 0),
                        'fuochi' => array('label' => 'FUOCHI D\'ARTIFICIO', 'price' => 0),
                        'gadget' => array('label' => 'GADGET', 'price' => 0),
                        'gun' => array('label' => 'GUN CO2', 'price' => 0),
                        'djafter' => array('label' => 'DJ AFTER', 'price' => 0),
                        'altro' => array('label' => 'ALTRO', 'price' => 0),
                    );
                    
                    foreach ($servizi as $key => $servizio) :
                        $checked = ($key === 'dj') ? 'checked' : '';
                    ?>
                    <div class="mm-service-item">
                        <input type="checkbox" id="srv_<?php echo esc_attr($key); ?>" <?php echo $checked; ?>>
                        <label for="srv_<?php echo esc_attr($key); ?>"><?php echo esc_html($servizio['label']); ?></label>
                        <div class="mm-service-pricing">
                            <input type="number" id="price_<?php echo esc_attr($key); ?>" placeholder="€" value="<?php echo esc_attr($servizio['price']); ?>" min="0" step="0.01" class="mm-price-input">
                            <input type="number" id="discount_<?php echo esc_attr($key); ?>" placeholder="Sconto €" value="0" min="0" step="0.01" class="mm-discount-input" title="Sconto fisso per questo servizio">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Servizi Extra -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">✨ Servizi Aggiuntivi</h2>
                <div class="mm-checkbox-group">
                    <?php
                    $extras = array('Accoglienza', 'Antipasti', 'Sala', 'Torta', 'Buffet f/d', 'After Party');
                    foreach ($extras as $extra) :
                    ?>
                    <div class="mm-checkbox-item">
                        <input type="checkbox" id="extra_<?php echo sanitize_title($extra); ?>" name="servizi_extra[]" value="<?php echo esc_attr($extra); ?>">
                        <label for="extra_<?php echo sanitize_title($extra); ?>"><?php echo esc_html($extra); ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Note -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📝 Note</h2>
                <div class="mm-form-row full">
                    <div class="mm-form-group">
                        <textarea id="note" name="note" placeholder="Inserisci eventuali note o richieste particolari..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Sconti e Opzioni -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">💰 Sconti e Opzioni</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Sconto Fisso (€)</label>
                        <input type="number" id="sconto" name="sconto" placeholder="0,00" min="0" step="0.01" value="0">
                    </div>
                    <div class="mm-form-group">
                        <label>Sconto Percentuale (%)</label>
                        <input type="number" id="sconto_percentuale" name="sconto_percentuale" placeholder="0" min="0" max="100" step="0.1" value="0">
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <div class="mm-checkbox-group">
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="applica_enpals" name="applica_enpals" value="1" checked>
                                <label for="applica_enpals">Applica Enpals (33%)</label>
                            </div>
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="applica_iva" name="applica_iva" value="1" checked>
                                <label for="applica_iva">Applica IVA (22%)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calcolo Prezzi -->
            <div class="mm-price-summary">
                <div class="mm-price-row">
                    <span class="label">Totale Servizi:</span>
                    <span class="value" id="subtotal">€ 0,00</span>
                </div>
                <div class="mm-price-row" id="sconto-row" style="display: none; color: #4caf50;">
                    <span class="label">- Sconto:</span>
                    <span class="value" id="sconto-display">€ 0,00</span>
                </div>
                <div class="mm-price-row" id="subtotal-sconto-row" style="display: none;">
                    <span class="label">Subtotale:</span>
                    <span class="value" id="subtotal-sconto">€ 0,00</span>
                </div>
                <div class="mm-price-row" id="enpals-row">
                    <span class="label">Ex Enpals (33%):</span>
                    <span class="value" id="enpals">€ 0,00</span>
                </div>
                <div class="mm-price-row" id="iva-row">
                    <span class="label">IVA (22%):</span>
                    <span class="value" id="iva">€ 0,00</span>
                </div>
                <div class="mm-price-row total">
                    <span class="label">TOTALE:</span>
                    <span class="value" id="total">€ 0,00</span>
                </div>
            </div>
            
            <!-- Acconto -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">💰 Acconto (opzionale)</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Data Acconto</label>
                        <input type="date" id="data_acconto" name="data_acconto">
                    </div>
                    <div class="mm-form-group">
                        <label>Importo Acconto (€)</label>
                        <input type="number" id="importo_acconto" name="importo_acconto" placeholder="0,00" min="0" step="0.01">
                    </div>
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="mm-form-actions">
                <button type="submit" class="mm-btn mm-btn-primary">
                    💾 Salva Preventivo
                </button>
                <button type="button" class="mm-btn mm-btn-secondary mm-btn-reset">
                    🔄 Reset Form
                </button>
            </div>
            
        </div>
    </form>
</div>
