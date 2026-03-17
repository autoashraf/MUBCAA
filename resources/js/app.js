import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
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

    if (!sliderRoot || slides.length < 2) {
        return;
    }

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
});
