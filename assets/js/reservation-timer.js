document.addEventListener('DOMContentLoaded', function () {
    let timerDuration = reservationTimer.timerDuration;
    const timerDisplayTop = document.querySelector('#timer-top');
    const timerDisplayLocal = document.querySelector('#timer-local');

    function updateTimer() {
        const minutes = Math.floor(timerDuration / 60);
        const seconds = timerDuration % 60;
        const formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        if (timerDisplayTop) timerDisplayTop.textContent = `Rezervācija jāpabeidz ${formattedTime} laikā`;
        if (timerDisplayLocal) timerDisplayLocal.textContent = `Rezervācija jāpabeidz ${formattedTime} laikā`;
    }

    function startTimer() {
        updateTimer();
        const interval = setInterval(() => {
            timerDuration -= 1;
            if (timerDuration <= 0) {
                clearInterval(interval);
                alert(reservationTimer.expiryMessage);
                // Handle session expiry (e.g., reload or reset form).
            } else {
                updateTimer();
            }
        }, 1000);
    }

    startTimer();
});
