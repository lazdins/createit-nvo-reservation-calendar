<?php
// Version 2.5 - Full Meta Box for "Rezervācijas" with All Fields createit-nvo-reservation-calendar/includes/admin-reservations-meta.php

defined('ABSPATH') || exit;

// Add Meta Boxes for Reservations
add_action('add_meta_boxes', 'createit_nvo_add_reservation_meta_boxes');
function createit_nvo_add_reservation_meta_boxes() {
    add_meta_box(
        'nvo_reservation_details',
        'Rezervācijas detaļas',
        'createit_nvo_reservation_meta_box_callback',
        'nvo_reservations',
        'normal',
        'high'
    );
}

// Meta Box Callback Function
function createit_nvo_reservation_meta_box_callback($post) {
    // Fetch existing data if editing
    $reservation_data = get_post_meta($post->ID, '_nvo_reservation_meta', true);

    // Set default values if no data
    $reservation_data = wp_parse_args((array)$reservation_data, [
        'room_id' => '',
        'reservation_type' => 'single', // Default to single reservation
        'dates' => [],
        'recurring' => [
            'start_date' => '',
            'end_date' => '',
            'weekdays' => [],
            'time_intervals' => []
        ],
        'organizer_name' => '',
        'organizer_address' => '',
        'organizer_reg_number' => '',
        'participant_count' => '',
        'event_frequency' => 'single',
        'event_type' => 'meeting',
        'disability_friendly' => false,
        'event_description' => '',
        'equipment_requested' => [],
        'coffee_breaks' => '',
        'contact_name' => '',
        'contact_phone' => '',
        'contact_email' => '',
        'signatory_name' => '',
        'signatory_position' => '',
        'status' => 'pending', // Default status
    ]);

    // Fetch available rooms
    $rooms = get_posts([
        'post_type' => 'nvo_rooms',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);
	
	// Fetch rules
    $rules = get_option('createit_nvo_noteikumi', 'Nav noteikumu.');

    wp_nonce_field('nvo_reservation_meta_nonce_action', 'nvo_reservation_meta_nonce');
    ?>
    <table class="form-table">
        <!-- Reservation Type -->
        <tr>
            <th><label for="reservation-type">Rezervācijas veids</label></th>
            <td>
                <select name="reservation_type" id="reservation-type">
                    <option value="single" <?php selected($reservation_data['reservation_type'], 'single'); ?>>Vienreizēja rezervācija</option>
                    <option value="recurring" <?php selected($reservation_data['reservation_type'], 'recurring'); ?>>Atkārtota rezervācija</option>
                </select>
            </td>
        </tr>

        <!-- Room Selection -->
        <tr>
            <th><label for="room-id">Telpa</label></th>
            <td>
                <select name="room_id" id="room-id">
                    <option value="">Izvēlieties telpu</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo esc_attr($room->ID); ?>" <?php selected($reservation_data['room_id'], $room->ID); ?>>
                            <?php echo esc_html($room->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

		        <!-- Equipment Requested -->
        <tr>
            <th><label>Nepieciešamais aprīkojums</label></th>
            <td>
                <div id="equipment-wrapper">
                    <?php if (!empty($reservation_data['equipment_requested'])): ?>
                        <?php foreach ($reservation_data['equipment_requested'] as $equipment_id): ?>
                            <label>
                                <input type="checkbox" name="equipment_requested[]" value="<?php echo esc_attr($equipment_id); ?>" checked>
                                <?php echo esc_html(get_the_title($equipment_id)); ?>
                            </label><br>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Izvēlieties telpu, lai ielādētu pieejamo aprīkojumu.</p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
		
        <!-- Single Reservation Dates -->
        <tr class="single-reservation-fields">
            <th><label>Datumi</label></th>
            <td>
                <div id="reservation-dates-wrapper">
                    <?php foreach ($reservation_data['dates'] as $date): ?>
                        <div class="date-entry">
                            <input type="date" name="dates[date][]" value="<?php echo esc_attr($date['date']); ?>" />
                            <input type="text" class="flatpickr-time" name="dates[from][]" placeholder="No plkst." value="<?php echo esc_attr($date['from']); ?>" />
                            <input type="text" class="flatpickr-time" name="dates[to][]" placeholder="Līdz plkst." value="<?php echo esc_attr($date['to']); ?>" />
                            <button type="button" class="button remove-date">Noņemt</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button add-date">Pievienot datumu</button>
            </td>
        </tr>

        <!-- Recurring Reservation Fields -->
        <tr class="recurring-reservation-fields">
            <th><label>Atkārtotas rezervācijas</label></th>
            <td>
                <label for="recurring-start-date">Sākuma datums:</label>
                <input type="date" name="recurring[start_date]" id="recurring-start-date" value="<?php echo esc_attr($reservation_data['recurring']['start_date']); ?>" />
                <label for="recurring-end-date">Beigu datums:</label>
                <input type="date" name="recurring[end_date]" id="recurring-end-date" value="<?php echo esc_attr($reservation_data['recurring']['end_date']); ?>" />

                <div>
                    <p>Izvēlieties nedēļas dienas un laika intervālus:</p>
                    <?php
                    $weekdays = [
                        'monday' => 'Pirmdiena',
                        'tuesday' => 'Otrdiena',
                        'wednesday' => 'Trešdiena',
                        'thursday' => 'Ceturtdiena',
                        'friday' => 'Piektdiena',
                        'saturday' => 'Sestdiena',
                        'sunday' => 'Svētdiena',
                    ];
                    foreach ($weekdays as $key => $label): ?>
                        <div class="recurring-time-entry">
                            <label>
                                <input type="checkbox" name="recurring[weekdays][]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $reservation_data['recurring']['weekdays'] ?? [])); ?> />
                                <?php echo esc_html($label); ?>
                            </label>
                            <input type="text" class="flatpickr-time" name="recurring[time_intervals][<?php echo esc_attr($key); ?>][from]" placeholder="No plkst." value="<?php echo esc_attr($reservation_data['recurring']['time_intervals'][$key]['from'] ?? ''); ?>" />
                            <input type="text" class="flatpickr-time" name="recurring[time_intervals][<?php echo esc_attr($key); ?>][to]" placeholder="Līdz plkst." value="<?php echo esc_attr($reservation_data['recurring']['time_intervals'][$key]['to'] ?? ''); ?>" />
                        </div>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
		
		<!-- Availability Validation -->
        <tr>
            <th></th>
            <td>
                <button type="button" id="validate-availability" class="button button-primary"><?php esc_html_e('Pārbaudīt pieejamību', 'nvo'); ?></button>
                <div id="availability-results"></div>
            </td>
        </tr>

        <!-- Existing Fields (Organizer, etc.) -->
        <!-- Keep all existing fields below unchanged -->
		<!-- Organizer Information -->
        <tr>
            <th><label for="organizer-name">Pieteicēja nosaukums</label></th>
            <td><input type="text" name="organizer_name" id="organizer-name" value="<?php echo esc_attr($reservation_data['organizer_name']); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="organizer-address">Pieteicēja juridiskā adrese</label></th>
            <td><input type="text" name="organizer_address" id="organizer-address" value="<?php echo esc_attr($reservation_data['organizer_address']); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="organizer-reg-number">Pieteicēja reģistrācijas numurs</label></th>
            <td><input type="text" name="organizer_reg_number" id="organizer-reg-number" value="<?php echo esc_attr($reservation_data['organizer_reg_number']); ?>" class="regular-text"></td>
        </tr>

                <!-- Event Type -->
        <tr>
            <th><label for="event-type">Pasākuma veids</label></th>
            <td>
                <select name="event_type" id="event-type">
                    <option value="administrative" <?php selected($reservation_data['event_type'], 'administrative'); ?>>Administratīvais darbs</option>
                    <option value="meeting" <?php selected($reservation_data['event_type'], 'meeting'); ?>>Sanāksme</option>
                    <option value="informative" <?php selected($reservation_data['event_type'], 'informative'); ?>>Informatīvie un izglītojošie pasākumi</option>
                    <option value="cultural" <?php selected($reservation_data['event_type'], 'cultural'); ?>>Kultūras aktivitāšu mēģinājumi</option>
                    <option value="show" <?php selected($reservation_data['event_type'], 'show'); ?>>Izrāde, koncerts, izstāde</option>
                    <option value="charity" <?php selected($reservation_data['event_type'], 'charity'); ?>>Labdarības pasākums</option>
                    <option value="other" <?php selected($reservation_data['event_type'], 'other'); ?>>Cits (norādīt)</option>
                </select>
                <input type="text" name="event_type_other" id="event-type-other" value="<?php echo esc_attr($reservation_data['event_type_other'] ?? ''); ?>" class="regular-text" style="<?php echo $reservation_data['event_type'] === 'other' ? '' : 'display:none;'; ?>" placeholder="Norādiet pasākuma veidu">
            </td>
        </tr>

        <!-- Disability Friendly -->
        <tr>
            <th><label for="disability-friendly">Personas ar Kustību Traucējumiem</label></th>
            <td>
                <select name="disability_friendly" id="disability-friendly">
                    <option value="0" <?php selected($reservation_data['disability_friendly'], false); ?>>Nē</option>
                    <option value="1" <?php selected($reservation_data['disability_friendly'], true); ?>>Jā</option>
                </select>
            </td>
        </tr>

        <!-- Coffee Breaks -->
        <tr>
            <th><label for="coffee-breaks">Paredzētas kafijas pauzes</label></th>
            <td><textarea name="coffee_breaks" id="coffee-breaks" rows="2" class="large-text"><?php echo esc_textarea($reservation_data['coffee_breaks']); ?></textarea></td>
        </tr>

        <!-- Contact Person -->
        <tr>
            <th><label for="contact-name">Pasākuma kontaktpersonas Vārds, Uzvārds</label></th>
            <td><input type="text" name="contact_name" id="contact-name" value="<?php echo esc_attr($reservation_data['contact_name']); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="contact-phone">Pasākuma kontaktpersonas Tālrunis</label></th>
            <td><input type="text" name="contact_phone" id="contact-phone" value="<?php echo esc_attr($reservation_data['contact_phone']); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="contact-email">Pasākuma kontaktpersonas E-pasts</label></th>
            <td><input type="email" name="contact_email" id="contact-email" value="<?php echo esc_attr($reservation_data['contact_email']); ?>" class="regular-text"></td>
        </tr>

        <!-- Signatory Information -->
        <tr>
            <th><label for="signatory-name">Pieteicēja paraksttiesās personas Vārds, Uzvārds</label></th>
            <td><input type="text" name="signatory_name" id="signatory-name" value="<?php echo esc_attr($reservation_data['signatory_name']); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="signatory-position">Pieteicēja paraksttiesīgās personas amats</label></th>
            <td><input type="text" name="signatory_position" id="signatory-position" value="<?php echo esc_attr($reservation_data['signatory_position']); ?>" class="regular-text"></td>
        </tr>

        <!-- Status -->
        <tr>
            <th><label for="status">Statuss</label></th>
            <td>
                <select name="status" id="status">
                    <option value="pending" <?php selected($reservation_data['status'], 'pending'); ?>>Izskatīšanā</option>
                    <option value="confirmed" <?php selected($reservation_data['status'], 'confirmed'); ?>>Apstiprināts</option>
                    <option value="rejected" <?php selected($reservation_data['status'], 'rejected'); ?>>Noraidīts</option>
                    <option value="cancelled" <?php selected($reservation_data['status'], 'cancelled'); ?>>Atcelts</option>
                </select>
            </td>
        </tr>

        <tr>
            <th><label for="participant-count">Plānotais dalībnieku skaits</label></th>
            <td><input type="number" name="participant_count" id="participant-count" value="<?php echo esc_attr($reservation_data['participant_count']); ?>"></td>
        </tr>
        <tr>
            <th><label for="event-frequency">Pasākuma biežums</label></th>
            <td>
                <select name="event_frequency" id="event-frequency">
                    <option value="single" <?php selected($reservation_data['event_frequency'], 'single'); ?>>Vienreizējs pasākums</option>
                    <option value="recurring_weekly" <?php selected($reservation_data['event_frequency'], 'recurring_weekly'); ?>>Regulāras aktivitātes</option>
                    <option value="recurring_monthly" <?php selected($reservation_data['event_frequency'], 'recurring_monthly'); ?>>Pasākumu cikls</option>
                </select>
            </td>
        </tr>

        <!-- Agreement -->
        <tr>
            <th><label for="rules-agreement">Noteikumi</label></th>
            <td>
                <div style="max-height: 200px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">
                    <?php echo wpautop(wp_kses_post($rules)); ?>
                </div>
                <label>
                    <input type="checkbox" name="rules_agreement" id="rules-agreement" value="1" required>
                    Ar šo Pieteicējs apliecina, ka ir iepazinies un apņemas, rīkojot pasākumu, ievērot šādus telpu lietošanas noteikumus.
                </label>
            </td>
        </tr>
    </table>

	<style>
        .date-entry, .recurring-time-entry {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .date-entry input, .recurring-time-entry input {
            flex: 1;
        }

    </style>

    <script>
        (function($) {
            $(document).ready(function() {
                // Initialize Flatpickr with 30-minute step
                function initializeFlatpickr() {
                    $('.flatpickr-time').flatpickr({
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "H:i",
                        time_24hr: true,
                        minuteIncrement: 30
                    });
                }

                initializeFlatpickr();

                // Toggle reservation type fields
                function toggleReservationFields() {
                    const type = $('#reservation-type').val();
                    $('.single-reservation-fields').toggle(type === 'single');
                    $('.recurring-reservation-fields').toggle(type === 'recurring');
                }

                $('#reservation-type').change(toggleReservationFields);
                toggleReservationFields();

                // Add date field dynamically
                $(document).on('click', '.add-date', function() {
                    const newRow = `
                        <div class="date-entry">
                            <input type="date" name="dates[date][]" />
                            <input type="text" class="flatpickr-time" name="dates[from][]" placeholder="No plkst." />
                            <input type="text" class="flatpickr-time" name="dates[to][]" placeholder="Līdz plkst." />
                            <button type="button" class="button remove-date">Noņemt</button>
                        </div>`;
                    $('#reservation-dates-wrapper').append(newRow);
                    initializeFlatpickr();
                });
				
				
				// Remove date entry
                $(document).on('click', '.remove-date', function() {
                    $(this).closest('.date-entry').remove();
                });
				
				// Fetch equipment dynamically
                $('#room-id').change(function() {
                    let roomId = $(this).val();
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'fetch_room_equipment',
                            room_id: roomId
                        },
                        success: function(response) {
                            $('#equipment-wrapper').html(response);
                        }
                    });
                });
				
				// Validate availability
				$('#validate-availability').on('click', function() {
					const data = {
						action: 'validate_reservation_dates',
						room_id: $('#room-id').val(),
						reservation_type: $('#reservation-type').val(),
						dates: JSON.stringify($('input[name^="dates"]').serializeArray()),
						security: '<?php echo wp_create_nonce("reservation_nonce"); ?>'
					};

					$.post(ajaxurl, data, function(response) {
						$('#availability-results').html(response.data.message);
					});
				});
            });
        })(jQuery);
    </script>
    <?php
}

// Save Meta Box Data
/**
 * Save reservation meta data with validation for conflicts.
 */
add_action('save_post', 'createit_nvo_save_reservation_meta_data');
function createit_nvo_save_reservation_meta_data($post_id) {
    // Verify nonce
    if (!isset($_POST['nvo_reservation_meta_nonce']) || !wp_verify_nonce($_POST['nvo_reservation_meta_nonce'], 'nvo_reservation_meta_nonce_action')) {
        return;
    }

    // Prevent autosave interference
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Collect and sanitize input data
    $room_id = intval($_POST['room_id']);
    $reservation_type = sanitize_text_field($_POST['reservation_type']);
    $dates = [];

    // Handle single or recurring reservations
    if ($reservation_type === 'single') {
        $dates = array_map(function ($date, $from, $to) {
            return [
                'date' => sanitize_text_field($date),
                'from' => sanitize_text_field($from),
                'to' => sanitize_text_field($to),
            ];
        }, $_POST['dates']['date'], $_POST['dates']['from'], $_POST['dates']['to']);
    } elseif ($reservation_type === 'recurring') {
        require_once plugin_dir_path(__FILE__) . '/helpers.php';
        $dates = process_recurring_dates($_POST['recurring']); // Generate dates for recurring reservations
    }

    // Validate room ID
    if (!$room_id) {
        wp_die(__('Error: Room is not selected.', 'createit'));
    }

    // Validate reservation dates for conflicts
    require_once plugin_dir_path(__FILE__) . '/helpers.php';
    $conflicts = validate_reservation_conflicts($room_id, $dates);

    if (!empty($conflicts)) {
        // If conflicts exist, build and display an error message
        $conflict_messages = array_map(function ($conflict) {
            return sprintf(
                __('Date: %s, From: %s, To: %s', 'createit'),
                esc_html($conflict['date']),
                esc_html($conflict['from']),
                esc_html($conflict['to'])
            );
        }, $conflicts);

        $error_message = __('Conflicting reservations detected:', 'createit') . '<br>' . implode('<br>', $conflict_messages);
        $error_message .= '<br>' . __('Please adjust your dates or times.', 'createit');
        wp_die($error_message);
    }

    // Build the reservation data array
    $reservation_data = [
        'room_id' => $room_id,
        'reservation_type' => $reservation_type,
        'dates' => $dates,
        'organizer_name' => sanitize_text_field($_POST['organizer_name']),
        'organizer_address' => sanitize_text_field($_POST['organizer_address']),
        'organizer_reg_number' => sanitize_text_field($_POST['organizer_reg_number']),
        'participant_count' => intval($_POST['participant_count']),
        'event_frequency' => sanitize_text_field($_POST['event_frequency']),
        'event_type' => sanitize_text_field($_POST['event_type']),
        'disability_friendly' => isset($_POST['disability_friendly']),
        'event_description' => sanitize_textarea_field($_POST['event_description']),
        'equipment_requested' => array_map('sanitize_text_field', $_POST['equipment_requested'] ?? []),
        'coffee_breaks' => sanitize_textarea_field($_POST['coffee_breaks']),
        'contact_name' => sanitize_text_field($_POST['contact_name']),
        'contact_phone' => sanitize_text_field($_POST['contact_phone']),
        'contact_email' => sanitize_email($_POST['contact_email']),
        'signatory_name' => sanitize_text_field($_POST['signatory_name']),
        'signatory_position' => sanitize_text_field($_POST['signatory_position']),
        'status' => sanitize_text_field($_POST['status']),
    ];

    // Save the reservation data
    update_post_meta($post_id, '_nvo_reservation_meta', $reservation_data);
}

/**
 * Process recurring dates
 * Generate dates from recurring start, end, and intervals
 */
function process_recurring_dates($recurring) {
    $start_date = sanitize_text_field($recurring['start_date']);
    $end_date = sanitize_text_field($recurring['end_date']);
    $weekdays = array_map('sanitize_text_field', $recurring['weekdays']);
    $time_intervals = array_map(function ($from, $to) {
        return [
            'from' => sanitize_text_field($from),
            'to' => sanitize_text_field($to),
        ];
    }, $recurring['time_intervals']['from'], $recurring['time_intervals']['to']);

    $dates = [];
    $current_date = $start_date;

    while ($current_date <= $end_date) {
        $weekday = strtolower(date('l', strtotime($current_date)));
        if (in_array($weekday, $weekdays)) {
            foreach ($time_intervals as $interval) {
                $dates[] = [
                    'date' => $current_date,
                    'from' => $interval['from'],
                    'to' => $interval['to'],
                ];
            }
        }
        $current_date = date('Y-m-d', strtotime($current_date . '+1 day'));
    }

    return $dates;
}



// AJAX handler for fetching room equipment
add_action('wp_ajax_fetch_room_equipment', 'fetch_room_equipment');
function fetch_room_equipment() {
    $room_id = intval($_POST['room_id']);
    $room_meta = get_post_meta($room_id, '_nvo_room_meta', true);
    $equipment = $room_meta['equipment'] ?? [];

    if (empty($equipment)) {
        echo '<p>Šai telpai nav pieejama aprīkojuma.</p>';
    } else {
        foreach ($equipment as $equipment_id) {
            echo '<label><input type="checkbox" name="equipment_requested[]" value="' . esc_attr($equipment_id) . '">' . esc_html(get_the_title($equipment_id)) . '</label><br>';
        }
    }

    wp_die();
}