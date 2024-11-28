<?php
// Version 1.3 - Admin Calendar Page
// File: createit-nvo-reservation-calendar/includes/admin-calendar.php

defined('ABSPATH') || exit;

// Main function for the calendar page
function createit_nvo_calendar_page() {
    ?>
    <div class="wrap">
        <h1>Kalendārs</h1>

        <!-- Add New Reservation Button -->
        <a href="<?php echo admin_url('post-new.php?post_type=nvo_reservations'); ?>" class="button button-primary" style="margin-bottom: 10px;">
            Pievienot jaunu rezervāciju
        </a>

        <!-- Calendar Controls -->
        <div class="calendar-controls" style="margin-bottom: 20px;">
            <select id="calendar-view" class="calendar-control">
                <option value="dayGridMonth">Mēnesis</option>
                <option value="timeGridWeek">Nedēļa</option>
                <option value="timeGridDay">Diena</option>
                <option value="listWeek">Dienas kārtība</option>
            </select>
            <button id="prev-period" class="button">Iepriekšējais</button>
            <button id="next-period" class="button">Nākamais</button>
            <input type="date" id="specific-date" class="calendar-control" />
            <button id="go-to-date" class="button">Rādīt izvēlēto dienu</button>
        </div>

        <!-- Calendar Container -->
        <div id="calendar"></div>
    </div>

    <!-- FullCalendar Script -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'lv',
                firstDay: 1, // Week starts on Monday
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                businessHours: {
                    daysOfWeek: [1, 2, 3, 4, 5, 6], // Monday - Saturday
                    startTime: '08:00',
                    endTime: '22:00'
                },
                events: [
                    {
                        id: '1',
                        title: 'Tikšanās',
                        start: '2024-11-21T10:00:00',
                        end: '2024-11-21T12:00:00'
                    },
                    {
                        id: '2',
                        title: 'Apmācības',
                        start: '2024-11-21T13:00:00',
                        end: '2024-11-21T16:00:00'
                    },
                    {
                        id: '3',
                        title: 'Seminārs',
                        start: '2024-11-22T09:00:00',
                        end: '2024-11-22T11:00:00'
                    },
                    {
                        id: '4',
                        title: 'Konsultācijas',
                        start: '2024-11-23T10:00:00',
                        end: '2024-11-23T12:00:00'
                    }
                ],
                eventClick: function (info) {
                    alert('Notikums: ' + info.event.title);
                    info.jsEvent.preventDefault();
                }
            });

            // Change view based on dropdown
            document.getElementById('calendar-view').addEventListener('change', function () {
                calendar.changeView(this.value);
            });

            // Go to previous period
            document.getElementById('prev-period').addEventListener('click', function () {
                calendar.prev();
            });

            // Go to next period
            document.getElementById('next-period').addEventListener('click', function () {
                calendar.next();
            });

            // Go to specific date
            document.getElementById('go-to-date').addEventListener('click', function () {
                var specificDate = document.getElementById('specific-date').value;
                if (specificDate) {
                    calendar.gotoDate(specificDate); // Go to selected date
                    calendar.changeView('timeGridDay'); // Switch to day view
                } else {
                    alert('Lūdzu izvēlieties datumu.');
                }
            });

            calendar.render();
        });
    </script>
    <?php
}
