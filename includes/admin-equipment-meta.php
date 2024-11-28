<?php
// Version 1.0 - Meta Box for "nvo_equipment" CPT createit-nvo-reservation-calendar/includes/admin-equipment-meta.php
defined('ABSPATH') || exit;

// Add Meta Box for Equipment
add_action('add_meta_boxes', 'createit_nvo_add_equipment_meta_box');
function createit_nvo_add_equipment_meta_box() {
    add_meta_box(
        'nvo_equipment_details',
        'Aprīkojuma detaļas',
        'createit_nvo_equipment_meta_box_callback',
        'nvo_equipment',
        'normal',
        'high'
    );
}

// Meta Box Callback Function
function createit_nvo_equipment_meta_box_callback($post) {
    // Fetch existing data if editing
    $equipment_data = get_post_meta($post->ID, '_nvo_equipment_meta', true);

    // Set default value if no data
    $availability = isset($equipment_data['availability']) ? intval($equipment_data['availability']) : 0;

    wp_nonce_field('nvo_equipment_meta_nonce_action', 'nvo_equipment_meta_nonce');
    ?>
    <table class="form-table">
        <!-- Pieejamība (Availability) -->
        <tr>
            <th><label for="equipment-availability">Pieejamība (Daudzums)</label></th>
            <td>
                <input type="number" name="equipment_availability" id="equipment-availability" value="<?php echo esc_attr($availability); ?>" min="0" class="small-text">
                <p class="description">Norādiet pieejamo daudzumu šim aprīkojumam.</p>
            </td>
        </tr>
    </table>
    <?php
}

// Save Meta Box Data
add_action('save_post', 'createit_nvo_save_equipment_meta_data');
function createit_nvo_save_equipment_meta_data($post_id) {
    if (!isset($_POST['nvo_equipment_meta_nonce']) || !wp_verify_nonce($_POST['nvo_equipment_meta_nonce'], 'nvo_equipment_meta_nonce_action')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $availability = isset($_POST['equipment_availability']) ? intval($_POST['equipment_availability']) : 0;

    $equipment_data = [
        'availability' => $availability,
    ];

    update_post_meta($post_id, '_nvo_equipment_meta', $equipment_data);

    // Synchronize with custom database table
    global $wpdb;
    $wpdb->replace(
        "{$wpdb->prefix}nvo_equipment",
        [
            'id' => $post_id,
            'name' => get_the_title($post_id),
            'availability' => $availability,
            'updated_at' => current_time('mysql'),
        ],
        [
            '%d', '%s', '%d', '%s'
        ]
    );
}
