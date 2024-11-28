<?php
// Version 1.5 - Register CPTs and Add Custom Admin Pages createit-nvo-reservation-calendar/includes/cpt.php
defined('ABSPATH') || exit;

function createit_nvo_register_cpts() {
    // Register Reservations CPT
    register_post_type('nvo_reservations', [
        'label' => 'Rezervācijas',
        'public' => false,
        'show_ui' => true,
        'supports' => ['title'],
        'menu_icon' => 'dashicons-calendar-alt',
        'has_archive' => false,
        'rewrite' => ['slug' => 'rezervacijas'],
    ]);

    // Register Rooms CPT
    register_post_type('nvo_rooms', [
        'label' => 'Telpas',
        'public' => false,
        'show_ui' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-admin-home',
        'has_archive' => false,
        'rewrite' => ['slug' => 'telpas'],
    ]);

    // Register Equipment CPT
    register_post_type('nvo_equipment', [
        'label' => 'Aprīkojums',
        'public' => false,
        'show_ui' => true,
        'supports' => ['title'],
        'menu_icon' => 'dashicons-hammer',
        'has_archive' => false,
        'rewrite' => ['slug' => 'aprikojums'],
    ]);
}

add_action('init', 'createit_nvo_register_cpts');

// Override Admin Pages for Custom Post Types
function createit_nvo_replace_cpt_admin_pages() {
    // Replace Reservations Admin Page
    add_submenu_page(
        'edit.php?post_type=nvo_reservations',
        'Rezervācijas',
        'Rezervācijas',
        'manage_options',
        'custom-nvo-reservations',
        'createit_nvo_custom_reservations_page'
    );

    // Replace Rooms Admin Page
    add_submenu_page(
        'edit.php?post_type=nvo_rooms',
        'Telpas',
        'Telpas',
        'manage_options',
        'custom-nvo-rooms',
        'createit_nvo_custom_rooms_page'
    );

    // Replace Equipment Admin Page
    add_submenu_page(
        'edit.php?post_type=nvo_equipment',
        'Aprīkojums',
        'Aprīkojums',
        'manage_options',
        'custom-nvo-equipment',
        'createit_nvo_custom_equipment_page'
    );
}

add_action('admin_menu', 'createit_nvo_replace_cpt_admin_pages');
