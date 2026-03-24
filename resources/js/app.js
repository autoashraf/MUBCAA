import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const verificationModalRoot = document.querySelector('[data-verification-modal-root]');

    const mountOtpGroups = (root = document) => {
        root.querySelectorAll('[data-otp-group]').forEach((group) => {
            const form = group.closest('form');
            const hiddenInput = form?.querySelector('input[name="code"]');
            const digits = Array.from(group.querySelectorAll('.otp-digit'));

            if (!form || !hiddenInput || digits.length === 0 || group.dataset.otpMounted === 'true') {
                return;
            }

            const syncHidden = () => {
                hiddenInput.value = digits.map((input) => input.value).join('');

                digits.forEach((input) => {
                    input.classList.toggle('is-filled', input.value !== '');
                });
            };

            digits.forEach((input, index) => {
                input.addEventListener('input', () => {
                    input.value = input.value.replace(/\D/g, '').slice(0, 1);
                    syncHidden();

                    if (input.value && digits[index + 1]) {
                        digits[index + 1].focus();
                    }
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && !input.value && digits[index - 1]) {
                        digits[index - 1].focus();
                    }

                    if (event.key === 'ArrowLeft' && digits[index - 1]) {
                        event.preventDefault();
                        digits[index - 1].focus();
                    }

                    if (event.key === 'ArrowRight' && digits[index + 1]) {
                        event.preventDefault();
                        digits[index + 1].focus();
                    }
                });

                input.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, digits.length);

                    if (!pasted) {
                        return;
                    }

                    digits.forEach((digitInput, digitIndex) => {
                        digitInput.value = pasted[digitIndex] || '';
                    });

                    syncHidden();
                    digits[Math.min(pasted.length, digits.length) - 1]?.focus();
                });
            });

            form.addEventListener('submit', syncHidden);
            group.dataset.otpMounted = 'true';
            syncHidden();
        });
    };

    const clearFormErrors = (form) => {
        form.querySelectorAll('.ajax-error').forEach((node) => node.remove());
        form.querySelectorAll('.is-invalid').forEach((node) => node.classList.remove('is-invalid'));
    };

    const applyFormErrors = (form, errors) => {
        Object.entries(errors || {}).forEach(([field, messages]) => {
            const input = form.querySelector(`[name="${field}"]`);

            if (!input) {
                return;
            }

            input.classList.add('is-invalid');

            const error = document.createElement('small');
            error.className = 'ajax-error';
            error.textContent = Array.isArray(messages) ? messages[0] : messages;
            input.insertAdjacentElement('afterend', error);
        });
    };

    const showFormMessage = (form, message, className = 'alert-success') => {
        form.querySelectorAll('.ajax-form-alert').forEach((node) => node.remove());

        if (!message) {
            return;
        }

        const alert = document.createElement('div');
        alert.className = `${className} ajax-form-alert`;
        alert.textContent = message;
        form.insertAdjacentElement('afterbegin', alert);
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-ajax-form]');

        if (!form) {
            return;
        }

        event.preventDefault();
        clearFormErrors(form);

        const submitter = event.submitter;

        if (submitter) {
            submitter.disabled = true;
        }

        const formData = new FormData(form);

        if (submitter?.name && !formData.has(submitter.name)) {
            formData.append(submitter.name, submitter.value || '1');
        }

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 422) {
                    applyFormErrors(form, payload.errors || {});
                    showFormMessage(form, payload.message || 'Please review the highlighted fields.', 'alert-success alert-warning-like');
                    return;
                }

                showFormMessage(form, payload.message || 'Something went wrong. Please try again.', 'alert-success alert-warning-like');
                return;
            }

            const mode = form.dataset.ajaxForm;

            if (mode === 'registration' && verificationModalRoot && payload.modal_html) {
                verificationModalRoot.innerHTML = payload.modal_html;
                mountOtpGroups(verificationModalRoot);
                return;
            }

            if (mode === 'verification' && payload.completed && payload.continue_url) {
                window.location.href = payload.continue_url;
                return;
            }

            if (mode === 'verification' && verificationModalRoot && payload.modal_html) {
                verificationModalRoot.innerHTML = payload.modal_html;
                mountOtpGroups(verificationModalRoot);
                return;
            }

            if (mode === 'wizard') {
                showFormMessage(form, payload.message);

                if (payload.submitted && payload.redirect_url) {
                    window.location.href = payload.redirect_url;
                    return;
                }

                if (payload.next_step && wizardRoot?.__showStep) {
                    wizardRoot.__showStep(Number(payload.next_step));
                }
            }
        } catch (error) {
            showFormMessage(form, 'Something went wrong. Please try again.', 'alert-success alert-warning-like');
        } finally {
            if (submitter) {
                submitter.disabled = false;
            }
        }
    });

    const mobileNavGroups = document.querySelectorAll('[data-mobile-nav-group]');

    mountOtpGroups();

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

    const wizardRoot = document.querySelector('[data-profile-wizard]');

    if (wizardRoot) {
        const stepInputs = wizardRoot.querySelectorAll('input[name="wizard_step"]');
        const stepLabel = wizardRoot.querySelector('[data-wizard-step-label]');
        const stepItems = wizardRoot.querySelectorAll('[data-wizard-step-item]');
        const panels = wizardRoot.querySelectorAll('[data-wizard-panel]');
        const triggers = wizardRoot.querySelectorAll('[data-step-target]');
        const initialStep = Number(wizardRoot.dataset.initialStep || 2);

        const showStep = (step) => {
            stepInputs.forEach((input) => {
                input.disabled = Number(input.value) !== step;
            });

            panels.forEach((panel) => {
                const isActive = Number(panel.dataset.wizardPanel) === step;
                panel.classList.toggle('is-active', isActive);

                panel.querySelectorAll('input, select, textarea, button').forEach((control) => {
                    if (control.matches('[data-step-target]')) {
                        return;
                    }

                    if (control.name === 'wizard_step') {
                        control.disabled = !isActive;

                        return;
                    }

                    control.disabled = !isActive;
                });
            });

            stepItems.forEach((item) => {
                item.classList.toggle('is-active', Number(item.dataset.wizardStepItem) === step);
            });

            if (stepLabel) {
                stepLabel.textContent = `Step ${step} of 10`;
            }
        };

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                showStep(Number(trigger.dataset.stepTarget));
            });
        });

        wizardRoot.__showStep = showStep;
        showStep(initialStep);
    }
});
