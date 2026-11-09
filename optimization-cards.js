/* Native disclosure cards with an optional horizontal rail on small screens. */
(() => {
    const section = document.querySelector('#optimization');
    if (!section) return;
    const rail = section.querySelector('.optimization__grid');
    const cards = [...rail.querySelectorAll('.optimization-card')];
    if (!cards.length) return;
    const panels = cards.map((card, i) => {
        const detail = document.createElement('details');
        detail.name = 'optimization-audits';
        detail.className = 'card optimization-card audit-tile';
        const summary = document.createElement('summary');
        summary.append(card.querySelector('.card-art'), card.querySelector('.card__title'));
        const hint = document.createElement('span');
        hint.className = 'audit-tile__hint';
        hint.setAttribute('aria-hidden', 'true');
        hint.textContent = '↗';
        summary.append(hint);
        const body = document.createElement('div');
        body.className = 'audit-tile__body';
        body.append(...card.children);
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'audit-tile__close';
        close.textContent = 'Закрыть';
        close.addEventListener('click', () => {
            detail.open = false;
            summary.focus({ preventScroll: true });
        });
        body.append(close);
        detail.append(summary, body);
        card.replaceWith(detail);
        return detail;
    });
    section.classList.add('audit-gallery');

    const controls = document.createElement('div');
    controls.className = 'audit-controls';
    const previous = document.createElement('button');
    const next = document.createElement('button');
    previous.type = next.type = 'button';
    previous.textContent = '←';
    next.textContent = '→';
    previous.setAttribute('aria-label', 'Предыдущие аудиты');
    next.setAttribute('aria-label', 'Следующие аудиты');
    controls.append(previous, next);
    rail.before(controls);
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    function move(direction) {
        const distance = panels[0].getBoundingClientRect().width + 20;
        rail.scrollBy({ left: direction * distance, behavior: reduced.matches ? 'instant' : 'smooth' });
    }
    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    function updateControls() {
        const end = rail.scrollWidth - rail.clientWidth;
        controls.hidden = end < 2;
        previous.disabled = rail.scrollLeft < 2;
        next.disabled = rail.scrollLeft > end - 2;
    }
    rail.addEventListener('scroll', updateControls, { passive: true });
    new ResizeObserver(updateControls).observe(rail);

    let active = 0;
    let visible = false;
    function updateFlow() {
        document.dispatchEvent(new CustomEvent('audit-flow', {
            detail: { progress: active, weight: visible && panels.some(panel => panel.open) ? 1 : 0 }
        }));
    }
    panels.forEach((panel, i) => {
        panel.addEventListener('toggle', () => {
            if (panel.open) {
                active = i;
                panels.forEach(other => { if (other !== panel) other.open = false; });
            }
            updateFlow();
        });
    });
    new IntersectionObserver(entries => {
        visible = entries[0].isIntersecting;
        updateFlow();
    }, { threshold: .1 }).observe(section);
    updateControls();
})();
