/* =====================================================================
   Liberia Tourism Management System - front-end behaviour (vanilla ES6)
   ===================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    initMenu();
    initSearch();
    initBookingCalculator();
    initConfirmations();
    initReveal();
    initAlerts();
});

/* Mobile navigation (public site and admin sidebar share the same pattern) */
function initMenu() {
    document.querySelectorAll('[aria-controls]').forEach(button => {
        const target = document.getElementById(button.getAttribute('aria-controls'));
        if (!target) return;
        button.addEventListener('click', () => {
            const open = target.classList.toggle('open');
            button.setAttribute('aria-expanded', open);
        });
    });
}

/* Live search on the home page: asks api/search.php and lists the matches */
function initSearch() {
    const input = document.querySelector('#site-search');
    const box = document.querySelector('#search-results');
    if (!input || !box) return;

    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const term = input.value.trim();
        if (term.length < 2) { box.hidden = true; return; }
        timer = setTimeout(() => runSearch(term, box), 250); // wait until typing pauses
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('.search-box')) box.hidden = true;
    });
}

async function runSearch(term, box) {
    try {
        const response = await fetch(`${window.APP_BASE}api/search.php?q=${encodeURIComponent(term)}`);
        const results = await response.json();
        box.replaceChildren();
        if (results.length === 0) {
            const empty = document.createElement('p');
            empty.textContent = 'No matches found. Try another word.';
            box.append(empty);
        }
        results.forEach(item => {
            const link = document.createElement('a');
            link.href = item.url;
            const title = document.createElement('span');
            title.textContent = item.title;
            const type = document.createElement('small');
            type.textContent = item.type;
            link.append(title, type);
            box.append(link);
        });
        box.hidden = false;
    } catch (error) {
        box.hidden = true;
    }
}

/* Booking form: recalculates the total whenever a value changes */
function initBookingCalculator() {
    const form = document.querySelector('[data-booking]');
    if (!form) return;

    const price = parseFloat(form.dataset.price);
    const rate = parseFloat(form.dataset.rate);
    const isHotel = form.dataset.booking === 'hotel';
    const totalEl = form.querySelector('#total');
    const lrdEl = form.querySelector('#total-lrd');
    const start = form.querySelector('[name=start_date]');
    const end = form.querySelector('[name=end_date]');

    const update = () => {
        let total;
        if (isHotel) {
            const nights = Math.max(1, Math.round((new Date(end.value) - new Date(start.value)) / 86400000) || 1);
            const rooms = Math.max(1, parseInt(form.rooms.value, 10) || 1);
            total = price * nights * rooms;
            form.querySelector('#nights').textContent = `${nights} night${nights > 1 ? 's' : ''}`;
        } else {
            total = price * Math.max(1, parseInt(form.people.value, 10) || 1);
        }
        totalEl.textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        lrdEl.textContent = 'about L$' + Math.round(total * rate).toLocaleString('en-US');
    };

    // Check-out must always be after check-in
    if (isHotel) {
        start.addEventListener('change', () => {
            const next = new Date(start.value);
            if (isNaN(next)) return;
            next.setDate(next.getDate() + 1);
            end.min = next.toISOString().slice(0, 10);
            if (!end.value || end.value < end.min) end.value = end.min;
        });
    }
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
}

/* Ask before destructive actions such as delete or cancel */
function initConfirmations() {
    document.addEventListener('submit', event => {
        const message = event.target.dataset.confirm;
        if (message && !window.confirm(message)) event.preventDefault();
    });
}

/* Fade sections in as they scroll into view */
function initReveal() {
    const items = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) {
        items.forEach(item => item.classList.add('in'));
        return;
    }
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });
    items.forEach(item => observer.observe(item));
}

/* Success messages disappear after a few seconds */
function initAlerts() {
    document.querySelectorAll('.alert-success').forEach(alert => {
        setTimeout(() => alert.remove(), 6000);
    });
}
