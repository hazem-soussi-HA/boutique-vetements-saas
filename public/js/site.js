/* Maison Soussi — interactions & motion */
(function () {
    'use strict';

    const $ = (sel, root) => (root || document).querySelector(sel);
    const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

    /* Mobile navigation */
    const burger = $('#burger');
    const nav = $('#mainNav');
    if (burger && nav) {
        burger.addEventListener('click', () => {
            const open = nav.classList.toggle('is-open');
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* Header shadow on scroll */
    const header = $('#siteHeader');
    if (header) {
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* Reveal on scroll */
    const revealables = $$('.reveal');
    if (revealables.length) {
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

            revealables.forEach((el, i) => {
                el.style.transitionDelay = Math.min(i % 4, 3) * 70 + 'ms';
                io.observe(el);
            });
        } else {
            revealables.forEach((el) => el.classList.add('is-visible'));
        }
    }

    /* Product gallery */
    $$('[data-gallery]').forEach((gallery) => {
        const main = $('[data-gallery-main]', gallery);
        const thumbs = $$('[data-gallery-thumb]', gallery);
        if (!main || !thumbs.length) return;

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const src = thumb.dataset.galleryThumb;
                const alt = thumb.dataset.alt || '';
                if (main.dataset.src !== src) {
                    main.style.opacity = '0';
                    window.setTimeout(() => {
                        main.src = src;
                        main.alt = alt;
                        main.style.opacity = '1';
                    }, 180);
                    main.dataset.src = src;
                }
                thumbs.forEach((t) => t.classList.remove('is-active'));
                thumb.classList.add('is-active');
            });
        });
    });

    /* Size / colour pickers on product page */
    $$('[data-options]').forEach((block) => {
        const input = $('input[type=hidden]', block);
        const targets = $$('[data-sync="' + input.name + '"]');
        $$('.opt', block).forEach((opt) => {
            opt.addEventListener('click', () => {
                $$('.opt', block).forEach((o) => o.classList.remove('is-selected'));
                opt.classList.add('is-selected');
                input.value = opt.dataset.value;
                targets.forEach((t) => { t.value = opt.dataset.value; });
            });
        });
    });

    /* Quantity steppers */
    $$('[data-qty]').forEach((wrap) => {
        const input = $('input', wrap);
        if (!input) return;
        $$('button', wrap).forEach((btn) => {
            btn.addEventListener('click', () => {
                const step = btn.dataset.step === 'up' ? 1 : -1;
                const min = parseInt(input.min || '1', 10);
                const max = parseInt(input.max || '99', 10);
                let value = (parseInt(input.value || '1', 10) || min) + step;
                if (value < min) value = min;
                if (value > max) value = max;
                input.value = value;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });

    /* Admin: confirm destructive actions */
    $$('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    /* Auto-dismiss flash alerts after 6s */
    $$('.alert[data-autohide]').forEach((alert) => {
        window.setTimeout(() => {
            alert.style.transition = 'opacity .5s, transform .5s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            window.setTimeout(() => alert.remove(), 600);
        }, 6000);
    });
})();