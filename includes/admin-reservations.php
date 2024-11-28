<?php
// Admin Reservations Page createit-nvo-reservation-calendar/includes/admin-reservations.php
defined('ABSPATH') || exit;

function createit_nvo_reservations_page() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('Sorry, you are not allowed to access this page.'));
    }

    // Fetch and sanitize filters
    $filter_room = isset($_GET['filter_room']) ? intval($_GET['filter_room']) : '';
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
    $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
    $filter_date_till = isset($_GET['filter_date_till']) ? sanitize_text_field($_GET['filter_date_till']) : '';
    $filter_search = isset($_GET['filter_search']) ? sanitize_text_field($_GET['filter_search']) : '';

    // Build base query arguments
    $args = [
        'post_type' => 'nvo_reservations',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        's' => $filter_search,
        'meta_query' => ['relation' => 'AND'],
    ];

    if ($filter_room) {
        $args['meta_query'][] = [
            'key' => '_nvo_reservation_meta',
            'value' => sprintf('"room_id";i:%d', $filter_room),
            'compare' => 'LIKE',
        ];
    }

    if ($filter_status) {
        $args['meta_query'][] = [
            'key' => '_nvo_reservation_meta',
            'value' => sprintf('"status";s:%d:"%s"', strlen($filter_status), $filter_status),
            'compare' => 'LIKE',
        ];
    }

    // Initial query
    $initial_reservations = get_posts($args);

    // Date filtering
    if ($filter_date_from || $filter_date_till) {
        $filter_date_from = $filter_date_from ? strtotime($filter_date_from) : null;
        $filter_date_till = $filter_date_till ? strtotime($filter_date_till) : null;

        $reservations = array_filter($initial_reservations, function ($reservation) use ($filter_date_from, $filter_date_till) {
            $meta = get_post_meta($reservation->ID, '_nvo_reservation_meta', true);

            if (!isset($meta['dates']) || empty($meta['dates'])) {
                return false;
            }

            foreach ($meta['dates'] as $date_entry) {
                $event_date = strtotime($date_entry['date']);
                if (
                    (!$filter_date_from || $event_date >= $filter_date_from) &&
                    (!$filter_date_till || $event_date <= $filter_date_till)
                ) {
                    return true;
                }
            }

            return false;
        });
    } else {
        $reservations = $initial_reservations;
    }

    // Fetch all rooms
    $rooms = get_posts([
        'post_type' => 'nvo_rooms',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    ?>
    <div class="wrap">
        <h1>Rezervācijas</h1>

<a href="<?php echo admin_url('post-new.php?post_type=nvo_reservations'); ?>" class="button button-primary" style="margin-bottom: 10px;">
    Pievienot jaunu rezervāciju
</a>

		
<form method="GET" action="<?php echo admin_url('admin.php'); ?>">
    <input type="hidden" name="page" value="nvo-reservations"> <!-- Match the menu slug here -->
    <p><strong>Filtri:</strong></p>
    <div style="display: flex; gap: 10px; align-items: center;">
        <select name="filter_room">
            <option value="">Visas telpas</option>
            <?php foreach ($rooms as $room): ?>
                <option value="<?php echo esc_attr($room->ID); ?>" <?php selected($filter_room, $room->ID); ?>>
                    <?php echo esc_html($room->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="filter_status">
            <option value="">Visi statusi</option>
            <option value="pending" <?php selected($filter_status, 'pending'); ?>>Izskatīšanā</option>
            <option value="confirmed" <?php selected($filter_status, 'confirmed'); ?>>Apstiprināts</option>
            <option value="rejected" <?php selected($filter_status, 'rejected'); ?>>Noraidīts</option>
            <option value="cancelled" <?php selected($filter_status, 'cancelled'); ?>>Atcelts</option>
        </select>
        <input type="date" name="filter_date_from" 
               value="<?php echo esc_attr($filter_date_from ? date('d-m-Y', strtotime($filter_date_from)) : ''); ?>" 
               placeholder="DD-MM-YYYY" 
               pattern="\d{2}-\d{2}-\d{4}">
        <input type="date" name="filter_date_till" 
               value="<?php echo esc_attr($filter_date_till ? date('d-m-Y', strtotime($filter_date_till)) : ''); ?>" 
               placeholder="DD-MM-YYYY" 
               pattern="\d{2}-\d{2}-\d{4}">
        <input type="text" name="filter_search" value="<?php echo esc_attr($filter_search); ?>" placeholder="Meklēt nosaukumā...">
        <button type="submit" class="button">Filtrēt</button>
        <button type="button" class="button" onclick="window.location.href='<?php echo admin_url('admin.php?page=nvo-reservations'); ?>'">Dzēst filtrus</button>
    </div>
</form>




        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Telpa</th>
                    <th>Organizācija</th>
                    <th>Pasākums</th>
                    <th>Laiks</th>
                    <th>Statuss</th>
                    <th>Pēdējās izmaiņas</th>
                </tr>
            </thead>
            <tbody>
<?php if (!empty($reservations)): ?>
    <?php 
    // Define status translations
    $status_translations = [
        'pending' => 'Izskatīšanā',
        'confirmed' => 'Apstiprināts',
        'rejected' => 'Noraidīts',
        'cancelled' => 'Atcelts',
    ];
    ?>
    <?php foreach ($reservations as $reservation): ?>
        <?php
        $meta = get_post_meta($reservation->ID, '_nvo_reservation_meta', true);
        $room_name = isset($meta['room_id']) ? get_the_title($meta['room_id']) : 'N/A';
        $dates = $meta['dates'] ?? [];
        $status_key = $meta['status'] ?? 'N/A';
        $status_label = $status_translations[$status_key] ?? 'Nav zināms'; // Translate status or show "Nav zināms"
        ?>
        <tr class="reservation-row" data-reservation-id="<?php echo esc_attr($reservation->ID); ?>">
            <td><?php echo esc_html($reservation->ID); ?></td>
            <td><?php echo esc_html($room_name); ?></td>
            <td><?php echo esc_html($meta['organizer_name'] ?? 'N/A'); ?></td>
            <td><?php echo esc_html($reservation->post_title); ?></td>
            <td>
                <?php foreach ($dates as $date): ?>
                    <?php echo esc_html($date['date'] . ' No plkst.: ' . $date['from'] . ' Līdz plkst.: ' . $date['to']); ?><br>
                <?php endforeach; ?>
            </td>
            <td><?php echo esc_html($status_label); ?></td>
            <td><?php echo esc_html(get_the_modified_date('Y-m-d H:i:s', $reservation->ID)); ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="7">Nav rezervāciju.</td></tr>
<?php endif; ?>
</tbody>


        </table>
		
<div id="reservation-modal" class="reservation-modal hidden">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div id="reservation-details">
            <p>Loading...</p>
        </div>
    </div>
</div>


		
    </div>
    <?php
}
