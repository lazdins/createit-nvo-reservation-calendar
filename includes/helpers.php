<?php
// v1.0 createit-nvo-reservation-calendar/inlcudes/helpers.php
defined('ABSPATH') || exit;


/**
 * Check for overlapping reservations and return conflicts.
 */
function validate_reservation_conflicts($room_id, $dates) {
    global $wpdb;

    $reservations_table = "{$wpdb->prefix}nvo_reservations";
    $overlap_found = [];

    foreach ($dates as $date) {
        $existing_reservations = $wpdb->get_results($wpdb->prepare(
            "SELECT id, dates, status FROM $reservations_table 
             WHERE room_id = %d AND status IN ('pending', 'confirmed', 'holding')",
            $room_id
        ));

        foreach ($existing_reservations as $reservation) {
            $reservation_dates = json_decode($reservation->dates, true);

            foreach ($reservation_dates as $existing_date) {
                if (
                    $existing_date['date'] === $date['date'] && // Same date
                    (
                        ($date['from'] >= $existing_date['from'] && $date['from'] < $existing_date['to']) || // Overlap start
                        ($date['to'] > $existing_date['from'] && $date['to'] <= $existing_date['to']) || // Overlap end
                        ($date['from'] <= $existing_date['from'] && $date['to'] >= $existing_date['to']) // Complete overlap
                    )
                ) {
                    $overlap_found[] = [
                        'conflicting_reservation_id' => $reservation->id,
                        'date' => $existing_date['date'],
                        'from' => $existing_date['from'],
                        'to' => $existing_date['to'],
                        'status' => $reservation->status,
                    ];
                }
            }
        }
    }

    return $overlap_found;
}

/**
 * Create a temporary lock on a reservation.
 */
function create_temporary_reservation_lock($room_id, $dates) {
    global $wpdb;

    $reservations_table = "{$wpdb->prefix}nvo_reservations";
    $expiry = current_time('mysql', true) + 300; // 5 minutes lock

    $wpdb->insert($reservations_table, [
        'room_id' => $room_id,
        'dates' => json_encode($dates),
        'status' => 'holding',
        'holding_expiry' => $expiry,
    ]);

    return $wpdb->insert_id;
}
