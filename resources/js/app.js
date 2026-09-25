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

    // Gestionnaire global du bouton actualiser (100% compatible CSP, sans inline script)
    document.addEventListener('click', (e) => {
        const refreshBtn = e.target.closest('[data-action="refresh"], [data-refresh], #btn-actualiser, .btn-actualiser');
        if (refreshBtn) {
            e.preventDefault();
            window.location.reload();
        }
    });

    // Gestionnaire global du bouton d'impression (100% compatible CSP, sans inline script)
    document.addEventListener('click', (e) => {
        const printBtn = e.target.closest('[data-action="print"], [data-print], .btn-imprimer, .btn-print');
        if (printBtn) {
            e.preventDefault();
            window.print();
        }
    });
});
