<?php

// v1.0 createit-nvo-reservation-calendar/includes/ajax-generate-pdf.php
defined('ABSPATH') || exit;

function generate_reservation_pdf() {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        wp_die(__('Invalid reservation ID.', 'createit'));
    }

    $reservation_id = intval($_GET['id']);
    $meta = get_post_meta($reservation_id, '_nvo_reservation_meta', true);

    if (!$meta) {
        wp_die(__('Reservation not found.', 'createit'));
    }

    // Fetch rules and details
    $noteikumi = get_option('createit_nvo_noteikumi', 'Nav pieejami noteikumi.');
    $submission_date = get_post_time('U', true, $reservation_id); // Get the timestamp

	// Latvian month names
	$latvian_months = [
		1 => 'Janvārī', 2 => 'Februārī', 3 => 'Martā',
		4 => 'Aprīlī', 5 => 'Maijā', 6 => 'Jūnijā',
		7 => 'Jūlijā', 8 => 'Augustā', 9 => 'Septembrī',
		10 => 'Oktobrī', 11 => 'Novembrī', 12 => 'Decembrī'
	];

	// Extract the date components
	$year = date('Y', $submission_date);
	$day = date('j', $submission_date);
	$month = date('n', $submission_date); // Numeric month (1-12)

	// Format the date manually
	$formatted_date = sprintf('%s. gada %s. %s', $year, $day, $latvian_months[$month]);

    $data = [
        'address' => $meta['location'] ?? 'Nav norādīts',
        'room_number' => get_the_title($meta['room_id'] ?? 0) ?: 'Nav norādīts',
        'organizer_name' => $meta['organizer_name'] ?? 'Nav norādīts',
        'organizer_address' => $meta['organizer_address'] ?? 'Nav norādīts',
        'organizer_reg_number' => $meta['organizer_reg_number'] ?? 'Nav norādīts',
        'event_title' => get_the_title($reservation_id),
        'event_frequency' => $meta['event_frequency'] ?? 'Nav norādīts',
        'event_type' => $meta['event_type'] ?? 'Nav norādīts',
        'dates' => $meta['dates'] ?? [],
        'participant_count' => $meta['participant_count'] ?? 'Nav norādīts',
        'disability_friendly' => $meta['disability_friendly'] ? 'Jā' : 'Nē',
        'event_description' => $meta['event_description'] ?? 'Nav norādīts',
        'equipment_requested' => implode(', ', array_map('get_the_title', $meta['equipment_requested'] ?? [])) ?: 'Nav norādīts',
        'coffee_breaks' => $meta['coffee_breaks'] ?? 'Nav norādīts',
        'contact_name' => $meta['contact_name'] ?? 'Nav norādīts',
        'contact_phone' => $meta['contact_phone'] ?? 'Nav norādīts',
        'contact_email' => $meta['contact_email'] ?? 'Nav norādīts',
        'signatory_name' => $meta['signatory_name'] ?? '',
        'signatory_position' => $meta['signatory_position'] ?? '',
    ];

    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('NVO Reservation System');
    $pdf->SetTitle('Rezervācijas Informācija');
    $pdf->SetSubject('Rezervācijas informācija');
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->setFontSubsetting(true);

    // Use DejaVu Sans for full Unicode support
    $pdf->SetFont('dejavusans', '', 12);

    // Add a page
    $pdf->AddPage();

    // Title and Date
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, 'PIETEIKUMS', 0, 1, 'C');
    $pdf->SetFont('dejavusans', '', 14);
    $pdf->Cell(0, 10, 'Telpu lietošanai', 0, 1, 'C');
    $pdf->Cell(0, 10, $formatted_date, 0, 1, 'C');

    // Table with reservation details
    $pdf->SetFont('dejavusans', '', 12);
    $html = '<table border="1" cellpadding="5">
        <tr><td><strong>Adrese:</strong></td><td>' . esc_html($data['address']) . '</td></tr>
        <tr><td><strong>Telpas Nr.:</strong></td><td>' . esc_html($data['room_number']) . '</td></tr>
        <tr><td><strong>Pieteicēja nosaukums un juridiskā adrese:</strong></td><td>' . esc_html($data['organizer_name']) . ', ' . esc_html($data['organizer_address']) . '</td></tr>
        <tr><td><strong>Pieteicēja reģistrācijas numurs:</strong></td><td>' . esc_html($data['organizer_reg_number']) . '</td></tr>
        <tr><td><strong>Pasākuma nosaukums:</strong></td><td>' . esc_html($data['event_title']) . '</td></tr>
        <tr><td><strong>Pasākuma biežums:</strong></td><td>' . esc_html($data['event_frequency']) . '</td></tr>
        <tr><td><strong>Pasākuma veids:</strong></td><td>' . esc_html($data['event_type']) . '</td></tr>
        <tr><td><strong>Pasākuma norises datums/dati un laiks:</strong></td><td>';
    foreach ($data['dates'] as $date) {
        $html .= esc_html($date['date'] . ' no ' . $date['from'] . ' līdz ' . $date['to']) . '<br>';
    }
    $html .= '<small>¹ Ieskaitot arī sagatavošanas laiku pirms un pēc Pasākuma.</small></td></tr>
        <tr><td><strong>Plānotais dalībnieku skaits:</strong></td><td>' . esc_html($data['participant_count']) . '</td></tr>
        <tr><td><strong>Pasākuma mērķis un norises apraksts:</strong></td><td>' . esc_html($data['event_description']) . '</td></tr>
        <tr><td><strong>Nepieciešamais aprīkojums:</strong></td><td>' . esc_html($data['equipment_requested']) . '</td></tr>
        <tr><td><strong>Paredzētas kafijas pauzes:</strong></td><td>' . esc_html($data['coffee_breaks']) . '</td></tr>
        <tr><td><strong>Pasākuma kontaktpersona:</strong></td><td>' . esc_html($data['contact_name']) . ', ' . esc_html($data['contact_phone']) . ', ' . esc_html($data['contact_email']) . '</td></tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Add Noteikumi
    $pdf->Ln(10);
    $pdf->SetFont('dejavusans', '', 12);
    $pdf->writeHTML('<p>Noteikumi:</p>' . wpautop($noteikumi), true, false, true, false, '');

    // Footer with page number and electronic signature note
    $pdf->SetY(-15);
    $pdf->SetFont('dejavusans', 'I', 10);
    $pdf->Cell(0, 10, 'Lapa ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'Šis dokuments ir parakstīts ar drošu elektronisko parakstu un satur laika zīmogu.', 0, 0, 'C');

    // Output as download
    $pdf->Output('rezervacija_' . $reservation_id . '.pdf', 'D');
    exit;
}
