<?php
// Version 1.0 - Shortcode for Room Listing with Filters
defined('ABSPATH') || exit;

add_shortcode('nvo_rooms_list', 'createit_nvo_rooms_list_shortcode');

function createit_nvo_rooms_list_shortcode($atts) {
    wp_enqueue_script('jquery');
    wp_enqueue_style('nvo-rooms-styles', CREATEIT_NVO_PLUGIN_URL . 'assets/css/room-shortcode-styles.css');

    ob_start();
    ?>
    <div class="nvo-rooms-wrap">
        <h1>Pieejamās telpas</h1>

        <!-- Filter Section -->
        <div class="nvo-rooms-filters">
            <div>
                <label for="filter-apkaime">Apkaime:</label>
                <select id="filter-apkaime">
                    <option value="">Visas apkaimes</option>
                    <?php
                    $apkaimes = ['Āgenskalns', 'Atgāzene', 'Avoti', 'Beberbeķi', 'Berģi', 'Bieriņi', 'Bišumuiža', 'Bolderāja', 'Brasa'];
                    foreach ($apkaimes as $apkaime) {
                        echo "<option value='" . esc_attr($apkaime) . "'>" . esc_html($apkaime) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label for="filter-capacity">Apmeklētāju ietilpība:</label>
                <input type="number" id="filter-capacity" placeholder="Ievadiet personu skaitu">
            </div>

            <div>
                <label for="filter-disability">Invalīdiem draudzīga:</label>
                <select id="filter-disability">
                    <option value="">Izvēlēties</option>
                    <option value="1">Jā</option>
                    <option value="0">Nē</option>
                </select>
            </div>

            <div>
                <button id="apply-filters" class="button">Meklēt</button>
            </div>
        </div>

        <!-- Room List -->
        <div id="nvo-rooms-list" class="nvo-rooms-list">
            <!-- Placeholder for AJAX-loaded rooms -->
        </div>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            function loadRooms(filters) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'GET',
                    data: {
                        action: 'filter_rooms',
                        filters: filters,
                    },
                    success: function (response) {
                        $('#nvo-rooms-list').html(response);
                    },
                    error: function () {
                        $('#nvo-rooms-list').html('<p>Kļūda, ielādējot telpas.</p>');
                    },
                });
            }

            $('#apply-filters').on('click', function () {
                const filters = {
                    apkaime: $('#filter-apkaime').val(),
                    capacity: $('#filter-capacity').val(),
                    disability: $('#filter-disability').val(),
                };
                loadRooms(filters);
            });

            loadRooms({});
        });
    </script>
    <?php
    return ob_get_clean();
}