/* Silver and cobalt light paths. All geometry stays in the background. */
(() => {
    const hero = document.querySelector('.hero');
    if (!hero) return;

    const canvas = document.createElement('canvas');
    canvas.className = 'light-flow';
    canvas.setAttribute('aria-hidden', 'true');
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    document.body.prepend(canvas);
    document.body.classList.add('flow-enabled');

    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    let width = 0;
    let height = 0;
    let frame = 0;
    let lastTime = 0;
    let time = 0;
    let scroll = window.scrollY;
    let auditProgress = 0;
    let auditWeight = 0;
    const timeline = document.querySelector('.process__timeline');
    let timelineVisible = false;
    let timelineHeight = 0;

    function point(y, strand, phase) {
        const world = (y + scroll * .42 * (1 - auditWeight)) / height
            + auditProgress * .42 * auditWeight;
        const spread = strand * (.006 + .014 * Math.sin(world * 3.2 + .8 + auditProgress * auditWeight * .8));
        return width * (.72 - .2 * auditWeight + .20 * Math.sin(world * 3.8 - .5)
            + .075 * Math.sin(world * 7 + phase * .18)
            + spread + .012 * Math.sin(world * 6 + phase + strand * .12));
    }

    function path(start, end, strand, phase) {
        ctx.beginPath();
        for (let y = start; y <= end; y += 9) {
            const x = point(y, strand, phase);
            if (y === start) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        }
    }

    function draw() {
        ctx.clearRect(0, 0, width, height);
        ctx.globalCompositeOperation = 'lighter';
        const mobile = width < 700;
        const strands = mobile ? 14 : 26;

        // Broad, faint light underneath the fine individual fibers.
        ctx.shadowColor = '#3172cb';
        ctx.shadowBlur = mobile ? 22 : 45;
        ctx.strokeStyle = 'rgba(54, 111, 209, .12)';
        ctx.lineWidth = mobile ? 28 : 60;
        path(-12, height + 18, 0, time);
        ctx.stroke();
        ctx.shadowBlur = 0;

        for (let i = 0; i < strands; i++) {
            const strand = i - strands / 2;
            const silver = i % 4 === 0;
            ctx.lineWidth = silver ? .85 : 1.1;
            const intensity = .24 + .14 * Math.sin(i * 2.1 + time * .6) ** 2;
            ctx.strokeStyle = silver
                ? `rgba(194, 215, 239, ${intensity})`
                : `rgba(69, 137, 239, ${intensity})`;
            path(-12, height + 18, strand, time);
            ctx.stroke();

            // The short bright packet follows precisely the same strand.
            const start = ((time * .10 + i * .13) % 1.3) * height - height * .2;
            const length = height * .13;
            const gradient = ctx.createLinearGradient(0, start, 0, start + length);
            gradient.addColorStop(0, 'rgba(85,155,255,0)');
            gradient.addColorStop(.75, silver ? 'rgba(216,232,250,.65)' : 'rgba(99,169,255,.55)');
            gradient.addColorStop(1, 'rgba(132,187,255,0)');
            ctx.strokeStyle = gradient;
            ctx.lineWidth = silver ? 1.4 : 1.8;
            path(start, start + length, strand, time);
            ctx.stroke();
        }
        ctx.globalCompositeOperation = 'source-over';
        if (timeline && timelineVisible) {
            timeline.style.setProperty('--flow-packet-y', `${((time * .13) % 1) * Math.max(0, timelineHeight - 100)}px`);
        }
    }

    function tick(now) {
        frame = 0;
        if (document.hidden || reduced.matches) return;
        if (now - lastTime >= (width < 700 ? 50 : 33)) {
            time += Math.min((now - lastTime) / 1000, .06);
            lastTime = now;
            scroll += (window.scrollY - scroll) * .14;
            draw();
        }
        frame = requestAnimationFrame(tick);
    }

    function sync() {
        cancelAnimationFrame(frame);
        frame = 0;
        const running = !reduced.matches;
        document.body.classList.toggle('flow-paused', !running);
        document.dispatchEvent(new Event('flow-motion-change'));
        draw();
        if (running && !document.hidden) {
            lastTime = performance.now();
            frame = requestAnimationFrame(tick);
        }
    }

    function resize() {
        width = window.innerWidth;
        height = window.innerHeight;
        const ratio = Math.min(window.devicePixelRatio || 1, width < 700 ? 1 : 1.5);
        canvas.width = Math.round(width * ratio);
        canvas.height = Math.round(height * ratio);
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        scroll = window.scrollY;
        draw();
    }

    reduced.addEventListener('change', sync);
    document.addEventListener('audit-flow', event => {
        auditProgress = event.detail.progress;
        auditWeight = reduced.matches ? 0 : event.detail.weight;
    });
    document.addEventListener('visibilitychange', sync);
    window.addEventListener('resize', resize, { passive: true });
    if (timeline) {
        new IntersectionObserver(entries => {
            timelineVisible = entries[0].isIntersecting;
        }).observe(timeline);
        new ResizeObserver(() => { timelineHeight = timeline.offsetHeight; }).observe(timeline);
    }
    resize();
    sync();
})();
