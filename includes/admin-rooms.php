<?php
// Version 1.4 - List Rooms from CPT "nvo_rooms" createit-nvo-reservation-calendar/includes/admin-rooms.php
defined('ABSPATH') || exit;

function createit_nvo_rooms_page() {
    // Fetch all rooms from the "nvo_rooms" custom post type
    $rooms = get_posts([
        'post_type' => 'nvo_rooms',
        'posts_per_page' => -1, // Fetch all entries
        'post_status' => 'publish', // Only published posts
    ]);

    // Fetch saved global availability settings
    $global_availability = get_option('createit_nvo_global_availability', [
        'non_bookable_dates' => [],
        'week_days' => [
            'pirmdiena' => true,
            'otrdiena' => true,
            'trešdiena' => true,
            'ceturtdiena' => true,
            'piektdiena' => true,
            'sestdiena' => false,
            'svētdiena' => false,
        ],
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_global_availability'])) {
        // Save form data
        check_admin_referer('save_global_availability');

        $non_bookable_dates = array_filter(array_map('sanitize_text_field', $_POST['non_bookable_dates'] ?? []));
        $week_days = array_fill_keys(
            ['pirmdiena', 'otrdiena', 'trešdiena', 'ceturtdiena', 'piektdiena', 'sestdiena', 'svētdiena'],
            false
        );

        foreach ($week_days as $day => $value) {
            $week_days[$day] = isset($_POST['week_days'][$day]);
        }

        $global_availability = [
            'non_bookable_dates' => $non_bookable_dates,
            'week_days' => $week_days,
        ];

        update_option('createit_nvo_global_availability', $global_availability);

        echo '<div class="notice notice-success"><p>Iestatījumi saglabāti.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Telpas</h1>
        
		<a href="<?php echo admin_url('post-new.php?post_type=nvo_rooms'); ?>" class="button button-primary">Pievienot jaunu telpu</a>
        
        <h2>Vispārējie telpu pieejamības iestatījumi</h2>
        <form method="post" id="global-availability">
            <?php wp_nonce_field('save_global_availability'); ?>
            <label>Datumi kuros, rezervācija nav iespējama:</label>
            <div id="non-bookable-dates">
                <?php if (empty($global_availability['non_bookable_dates'])): ?>
                    <div class="date-entry">
                        <input type="date" name="non_bookable_dates[]" />
                    </div>
                <?php else: ?>
                    <?php foreach ($global_availability['non_bookable_dates'] as $date): ?>
                        <div class="date-entry">
                            <input type="date" name="non_bookable_dates[]" value="<?php echo esc_attr($date); ?>" />
                            <button type="button" class="button remove-date">Noņemt</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button add-date">Pievienot datumu</button>

            <h3>Rezervācijas pieejamas šajās nedēļas dienās:</h3>
            <?php foreach ($global_availability['week_days'] as $day => $enabled): ?>
                <label>
                    <input type="checkbox" name="week_days[<?php echo esc_attr($day); ?>]" <?php checked($enabled); ?> />
                    <?php echo ucfirst($day); ?>
                </label>
            <?php endforeach; ?>

            <p>
                <button type="submit" name="save_global_availability" class="button button-primary">Saglabāt iestatījumus</button>
            </p>
        </form>

<h2>Visas telpas</h2>
<table class="widefat fixed">
<thead>
    <tr>
        <th>ID</th>
        <th>Telpas nosaukums</th>
        <th>Atrašanās vieta</th>
        <th>Darba laiks</th>
        <th>Pieejamais aprīkojums</th>
        <th>Invalīdiem draudzīga</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($rooms as $room): ?>
        <?php
        // Fetch room data
        $room_data = get_post_meta($room->ID, '_nvo_room_meta', true);
        $location = $room_data['location'] ?? 'Nav norādīts';
        $availability_schedule = $room_data['availability_schedule'] ?? [];
        $equipment = $room_data['equipment'] ?? [];
        $disability_friendly = $room_data['disability_friendly'] ?? false;
        $color = $room_data['color'] ?? '#ffffff';
        ?>
        <tr onclick="window.location='<?php echo admin_url('post.php?post=' . $room->ID . '&action=edit'); ?>';" style="cursor: pointer;">
            <td><?php echo esc_html($room->ID); ?></td>
            <td>
                <div style="display: flex; align-items: center;">
                    <div style="width: 28px; height: 28px; background-color: <?php echo esc_attr($color); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                        <span class="dashicons dashicons-admin-home" style="color: #ffffff; font-size: 16px;"></span>
                    </div>
                    <?php echo esc_html($room->post_title); ?>
                </div>
            </td>
            <td><?php echo esc_html($location); ?></td>
            <td>
                <?php
                if (!empty($availability_schedule)) {
                    foreach ($availability_schedule as $day => $schedule) {
                        if (!empty($schedule['active'])) {
                            echo ucfirst($day) . ': ' . esc_html($schedule['from']) . ' - ' . esc_html($schedule['to']) . '<br>';
                        }
                    }
                } else {
                    echo 'Nav norādīts';
                }
                ?>
            </td>
            <td>
                <?php
                if (!empty($equipment)) {
                    $equipment_titles = array_map(function ($equipment_id) {
                        return get_the_title($equipment_id);
                    }, $equipment);
                    echo implode(', ', $equipment_titles);
                } else {
                    echo 'Nav norādīts';
                }
                ?>
            </td>
            <td><?php echo esc_html($disability_friendly ? 'Jā' : 'Nē'); ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>
</table>



    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nonBookableDates = document.getElementById('non-bookable-dates');

            document.querySelector('.add-date').addEventListener('click', function () {
                const newDateField = document.createElement('div');
                newDateField.classList.add('date-entry');
                newDateField.innerHTML = `
                    <input type="date" name="non_bookable_dates[]" />
                    <button type="button" class="button remove-date">Noņemt</button>
                `;
                nonBookableDates.appendChild(newDateField);
            });

            nonBookableDates.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-date')) {
                    e.target.parentElement.remove();
                }
            });
        });
    </script>
    <?php
}