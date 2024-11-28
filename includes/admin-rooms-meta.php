<?php
// Version 2.4 - Added Apkaime Field for Room Filtering
defined('ABSPATH') || exit;

// Add Meta Boxes for Rooms
add_action('add_meta_boxes', 'createit_nvo_add_rooms_meta_boxes');
function createit_nvo_add_rooms_meta_boxes() {
    add_meta_box(
        'nvo_room_details',
        'Telpas detaļas',
        'createit_nvo_room_meta_box_callback',
        'nvo_rooms',
        'normal',
        'high'
    );
}

// Meta Box Callback Function
function createit_nvo_room_meta_box_callback($post) {
    // Fetch existing data if editing
    $room_data = get_post_meta($post->ID, '_nvo_room_meta', true);

    // Set default values if no data
    $room_data = wp_parse_args((array) $room_data, [
        'location' => '',
        'apkaime' => '',
        'color' => '#ffffff',
        'disability_friendly' => false,
        'equipment' => [],
        'availability_schedule' => [],
    ]);

    $equipment_options = get_posts([
        'post_type' => 'nvo_equipment',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    wp_nonce_field('nvo_room_meta_nonce_action', 'nvo_room_meta_nonce');

    // Render Meta Box
    ?>
    <table class="form-table">
        <tr>
            <th><label for="room-location">Atrašanās vieta (Adrese)</label></th>
            <td><input type="text" name="room_location" id="room-location" value="<?php echo esc_attr($room_data['location']); ?>" class="regular-text"></td>
        </tr>

        <tr>
            <th><label for="room-apkaime">Apkaime</label></th>
            <td>
                <select name="room_apkaime" id="room-apkaime">
                    <option value="">Izvēlēties apkaimi</option>
                    <?php
                    $apkaimes = [
                        'Āgenskalns', 'Atgāzene', 'Avoti', 'Beberbeķi', 'Berģi', 'Bieriņi', 'Bišumuiža', 'Bolderāja', 'Brasa', 
                        'Brekši', 'Bukulti', 'Buļļi', 'Centrs', 'Čiekurkalns', 'Dārzciems', 'Dārziņi', 'Daugavgrīva', 'Dreiliņi', 
                        'Dzirciems', 'Grīziņkalns', 'Iļģuciems', 'Imanta', 'Jaunciems', 'Jugla', 'Katlakalns', 'Ķengarags', 
                        'Ķīpsala', 'Kleisti', 'Kundziņsala', 'Latgale', 'Mangaļsala', 'Mežaparks', 'Mežciems', 'Mīlgrāvis', 
                        'Mūkupurvs', 'Pētersala-Andrejsala', 'Pļavnieki', 'Pleskodāle', 'Purvciems', 'Rumbula', 'Salas', 
                        'Šampēteris', 'Sarkandaugava', 'Skanste', 'Šķirotava', 'Spilve', 'Suži', 'Teika', 'Torņakalns', 
                        'Trīsciems', 'Vecāķi', 'Vecdaugava', 'Vecmīlgrāvis', 'Vecpilsēta', 'Voleri', 'Zasulauks', 
                        'Ziepniekkalns', 'Zolitūde'
                    ];

                    foreach ($apkaimes as $apkaime) {
                        $selected = ($room_data['apkaime'] === $apkaime) ? 'selected' : '';
                        echo "<option value='" . esc_attr($apkaime) . "' $selected>" . esc_html($apkaime) . "</option>";
                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr>
            <th><label for="room-color">Telpas krāsa</label></th>
            <td><input type="color" name="room_color" id="room-color" value="<?php echo esc_attr($room_data['color']); ?>"></td>
        </tr>

        <tr>
            <th><label for="room-disability-friendly">Invalīdiem draudzīga</label></th>
            <td><input type="checkbox" name="room_disability_friendly" id="room-disability-friendly" <?php checked($room_data['disability_friendly'], true); ?>></td>
        </tr>

        <tr>
            <th><label for="room-equipment">Aprīkojums</label></th>
            <td>
                <?php if (!empty($equipment_options)): ?>
                    <?php foreach ($equipment_options as $equipment): ?>
                        <label>
                            <input type="checkbox" name="room_equipment[]" value="<?php echo esc_attr($equipment->ID); ?>" <?php checked(in_array($equipment->ID, $room_data['equipment']), true); ?>>
                            <?php echo esc_html($equipment->post_title); ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nav pieejamu aprīkojumu.</p>
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th><label>Darba laiki</label></th>
            <td>
                <?php
                $days = [
                    'pirmdiena' => 'Pirmdiena',
                    'otrdiena' => 'Otrdiena',
                    'tresdiena' => 'Trešdiena',
                    'ceturtdiena' => 'Ceturtdiena',
                    'piektdiena' => 'Piektdiena',
                    'sestdiena' => 'Sestdiena',
                    'svetdiena' => 'Svētdiena',
                ];
                foreach ($days as $day_key => $day_label):
                    $day_active = isset($room_data['availability_schedule'][$day_key]['active']) ? $room_data['availability_schedule'][$day_key]['active'] : false;
                    $from_time = isset($room_data['availability_schedule'][$day_key]['from']) ? $room_data['availability_schedule'][$day_key]['from'] : '';
                    $to_time = isset($room_data['availability_schedule'][$day_key]['to']) ? $room_data['availability_schedule'][$day_key]['to'] : '';
                ?>
                    <div class="day-schedule">
                        <label>
                            <input type="checkbox" name="room_availability[<?php echo esc_attr($day_key); ?>][active]" class="day-active-checkbox" <?php checked($day_active, true); ?>>
                            <strong><?php echo esc_html($day_label); ?></strong>
                        </label><br>
                        <div class="time-interval" style="<?php echo $day_active ? '' : 'display:none;'; ?>">
                            No plkst.: 
                            <input type="text" class="flatpickr-time" name="room_availability[<?php echo esc_attr($day_key); ?>][from]" value="<?php echo esc_attr($from_time); ?>">
                            Līdz plkst.: 
                            <input type="text" class="flatpickr-time" name="room_availability[<?php echo esc_attr($day_key); ?>][to]" value="<?php echo esc_attr($to_time); ?>">
                        </div>
                    </div>
                    <br>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>
    <?php
}

// Save Meta Box Data
add_action('save_post', 'createit_nvo_save_room_meta_data');
function createit_nvo_save_room_meta_data($post_id) {
    if (!isset($_POST['nvo_room_meta_nonce']) || !wp_verify_nonce($_POST['nvo_room_meta_nonce'], 'nvo_room_meta_nonce_action')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $room_data = [
        'location' => sanitize_text_field($_POST['room_location']),
        'apkaime' => sanitize_text_field($_POST['room_apkaime']),
        'color' => sanitize_hex_color($_POST['room_color']),
        'disability_friendly' => isset($_POST['room_disability_friendly']),
        'equipment' => array_map('intval', $_POST['room_equipment'] ?? []),
        'availability_schedule' => [],
    ];

    if (isset($_POST['room_availability'])) {
        foreach ($_POST['room_availability'] as $day_key => $day_data) {
            if (!empty($day_data['active'])) { // Only save active days
                $room_data['availability_schedule'][$day_key] = [
                    'active' => true,
                    'from' => sanitize_text_field($day_data['from']),
                    'to' => sanitize_text_field($day_data['to']),
                ];
            }
        }
    }

    update_post_meta($post_id, '_nvo_room_meta', $room_data);
}