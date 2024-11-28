<?php
// Version 1.1 - Added WYSIWYG editor for noteikumi createit-nvo-reservation-calendar/includes/admin-menu.php
defined('ABSPATH') || exit;

function createit_nvo_admin_menu() {
    add_menu_page(
        'NVO Telpu rezervācija', // Page title
        'Telpu rezervācija',     // Menu title
        'manage_options',        // Capability
        'nvo-reservation',       // Menu slug
        'createit_nvo_main_page',// Callback function
        'dashicons-calendar-alt' // Icon
    );

    add_submenu_page(
        'nvo-reservation',
        'Kalendārs',
        'Kalendārs',
        'manage_options',
        'nvo-calendar',
        'createit_nvo_calendar_page'
    );

    add_submenu_page(
        'nvo-reservation',
        'Rezervācijas',
        'Rezervācijas',
        'manage_options',
        'nvo-reservations',
        'createit_nvo_reservations_page' // Submenu points to this callback
    );

    add_submenu_page(
        'nvo-reservation',
        'Telpas',
        'Telpas',
        'manage_options',
        'nvo-rooms',
        'createit_nvo_rooms_page'
    );

    add_submenu_page(
        'nvo-reservation',
        'Aprīkojums',
        'Aprīkojums',
        'manage_options',
        'nvo-equipment',
        'createit_nvo_equipment_page'
    );
}

function createit_nvo_main_page() {
    $noteikumi = get_option('createit_nvo_noteikumi', '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createit_nvo_noteikumi'])) {
        check_admin_referer('createit_nvo_save_noteikumi');
        $noteikumi = wp_kses_post($_POST['createit_nvo_noteikumi']);
        update_option('createit_nvo_noteikumi', $noteikumi);
        echo '<div class="notice notice-success"><p>Noteikumi saglabāti.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>NVO Telpu rezervācija</h1>
        <form method="post">
            <?php wp_nonce_field('createit_nvo_save_noteikumi'); ?>
            <h2>Noteikumi</h2>
            <?php
            wp_editor($noteikumi, 'createit_nvo_noteikumi', [
                'textarea_name' => 'createit_nvo_noteikumi',
                'textarea_rows' => 10,
                'media_buttons' => false,
            ]);
            ?>
            <p>
                <button type="submit" class="button button-primary">Saglabāt noteikumus</button>
            </p>
        </form>
    </div>
    <?php
}
add_action('admin_menu', 'createit_nvo_admin_menu');
