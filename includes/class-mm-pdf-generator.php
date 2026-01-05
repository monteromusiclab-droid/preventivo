<?php
/**
 * Generatore PDF - Massimo Manca Preventivi
 * Supporta TCPDF (se disponibile) con fallback HTML to PDF
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_PDF_Generator {

    /**
     * Genera PDF preventivo
     */
    public static function generate_pdf($preventivo) {
        // Carica TCPDF se disponibile
        if (!class_exists('TCPDF')) {
            // Prova a caricare TCPDF da plugin comuni o installazioni WordPress
            $tcpdf_paths = array(
                ABSPATH . 'wp-content/plugins/tcpdf/tcpdf.php',
                ABSPATH . 'wp-includes/TCPDF/tcpdf.php',
                dirname(__FILE__) . '/tcpdf/tcpdf.php',
            );

            foreach ($tcpdf_paths as $path) {
                if (file_exists($path)) {
                    require_once($path);
                    break;
                }
            }
        }

        // Se TCPDF è disponibile, usalo
        if (class_exists('TCPDF')) {
            self::generate_tcpdf($preventivo);
        } else {
            // Altrimenti usa il fallback HTML
            self::generate_html_pdf($preventivo);
        }
    }

    /**
     * Genera PDF con TCPDF
     */
    private static function generate_tcpdf($preventivo) {
        // Impostazioni aziendali
        $company_name = get_option('mm_preventivi_company_name', 'MONTERO MUSIC di Massimo Manca');
        $company_address = get_option('mm_preventivi_company_address', 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)');
        $company_phone = get_option('mm_preventivi_company_phone', '333-7512343');
        $company_email = get_option('mm_preventivi_company_email', 'info@massimomanca.it');
        $company_piva = get_option('mm_preventivi_company_piva', 'P.I. 04867450753');
        $company_cf = get_option('mm_preventivi_company_cf', 'C.F. MNCMSM79E01119H');
        $company_logo = get_option('mm_preventivi_logo', '');

        // Crea PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Informazioni documento
        $pdf->SetCreator('Massimo Manca Preventivi Plugin');
        $pdf->SetAuthor($company_name);
        $pdf->SetTitle('Preventivo ' . $preventivo['numero_preventivo']);
        $pdf->SetSubject('Preventivo Evento');

        // Rimuovi header e footer default
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Margini
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 25);

        // Aggiungi pagina
        $pdf->AddPage();

        // Font
        $pdf->SetFont('helvetica', '', 10);

        // Header con logo
        $logo_html = '';
        if (!empty($company_logo) && filter_var($company_logo, FILTER_VALIDATE_URL)) {
            $logo_html = '<img src="' . esc_url($company_logo) . '" style="height: 50px; margin-bottom: 10px;">';
        }

        $html_header = '
        <table style="width: 100%; margin-bottom: 15px;">
            <tr>
                <td style="width: 50%; text-align: left; vertical-align: top;">
                    ' . $logo_html . '
                    <h1 style="color: #e91e63; font-size: 26px; margin: 0; font-weight: bold;">PREVENTIVO</h1>
                    <p style="color: #666; font-size: 10px; margin: 5px 0 0 0; text-transform: uppercase; letter-spacing: 1px;">DJ • Animazione • Scenografie • Photo Booth</p>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <p style="font-size: 11px; margin: 0; font-weight: bold; color: #333;">' . esc_html($company_name) . '</p>
                    <p style="font-size: 9px; margin: 3px 0; color: #666;">' . esc_html($company_address) . '</p>
                    <p style="font-size: 9px; margin: 3px 0; color: #666;">Tel. ' . esc_html($company_phone) . '</p>
                    <p style="font-size: 9px; margin: 3px 0; color: #666;">' . esc_html($company_email) . '</p>
                </td>
            </tr>
        </table>
        <hr style="border: none; border-top: 3px solid #e91e63; margin: 10px 0 20px 0;">
        ';

        $pdf->writeHTML($html_header, true, false, true, false, '');

        // Dati cliente e preventivo
        $html_info = '
        <table style="width: 100%; font-size: 10px; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%; padding: 8px; background-color: #f8f8f8; border-radius: 5px;">
                    <p style="margin: 0;"><strong style="color: #e91e63;">Cliente/Sposi:</strong></p>
                    <p style="margin: 3px 0 0 0; font-size: 12px; font-weight: bold;">' . esc_html($preventivo['sposi']) . '</p>
                </td>
                <td style="width: 50%; padding: 8px; background-color: #f8f8f8; border-radius: 5px; margin-left: 10px;">
                    <p style="margin: 0;"><strong style="color: #e91e63;">N. Preventivo:</strong></p>
                    <p style="margin: 3px 0 0 0; font-size: 12px; font-weight: bold;">' . esc_html($preventivo['numero_preventivo']) . '</p>
                </td>
            </tr>
        </table>
        <table style="width: 100%; font-size: 10px; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%;"><strong>Email:</strong> ' . esc_html($preventivo['email']) . '</td>
                <td style="width: 50%;"><strong>Data Preventivo:</strong> ' . date('d/m/Y', strtotime($preventivo['data_preventivo'])) . '</td>
            </tr>
            <tr>
                <td><strong>Telefono:</strong> ' . esc_html($preventivo['telefono']) . '</td>
                <td><strong>Data Evento:</strong> ' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '</td>
            </tr>
            <tr>
                <td><strong>Tipo Evento:</strong> ' . esc_html($preventivo['tipo_evento']) . '</td>
                <td><strong>Location:</strong> ' . esc_html($preventivo['location']) . '</td>
            </tr>
        </table>
        ';

        $pdf->writeHTML($html_info, true, false, true, false, '');

        // Cerimonia
        if (!empty($preventivo['cerimonia'])) {
            $cerimonia_list = is_array($preventivo['cerimonia']) ? implode(', ', $preventivo['cerimonia']) : $preventivo['cerimonia'];
            $html_cerimonia = '
            <p style="font-size: 10px; margin-bottom: 15px;"><strong>Cerimonia:</strong> ' . esc_html($cerimonia_list) . '</p>
            ';
            $pdf->writeHTML($html_cerimonia, true, false, true, false, '');
        }

        // Servizi
        $html_servizi = '
        <h3 style="color: #e91e63; font-size: 13px; margin: 20px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #f8bbd0;">Servizi Richiesti</h3>
        <table style="width: 100%; font-size: 10px; border-collapse: collapse; margin-bottom: 15px;">
            <thead>
                <tr style="background-color: #e91e63; color: white;">
                    <th style="padding: 8px; text-align: left; border: 1px solid #e91e63;">Servizio</th>
                    <th style="padding: 8px; text-align: right; border: 1px solid #e91e63; width: 80px;">Prezzo</th>
                    <th style="padding: 8px; text-align: right; border: 1px solid #e91e63; width: 80px;">Sconto</th>
                    <th style="padding: 8px; text-align: right; border: 1px solid #e91e63; width: 80px;">Totale</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($preventivo['servizi'] as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            $sconto = isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0;
            $totale_servizio = $prezzo - $sconto;

            $html_servizi .= '
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background-color: #fafafa;">' . esc_html($servizio['nome_servizio']) . '</td>
                    <td style="padding: 8px; text-align: right; border: 1px solid #ddd; background-color: #fafafa;">€ ' . number_format($prezzo, 2, ',', '.') . '</td>';

            if ($sconto > 0) {
                $html_servizi .= '<td style="padding: 8px; text-align: right; border: 1px solid #ddd; background-color: #fafafa; color: #4caf50; font-weight: bold;">-€ ' . number_format($sconto, 2, ',', '.') . '</td>';
            } else {
                $html_servizi .= '<td style="padding: 8px; text-align: right; border: 1px solid #ddd; background-color: #fafafa;">-</td>';
            }

            $html_servizi .= '<td style="padding: 8px; text-align: right; border: 1px solid #ddd; background-color: #fafafa; font-weight: bold;">€ ' . number_format($totale_servizio, 2, ',', '.') . '</td>
                </tr>';
        }

        $html_servizi .= '
            </tbody>
        </table>';

        $pdf->writeHTML($html_servizi, true, false, true, false, '');

        // Servizi extra
        if (!empty($preventivo['servizi_extra'])) {
            $extra_list = is_array($preventivo['servizi_extra']) ? implode(', ', $preventivo['servizi_extra']) : $preventivo['servizi_extra'];
            $html_extra = '
            <h3 style="color: #e91e63; font-size: 13px; margin: 15px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #f8bbd0;">Servizi Aggiuntivi</h3>
            <p style="font-size: 10px; padding: 10px; background-color: #fafafa; border-left: 4px solid #e91e63;">' . esc_html($extra_list) . '</p>
            ';
            $pdf->writeHTML($html_extra, true, false, true, false, '');
        }

        // Note
        if (!empty($preventivo['note'])) {
            $html_note = '
            <h3 style="color: #e91e63; font-size: 13px; margin: 15px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #f8bbd0;">Note</h3>
            <p style="font-size: 10px; padding: 10px; background-color: #fffaf0; border-left: 4px solid #ff9800; line-height: 1.6;">' . nl2br(esc_html($preventivo['note'])) . '</p>
            ';
            $pdf->writeHTML($html_note, true, false, true, false, '');
        }

        // Calcolo totali
        $totale_servizi = floatval($preventivo['totale_servizi']);
        $sconto = isset($preventivo['sconto']) ? floatval($preventivo['sconto']) : 0;
        $sconto_percentuale = isset($preventivo['sconto_percentuale']) ? floatval($preventivo['sconto_percentuale']) : 0;

        // Calcola sconto
        $importo_sconto = 0;
        if ($sconto_percentuale > 0) {
            $importo_sconto = $totale_servizi * ($sconto_percentuale / 100);
        } elseif ($sconto > 0) {
            $importo_sconto = $sconto;
        }

        $totale_dopo_sconto = $totale_servizi - $importo_sconto;

        // Calcola tasse se attive
        $applica_enpals = isset($preventivo['applica_enpals']) ? $preventivo['applica_enpals'] : true;
        $applica_iva = isset($preventivo['applica_iva']) ? $preventivo['applica_iva'] : true;

        $enpals = $applica_enpals ? ($totale_dopo_sconto * 0.33) : 0;
        $iva = $applica_iva ? ($totale_dopo_sconto * 0.22) : 0;
        $totale = $totale_dopo_sconto + $enpals + $iva;

        // Totali
        $html_totali = '
        <table style="width: 100%; margin-top: 25px; font-size: 11px; border-top: 3px solid #e91e63;">
            <tr>
                <td style="padding: 10px 0; text-align: right; width: 70%; font-weight: bold;">Totale Servizi:</td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold;">€ ' . number_format($totale_servizi, 2, ',', '.') . '</td>
            </tr>';

        // Mostra sconto se presente
        if ($importo_sconto > 0) {
            $sconto_label = 'Sconto';
            if ($sconto_percentuale > 0) {
                $sconto_label .= ' (' . number_format($sconto_percentuale, 0) . '%)';
            }
            $html_totali .= '
            <tr style="color: #4caf50;">
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">- ' . $sconto_label . ':</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">€ ' . number_format($importo_sconto, 2, ',', '.') . '</td>
            </tr>
            <tr style="border-top: 1px solid #ddd;">
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">Subtotale:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">€ ' . number_format($totale_dopo_sconto, 2, ',', '.') . '</td>
            </tr>';
        }

        // Enpals
        if ($applica_enpals) {
            $html_totali .= '
            <tr>
                <td style="padding: 8px 0; text-align: right;">Ex Enpals (33%):</td>
                <td style="padding: 8px 0; text-align: right;">€ ' . number_format($enpals, 2, ',', '.') . '</td>
            </tr>';
        }

        // IVA
        if ($applica_iva) {
            $html_totali .= '
            <tr>
                <td style="padding: 8px 0; text-align: right;">IVA (22%):</td>
                <td style="padding: 8px 0; text-align: right;">€ ' . number_format($iva, 2, ',', '.') . '</td>
            </tr>';
        }

        $html_totali .= '
            <tr style="border-top: 3px solid #e91e63; background-color: #f8bbd0;">
                <td style="padding: 12px 0; text-align: right; color: #e91e63;"><strong style="font-size: 15px;">TOTALE:</strong></td>
                <td style="padding: 12px 0; text-align: right; color: #e91e63;"><strong style="font-size: 16px;">€ ' . number_format($totale, 2, ',', '.') . '</strong></td>
            </tr>
        </table>';

        $pdf->writeHTML($html_totali, true, false, true, false, '');

        // Acconto
        if (!empty($preventivo['data_acconto']) && !empty($preventivo['importo_acconto'])) {
            $importo_acconto = floatval($preventivo['importo_acconto']);
            $restante = $totale - $importo_acconto;

            $html_acconto = '
            <table style="width: 100%; margin-top: 15px; font-size: 10px; background-color: #e8f5e9; padding: 10px; border-radius: 5px;">
                <tr>
                    <td style="padding: 5px;"><strong style="color: #2e7d32;">Acconto del ' . date('d/m/Y', strtotime($preventivo['data_acconto'])) . ':</strong></td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; color: #2e7d32;">€ ' . number_format($importo_acconto, 2, ',', '.') . '</td>
                </tr>
                <tr>
                    <td style="padding: 5px;"><strong>Restante da saldare:</strong></td>
                    <td style="padding: 5px; text-align: right; font-weight: bold;">€ ' . number_format($restante, 2, ',', '.') . '</td>
                </tr>
            </table>';
            $pdf->writeHTML($html_acconto, true, false, true, false, '');
        }

        // Footer
        $pdf->SetY(-25);
        $html_footer = '
        <hr style="border: none; border-top: 1px solid #ddd; margin: 15px 0 10px 0;">
        <p style="text-align: center; font-size: 8px; color: #999; line-height: 1.4;">
            <strong>' . esc_html($company_name) . '</strong><br>
            ' . esc_html($company_address) . '<br>
            ' . esc_html($company_piva) . ' - ' . esc_html($company_cf) . '<br>
            Tel. ' . esc_html($company_phone) . ' - Email: ' . esc_html($company_email) . '
        </p>';
        $pdf->writeHTML($html_footer, true, false, true, false, '');

        // Output PDF
        $filename = 'Preventivo_' . $preventivo['numero_preventivo'] . '.pdf';
        $pdf->Output($filename, 'D');
    }

    /**
     * Genera PDF in HTML (fallback se TCPDF non disponibile)
     */
    private static function generate_html_pdf($preventivo) {
        // Impostazioni aziendali
        $company_name = get_option('mm_preventivi_company_name', 'MONTERO MUSIC di Massimo Manca');
        $company_address = get_option('mm_preventivi_company_address', 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)');
        $company_phone = get_option('mm_preventivi_company_phone', '333-7512343');
        $company_email = get_option('mm_preventivi_company_email', 'info@massimomanca.it');
        $company_piva = get_option('mm_preventivi_company_piva', 'P.I. 04867450753');
        $company_cf = get_option('mm_preventivi_company_cf', 'C.F. MNCMSM79E01119H');
        $company_logo = get_option('mm_preventivi_logo', '');

        // Header per download HTML
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="Preventivo_' . $preventivo['numero_preventivo'] . '.html"');

        echo '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preventivo ' . esc_html($preventivo['numero_preventivo']) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { border-bottom: 4px solid #e91e63; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { max-height: 60px; margin-bottom: 15px; }
        h1 { color: #e91e63; font-size: 32px; margin-bottom: 5px; }
        .subtitle { color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: 2px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
        .info-box { background: #f8f8f8; padding: 15px; border-radius: 5px; }
        .info-box strong { color: #e91e63; display: block; margin-bottom: 5px; }
        h2 { color: #e91e63; font-size: 16px; margin: 25px 0 15px; padding-bottom: 10px; border-bottom: 2px solid #f8bbd0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #e91e63; color: white; font-weight: bold; }
        td:last-child { text-align: right; }
        .totals { background: #fafafa; padding: 20px; border-radius: 5px; margin-top: 30px; }
        .totals table { margin: 0; }
        .totals tr { border: none; }
        .totals td { border: none; padding: 8px 0; font-size: 14px; }
        .total-row { border-top: 3px solid #e91e63 !important; background: #f8bbd0; font-size: 18px !important; font-weight: bold; color: #e91e63; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 11px; color: #999; }
        .acconto { background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin-top: 20px; }
        .note { background: #fffaf0; padding: 15px; border-left: 4px solid #ff9800; margin: 15px 0; line-height: 1.6; }
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">';

        if (!empty($company_logo)) {
            echo '<img src="' . esc_url($company_logo) . '" alt="Logo" class="logo">';
        }

        echo '<h1>PREVENTIVO</h1>
            <p class="subtitle">DJ • Animazione • Scenografie • Photo Booth</p>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <strong>Cliente/Sposi:</strong>
                <span style="font-size: 16px; font-weight: bold;">' . esc_html($preventivo['sposi']) . '</span>
            </div>
            <div class="info-box">
                <strong>N. Preventivo:</strong>
                <span style="font-size: 16px; font-weight: bold;">' . esc_html($preventivo['numero_preventivo']) . '</span>
            </div>
        </div>

        <table style="border: none; font-size: 13px;">
            <tr><td style="border: none;"><strong>Email:</strong> ' . esc_html($preventivo['email']) . '</td><td style="border: none;"><strong>Data Preventivo:</strong> ' . date('d/m/Y', strtotime($preventivo['data_preventivo'])) . '</td></tr>
            <tr><td style="border: none;"><strong>Telefono:</strong> ' . esc_html($preventivo['telefono']) . '</td><td style="border: none;"><strong>Data Evento:</strong> ' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '</td></tr>
            <tr><td style="border: none;"><strong>Tipo Evento:</strong> ' . esc_html($preventivo['tipo_evento']) . '</td><td style="border: none;"><strong>Location:</strong> ' . esc_html($preventivo['location']) . '</td></tr>
        </table>';

        if (!empty($preventivo['cerimonia'])) {
            $cerimonia = is_array($preventivo['cerimonia']) ? implode(', ', $preventivo['cerimonia']) : $preventivo['cerimonia'];
            echo '<p style="margin: 15px 0;"><strong>Cerimonia:</strong> ' . esc_html($cerimonia) . '</p>';
        }

        echo '<h2>Servizi Richiesti</h2>
        <table>
            <thead>
                <tr>
                    <th>Servizio</th>
                    <th style="width: 100px;">Prezzo</th>
                    <th style="width: 100px;">Sconto</th>
                    <th style="width: 100px;">Totale</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($preventivo['servizi'] as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            $sconto = isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0;
            $totale_servizio = $prezzo - $sconto;

            echo '<tr>
                <td>' . esc_html($servizio['nome_servizio']) . '</td>
                <td>€ ' . number_format($prezzo, 2, ',', '.') . '</td>
                <td style="color: ' . ($sconto > 0 ? '#4caf50' : '#999') . '; font-weight: ' . ($sconto > 0 ? 'bold' : 'normal') . ';">' . ($sconto > 0 ? '-€ ' . number_format($sconto, 2, ',', '.') : '-') . '</td>
                <td style="font-weight: bold;">€ ' . number_format($totale_servizio, 2, ',', '.') . '</td>
            </tr>';
        }

        echo '</tbody></table>';

        if (!empty($preventivo['servizi_extra'])) {
            $extra = is_array($preventivo['servizi_extra']) ? implode(', ', $preventivo['servizi_extra']) : $preventivo['servizi_extra'];
            echo '<h2>Servizi Aggiuntivi</h2><p style="background: #fafafa; padding: 15px; border-left: 4px solid #e91e63;">' . esc_html($extra) . '</p>';
        }

        if (!empty($preventivo['note'])) {
            echo '<h2>Note</h2><div class="note">' . nl2br(esc_html($preventivo['note'])) . '</div>';
        }

        // Calcoli
        $totale_servizi = floatval($preventivo['totale_servizi']);
        $sconto = isset($preventivo['sconto']) ? floatval($preventivo['sconto']) : 0;
        $sconto_percentuale = isset($preventivo['sconto_percentuale']) ? floatval($preventivo['sconto_percentuale']) : 0;

        $importo_sconto = 0;
        if ($sconto_percentuale > 0) {
            $importo_sconto = $totale_servizi * ($sconto_percentuale / 100);
        } elseif ($sconto > 0) {
            $importo_sconto = $sconto;
        }

        $totale_dopo_sconto = $totale_servizi - $importo_sconto;

        $applica_enpals = isset($preventivo['applica_enpals']) ? $preventivo['applica_enpals'] : true;
        $applica_iva = isset($preventivo['applica_iva']) ? $preventivo['applica_iva'] : true;

        $enpals = $applica_enpals ? ($totale_dopo_sconto * 0.33) : 0;
        $iva = $applica_iva ? ($totale_dopo_sconto * 0.22) : 0;
        $totale = $totale_dopo_sconto + $enpals + $iva;

        echo '<div class="totals">
            <table>
                <tr><td><strong>Totale Servizi:</strong></td><td><strong>€ ' . number_format($totale_servizi, 2, ',', '.') . '</strong></td></tr>';

        if ($importo_sconto > 0) {
            $sconto_label = 'Sconto';
            if ($sconto_percentuale > 0) {
                $sconto_label .= ' (' . number_format($sconto_percentuale, 0) . '%)';
            }
            echo '<tr style="color: #4caf50;"><td><strong>- ' . $sconto_label . ':</strong></td><td><strong>€ ' . number_format($importo_sconto, 2, ',', '.') . '</strong></td></tr>';
            echo '<tr style="border-top: 2px solid #ddd;"><td><strong>Subtotale:</strong></td><td><strong>€ ' . number_format($totale_dopo_sconto, 2, ',', '.') . '</strong></td></tr>';
        }

        if ($applica_enpals) {
            echo '<tr><td>Ex Enpals (33%):</td><td>€ ' . number_format($enpals, 2, ',', '.') . '</td></tr>';
        }

        if ($applica_iva) {
            echo '<tr><td>IVA (22%):</td><td>€ ' . number_format($iva, 2, ',', '.') . '</td></tr>';
        }

        echo '<tr class="total-row"><td>TOTALE:</td><td>€ ' . number_format($totale, 2, ',', '.') . '</td></tr>
            </table>
        </div>';

        if (!empty($preventivo['data_acconto']) && !empty($preventivo['importo_acconto'])) {
            $importo_acconto = floatval($preventivo['importo_acconto']);
            $restante = $totale - $importo_acconto;
            echo '<div class="acconto">
                <p><strong>Acconto del ' . date('d/m/Y', strtotime($preventivo['data_acconto'])) . ':</strong> € ' . number_format($importo_acconto, 2, ',', '.') . '</p>
                <p style="margin-top: 10px;"><strong>Restante da saldare:</strong> € ' . number_format($restante, 2, ',', '.') . '</p>
            </div>';
        }

        echo '<div class="footer">
            <strong>' . esc_html($company_name) . '</strong><br>
            ' . esc_html($company_address) . '<br>
            ' . esc_html($company_piva) . ' - ' . esc_html($company_cf) . '<br>
            Tel. ' . esc_html($company_phone) . ' - Email: ' . esc_html($company_email) . '
        </div>
    </div>

    <script>
        // Auto-print on load (opzionale)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>';
        exit;
    }
}
