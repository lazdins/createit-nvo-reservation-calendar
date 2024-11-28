document.addEventListener('DOMContentLoaded', () => {
    // Function to initialize Flatpickr for all time fields in the given container
    const initializeFlatpickr = (container = document) => {
        container.querySelectorAll('.flatpickr-time').forEach((element) => {
            if (!element._flatpickr) { // Prevent re-initialization
                flatpickr(element, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    minuteIncrement: 30,
                });
            }
        });
    };

    // Initialize Flatpickr for existing fields
    initializeFlatpickr();

    const reservationWrapper = document.getElementById('reservation-dates-wrapper');
    const addButton = document.querySelector('.add-date');


});
