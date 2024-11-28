<?php
/**
 * Plugin Name: CreateIT NVO telpu rezervācijas kalendārs
 * Description: A room reservation calendar for NVO.
 * Version: 1.3
 * Author: CreateIT
 * Text Domain: createit
 */

// Version 1.1 - Added Custom Post Types for Reservations, Rooms, and Equipment
// Version 1.2 - Fixed reservations page - added filters, added reservation modal popup, added edit and pdf buttons
// Version 1.3 - Fixed rooms issues for time fields
defined('ABSPATH') || exit;

// Define constants
define('CREATEIT_NVO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CREATEIT_NVO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include files
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/database.php';
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/admin-menu.php';
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/admin-calendar.php';
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/admin-reservations.php';
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/admin-rooms.php';
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/admin-equipment.php';
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/cpt.php';
//include custom meta fields for cpts
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/admin-rooms-meta.php';
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/admin-equipment-meta.php';
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/admin-reservations-meta.php';
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/helpers.php';
//include ajax handlers
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/ajax-handlers.php';
//require tcpdf library
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/tcpdf/tcpdf.php';
//generating pdf logic
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/ajax-generate-pdf.php';
//register shortcodes
require_once CREATEIT_NVO_PLUGIN_PATH . 'includes/front-shortcodes.php';


// Hook to create tables on activation
register_activation_hook(__FILE__, 'createit_nvo_create_tables');

// Hook to load admin menu
add_action('admin_menu', 'createit_nvo_admin_menu');

// Hook to register custom post types
add_action('init', 'createit_nvo_register_cpts');

add_action('admin_enqueue_scripts', function($hook_suffix) {

    // Debugging: Log the hook_suffix and post_type for clarity
    global $post_type;
    error_log("Current hook_suffix: $hook_suffix");
    error_log("Current post_type: $post_type");

    // Enqueue scripts and styles for the FullCalendar integration
    if ($hook_suffix === 'toplevel_page_nvo-calendar') {
        wp_enqueue_style('fullcalendar-core', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/core/main.min.css');
        wp_enqueue_style('fullcalendar-scheduler', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/scheduler/main.min.css');
        wp_enqueue_script('fullcalendar-core', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/core/main.min.js', [], null, true);
        wp_enqueue_script('fullcalendar-scheduler', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/scheduler/main.min.js', ['fullcalendar-core'], null, true);
    }

    // Enqueue Flatpickr for time fields
    if (in_array($post_type, ['nvo_rooms', 'nvo_reservations'], true)) {
        wp_enqueue_style('flatpickr-css', 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.9/flatpickr.min.css');
        wp_enqueue_script('flatpickr-js', 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.9/flatpickr.min.js', [], null, true);
        wp_enqueue_script('flatpickr-init', CREATEIT_NVO_PLUGIN_URL . 'assets/js/flatpickr-init.js', ['flatpickr-js'], null, true);
    }

    // Enqueue styles and scripts for the modal popup on `nvo-reservations` page
    if ($hook_suffix === 'telpu-rezervacija_page_nvo-reservations') {
        wp_enqueue_style('reservation-modal-css', CREATEIT_NVO_PLUGIN_URL . 'assets/css/reservation-modal.css');
        wp_enqueue_script('reservation-modal-js', CREATEIT_NVO_PLUGIN_URL . 'assets/js/reservation-modal.js', ['jquery'], null, true);

        // Localize the modal script with AJAX URL for data fetching
        wp_localize_script('reservation-modal-js', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_url' => admin_url(),
        ]);
    }

    // Enqueue scripts for the working days functionality on the `nvo_rooms` post type
    if ($post_type === 'nvo_rooms' && in_array($hook_suffix, ['post.php', 'post-new.php'])) {
        wp_enqueue_script('admin-room-meta-js', CREATEIT_NVO_PLUGIN_URL . 'assets/js/admin-room-meta.js', ['jquery'], null, true);
        wp_enqueue_style('admin-room-meta-css', CREATEIT_NVO_PLUGIN_URL . 'assets/css/admin-room-meta.css');

        // Localize the room meta script for potential data sharing
        wp_localize_script('admin-room-meta-js', 'roomMetaObject', []);
    }
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('nvo-shortcode-styles', CREATEIT_NVO_PLUGIN_URL . 'assets/css/room-shortcode-styles.css', [], '1.0');
});



//register routes
add_action('rest_api_init', function() {
    register_rest_route('nvo/v1', '/resources', [
        'methods' => 'GET',
        'callback' => function() {
            $rooms = get_posts([
                'post_type' => 'nvo_rooms',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ]);

            return array_map(function($room) {
                return [
                    'id' => $room->ID,
                    'title' => $room->post_title,
                ];
            }, $rooms);
        },
    ]);
});

add_action('rest_api_init', function() {
    register_rest_route('nvo/v1', '/events', [
        'methods' => 'GET',
        'callback' => function() {
            $reservations = get_posts([
                'post_type' => 'nvo_reservations',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ]);

            return array_map(function($reservation) {
                $meta = get_post_meta($reservation->ID, '_nvo_reservation_meta', true);
                return [
                    'id' => $reservation->ID,
                    'resourceId' => $meta['room_id'] ?? null,
                    'title' => $reservation->post_title,
                    'start' => $meta['dates'][0]['date'] . 'T' . $meta['dates'][0]['from'],
                    'end' => $meta['dates'][0]['date'] . 'T' . $meta['dates'][0]['to'],
                    'url' => admin_url('post.php?post=' . $reservation->ID . '&action=edit'),
                ];
            }, $reservations);
        },
    ]);
});




add_action('admin_init', function() {
    // Handle the generate_reservation_pdf action
    if (isset($_GET['action']) && $_GET['action'] === 'generate_reservation_pdf') {
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            generate_reservation_pdf();
        } else {
            wp_die(__('Invalid reservation ID.', 'createit'));
        }
    }
});



// Register AJAX handlers for reservation validation and locking
add_action('wp_ajax_validate_reservation_dates', 'ajax_validate_reservation_dates');
add_action('wp_ajax_create_temporary_lock', 'ajax_create_temporary_lock');
add_action('wp_ajax_release_expired_locks', 'ajax_release_expired_locks');
