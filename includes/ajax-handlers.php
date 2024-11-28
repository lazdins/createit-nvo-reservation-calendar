<?php
defined('ABSPATH') || exit;

// Ensure helpers.php is loaded
require_once plugin_dir_path(__FILE__) . '/helpers.php'; 

// Existing functionality
add_action('wp_ajax_get_reservation_details', 'get_reservation_details');
function get_reservation_details() {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        wp_send_json_error(['message' => 'Invalid reservation ID.']);
    }

    $reservation_id = intval($_GET['id']);
    $meta = get_post_meta($reservation_id, '_nvo_reservation_meta', true);

    if (!$meta) {
        wp_send_json_error(['message' => 'Reservation not found.']);
    }

    $response = [
        'success' => true,
        'room_name' => isset($meta['room_id']) ? get_the_title($meta['room_id']) : null,
        'organizer_name' => $meta['organizer_name'] ?? null,
        'organizer_address' => $meta['organizer_address'] ?? null,
        'organizer_reg_number' => $meta['organizer_reg_number'] ?? null,
        'participant_count' => $meta['participant_count'] ?? null,
        'event_frequency' => $meta['event_frequency'] ?? null,
        'event_type' => $meta['event_type'] ?? null,
        'disability_friendly' => $meta['disability_friendly'] ? 'Jā' : 'Nē',
        'event_description' => $meta['event_description'] ?? null,
        'equipment_requested' => isset($meta['equipment_requested']) ? array_map('get_the_title', $meta['equipment_requested']) : [],
        'coffee_breaks' => $meta['coffee_breaks'] ?? null,
        'contact_name' => $meta['contact_name'] ?? null,
        'contact_phone' => $meta['contact_phone'] ?? null,
        'contact_email' => $meta['contact_email'] ?? null,
        'signatory_name' => $meta['signatory_name'] ?? null,
        'signatory_position' => $meta['signatory_position'] ?? null,
        'status' => $meta['status'] ?? null,
        'event_title' => get_the_title($reservation_id), // Post title
        'dates' => $meta['dates'] ?? [],
    ];

    wp_send_json_success($response);
}

// New functionality

/**
 * AJAX: Validate reservation dates
 */
add_action('wp_ajax_validate_reservation_dates', 'ajax_validate_reservation_dates');
function ajax_validate_reservation_dates() {
    check_ajax_referer('reservation_nonce', 'security');

    $room_id = intval($_POST['room_id']);
    $dates = isset($_POST['dates']) ? json_decode(stripslashes($_POST['dates']), true) : [];

    if (!$room_id || empty($dates)) {
        wp_send_json_error(['message' => 'Invalid room or dates.']);
    }

    require_once plugin_dir_path(__FILE__) . '/helpers.php';
    $conflicts = validate_reservation_conflicts($room_id, $dates);

    if (!empty($conflicts)) {
        $conflict_messages = [];
        foreach ($conflicts as $conflict) {
            $conflict_messages[] = "Datums: {$conflict['date']} No plkst.: {$conflict['from']} Līdz plkst.: {$conflict['to']} ir aizņemts ({$conflict['status']}).";
        }
        wp_send_json_error([
            'message' => 'Some dates and times are unavailable.',
            'conflicts' => $conflict_messages,
        ]);
    }

    wp_send_json_success(['message' => 'All dates are available.']);
}


/**
 * AJAX: Create temporary reservation lock
 */
add_action('wp_ajax_create_temporary_lock', 'ajax_create_temporary_lock');
function ajax_create_temporary_lock() {
    check_ajax_referer('reservation_nonce', 'security');

    $room_id = intval($_POST['room_id']);
    $dates = isset($_POST['dates']) ? json_decode(stripslashes($_POST['dates']), true) : [];

    if (!$room_id || empty($dates)) {
        wp_send_json_error(['message' => 'Invalid room or dates.']);
    }

    // Call the existing helper function
    $lock_id = create_temporary_reservation_lock($room_id, $dates);

    if ($lock_id) {
        wp_send_json_success(['lock_id' => $lock_id, 'message' => 'Temporary lock created.']);
    } else {
        wp_send_json_error(['message' => 'Failed to create temporary lock.']);
    }
}

/**
 * AJAX: Release expired locks (optional, can be handled via cron)
 */
add_action('wp_ajax_release_expired_locks', 'ajax_release_expired_locks');
function ajax_release_expired_locks() {
    check_ajax_referer('reservation_nonce', 'security');

    global $wpdb;
    $reservations_table = "{$wpdb->prefix}nvo_reservations";

    $wpdb->query("
        DELETE FROM $reservations_table
        WHERE status = 'holding' AND holding_expiry < NOW()
    ");

    wp_send_json_success(['message' => 'Expired locks released.']);
}


// Handle AJAX requests for room filtering
add_action('wp_ajax_filter_rooms', 'createit_nvo_filter_rooms');
add_action('wp_ajax_nopriv_filter_rooms', 'createit_nvo_filter_rooms');

function createit_nvo_filter_rooms() {
    $filters = isset($_GET['filters']) ? $_GET['filters'] : [];
    $args = [
        'post_type' => 'nvo_rooms',
        'posts_per_page' => 9,
        'paged' => isset($_GET['paged']) ? absint($_GET['paged']) : 1,
    ];


    if (!empty($filters['apkaime'])) {
        $args['meta_query'][] = [
            'key' => '_nvo_room_meta',
            'value' => sanitize_text_field($filters['apkaime']),
            'compare' => 'LIKE',
        ];
    }

    if (!empty($filters['capacity'])) {
        $args['meta_query'][] = [
            'key' => '_nvo_room_capacity',
            'value' => intval($filters['capacity']),
            'type' => 'NUMERIC',
            'compare' => '>=',
        ];
    }

    if (isset($filters['disability'])) {
        $args['meta_query'][] = [
            'key' => '_nvo_room_meta',
            'value' => '"disability_friendly";b:' . intval($filters['disability']) . ';',
            'compare' => 'LIKE',
        ];
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            $room_meta = get_post_meta(get_the_ID(), '_nvo_room_meta', true);
            $room_color = isset($room_meta['color']) ? $room_meta['color'] : '#ccc'; // Default color

            ?>
            <div class="nvo-room">
                <div class="nvo-room-image">
                    <?php if (has_post_thumbnail()) : ?>
                        <div style="border-bottom: 5px solid <?php echo esc_attr($room_color); ?>;">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php else : ?>
                        <div style="border-bottom: 5px solid <?php echo esc_attr($room_color); ?>;">
                            <img src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-fallback-image.png'); ?>" alt="<?php the_title(); ?>">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="nvo-room-title">
                    <h3><?php the_title(); ?></h3>
                </div>
                <a href="<?php the_permalink(); ?>" class="button">Skatīt / Rezervēt</a>
            </div>
            <?php
        endwhile;
    else :
        echo '<p>Nav pieejamu telpu atbilstoši izvēlētajiem kritērijiem.</p>';
    endif;

    wp_reset_postdata();
    wp_die();
}
