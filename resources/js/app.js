import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const mobileNavGroups = document.querySelectorAll('[data-mobile-nav-group]');

    mobileNavGroups.forEach((group) => {
        const trigger = group.querySelector('[data-mobile-nav-trigger]');
        const panel = group.querySelector('[data-mobile-nav-panel]');

        if (!trigger || !panel) {
            return;
        }

        trigger.addEventListener('click', () => {
            const willOpen = !panel.classList.contains('is-open');

            mobileNavGroups.forEach((item) => {
                item.querySelector('[data-mobile-nav-panel]')?.classList.remove('is-open');
                const itemTrigger = item.querySelector('[data-mobile-nav-trigger]');

                if (itemTrigger) {
                    itemTrigger.setAttribute('aria-expanded', 'false');
                }
            });

            if (willOpen) {
                panel.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });
    });

    const panelRoot = document.querySelector('[data-admin-panels]');
    const triggers = document.querySelectorAll('[data-admin-panel-trigger]');

    if (panelRoot && triggers.length > 0) {
        const panels = panelRoot.querySelectorAll('[data-admin-panel]');

        const setActivePanel = (target) => {
            panels.forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.adminPanel === target);
            });

            triggers.forEach((trigger) => {
                trigger.classList.toggle('is-active', trigger.dataset.adminPanelTrigger === target);
            });
        };

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                setActivePanel(trigger.dataset.adminPanelTrigger);
            });
        });
    }

    const sliderRoot = document.querySelector('[data-home-slider]');
    const slides = document.querySelectorAll('[data-home-slide]');
    const dots = document.querySelectorAll('[data-home-slider-dot]');

    if (sliderRoot && slides.length >= 2) {
        let activeIndex = 0;
        let timerId = null;

        const showSlide = (index) => {
            activeIndex = index;

            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle('is-active', slideIndex === index);
            });

            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === index);
            });
        };

        const startSlider = () => {
            timerId = window.setInterval(() => {
                showSlide((activeIndex + 1) % slides.length);
            }, 5000);
        };

        const resetSlider = () => {
            if (timerId) {
                window.clearInterval(timerId);
            }

            startSlider();
        };

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                resetSlider();
            });
        });

        sliderRoot.addEventListener('mouseenter', () => {
            if (timerId) {
                window.clearInterval(timerId);
            }
        });

        sliderRoot.addEventListener('mouseleave', () => {
            resetSlider();
        });

        showSlide(0);
        startSlider();
    }
});
