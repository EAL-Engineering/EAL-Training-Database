let timeLeft = null;

function setCountdown(initialTime) {
    timeLeft = initialTime;
    updateCountdown();
    setInterval(updateCountdown, 1000);
}

function updateCountdown() {
    const countdownElement = document.getElementById('countdown');
    if (!countdownElement || timeLeft === null) return;

    if (timeLeft > 0) {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        countdownElement.textContent = `${minutes}m ${seconds}s`;
        timeLeft--;
    } else {
        countdownElement.textContent = "Session expired";
    }
}
