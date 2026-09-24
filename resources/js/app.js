document.addEventListener('DOMContentLoaded', () => {
    // Clock widget updater in header
    const clockEl = document.getElementById('cash-clock');
    if (clockEl) {
        const updateClock = () => {
            const now = new Date();
            clockEl.textContent = now.toLocaleDateString('fr-FR') + ' - ' + now.toLocaleTimeString('fr-FR');
        };
        updateClock();
        setInterval(updateClock, 1000);
    }

    // Prevent double form submission on member creation
    const memberForm = document.getElementById('memberForm');
    if (memberForm) {
        memberForm.addEventListener('submit', () => {
            const btn = document.getElementById('submitBtn');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                btn.innerText = 'Enregistrement...';
            }
        });
    }
});
