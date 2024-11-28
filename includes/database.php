<?php
// Version 1.0 - Database setup createit-nvo-reservation-calendar/includes/database.php
defined('ABSPATH') || exit;

function createit_nvo_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Reservations table
    $reservations_table = "{$wpdb->prefix}nvo_reservations";
    $sql_reservations = "CREATE TABLE IF NOT EXISTS $reservations_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        room_id BIGINT UNSIGNED NOT NULL,
        dates JSON NOT NULL,
        event_name VARCHAR(255) NOT NULL,
        organizer_name VARCHAR(255) NOT NULL,
        organizer_address TEXT,
        organizer_reg_number VARCHAR(50),
        participant_count INT,
        event_frequency ENUM('single', 'recurring_weekly', 'recurring_monthly') DEFAULT 'single',
        event_type ENUM('meeting', 'cultural', 'administrative', 'charity', 'other'),
        disability_friendly BOOLEAN DEFAULT FALSE,
        event_description TEXT,
        equipment_requested JSON,
        coffee_breaks TEXT,
        contact_name VARCHAR(255),
        contact_phone VARCHAR(50),
        contact_email VARCHAR(255),
        signatory_name VARCHAR(255),
        signatory_position VARCHAR(255),
        status ENUM('pending', 'confirmed', 'cancelled', 'holding') DEFAULT 'pending',
        holding_expiry DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";

    // Rooms table
    $rooms_table = "{$wpdb->prefix}nvo_rooms";
    $sql_rooms = "CREATE TABLE IF NOT EXISTS $rooms_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        featured_image VARCHAR(255),
        gallery JSON,
        location VARCHAR(255),
        color VARCHAR(7),
        disability_friendly BOOLEAN DEFAULT FALSE,
        equipment JSON,
        availability_schedule JSON,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";

    // Equipment table
    $equipment_table = "{$wpdb->prefix}nvo_equipment";
    $sql_equipment = "CREATE TABLE IF NOT EXISTS $equipment_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        availability INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";

    // Global settings table
    $global_settings_table = "{$wpdb->prefix}nvo_global_settings";
    $sql_global_settings = "CREATE TABLE IF NOT EXISTS $global_settings_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        non_bookable_dates JSON,
        weekly_availability JSON,
        reservation_rules TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_reservations);
    dbDelta($sql_rooms);
    dbDelta($sql_equipment);
    dbDelta($sql_global_settings);
}
