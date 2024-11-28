<?php
// Version 1.2 - Corrected Equipment List with Clickable Links createit-nvo-reservation-calendar/includes/admin-equipment.php
defined('ABSPATH') || exit;

function createit_nvo_equipment_page() {
    // Fetch all equipment from the "nvo_equipment" custom post type
    $equipment_items = get_posts([
        'post_type' => 'nvo_equipment',
        'posts_per_page' => -1, // Fetch all entries
        'post_status' => 'publish', // Only published posts
    ]);

    ?>
    <div class="wrap">
        <h1>Aprīkojums</h1>
        
		<a href="<?php echo admin_url('post-new.php?post_type=nvo_equipment'); ?>" class="button button-primary">Pievienot jaunu aprīkojumu</a>
<h2>Pieejamais aprīkojums</h2>
		<table class="widefat fixed">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nosaukums</th>
                    <th>Pieejamība</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($equipment_items)): ?>
                    <?php foreach ($equipment_items as $item): ?>
                        <?php
                        // Retrieve custom meta data for the equipment
                        $equipment_meta = get_post_meta($item->ID, '_nvo_equipment_meta', true);
                        $availability = isset($equipment_meta['availability']) ? intval($equipment_meta['availability']) : 'Nav norādīts';
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($item->ID); ?>">
                                    <?php echo esc_html($item->ID); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($item->ID); ?>">
                                    <?php echo esc_html($item->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($availability); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Nav pievienots aprīkojums.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
