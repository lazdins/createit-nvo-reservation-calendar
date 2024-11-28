document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.day-active-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', (event) => {
            const container = event.target.closest('.day-schedule');
            const timeInterval = container.querySelector('.time-interval');
            
            if (event.target.checked) {
                timeInterval.style.display = '';
            } else {
                timeInterval.style.display = 'none';
                // Clear the input fields when unchecked
                timeInterval.querySelectorAll('input').forEach(input => {
                    input.value = '';
                });
            }
        });
    });
});
