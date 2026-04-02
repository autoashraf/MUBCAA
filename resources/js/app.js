import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const verificationModalRoot = document.querySelector('[data-verification-modal-root]');
    const closeVerificationModal = (closeUrl) => {
        if (!verificationModalRoot) {
            if (closeUrl) {
                window.location.href = closeUrl;
            }

            return;
        }

        verificationModalRoot.innerHTML = '';

        if (closeUrl) {
            window.history.replaceState({}, '', closeUrl);
        }
    };
    const updateWizardSummary = (summary) => {
        if (!summary) {
            return;
        }

        const statusPill = document.querySelector('[data-wizard-status-pill]');
        const completionChip = document.querySelector('[data-wizard-completion-chip]');
        const stepLabel = document.querySelector('[data-wizard-step-label]');

        if (statusPill) {
            statusPill.className = `status-pill status-${summary.status}`;
            statusPill.textContent = summary.status_label;
        }

        if (completionChip) {
            completionChip.textContent = `Profile ${summary.completion}% complete`;
        }

        if (stepLabel) {
            stepLabel.textContent = `Step ${summary.step} of 10`;
        }
    };

    const updateDraftButtons = (stepCompletion) => {
        if (!stepCompletion) {
            return;
        }

        Object.entries(stepCompletion).forEach(([step, isComplete]) => {
            const button = document.querySelector(`[data-draft-action-step="${step}"]`);

            if (!button) {
                return;
            }

            button.hidden = Boolean(isComplete);
        });
    };

    const mountImagePreviews = (root = document) => {
        const placeholderText = (targetName) => targetName === 'cover_photo' ? 'Cover Preview' : 'Photo Preview';

        root.querySelectorAll('[data-image-upload-trigger]').forEach((button) => {
            if (button.dataset.triggerMounted === 'true') {
                return;
            }

            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.imageUploadTrigger);

                input?.click();
            });

            button.dataset.triggerMounted = 'true';
        });

        root.querySelectorAll('[data-image-preview-input]').forEach((input) => {
            if (input.dataset.previewMounted === 'true') {
                return;
            }

            input.addEventListener('change', () => {
                const targetName = input.dataset.imagePreviewInput;
                const target = document.querySelector(`[data-image-preview-target="${targetName}"]`);
                const removeInput = document.querySelector(`[data-remove-image-input="${targetName}"]`);
                const removeButton = document.querySelector(`[data-remove-image-button="${targetName}"]`);
                const file = input.files?.[0];

                if (!target || !file || !file.type.startsWith('image/')) {
                    return;
                }

                const reader = new FileReader();

                reader.onload = (event) => {
                    if (target.tagName === 'IMG') {
                        target.src = event.target?.result;
                    } else {
                        const image = document.createElement('img');
                        image.src = event.target?.result;
                        image.alt = 'Selected preview image';
                        image.setAttribute('data-image-preview-target', targetName);
                        target.replaceWith(image);
                    }

                    if (removeInput) {
                        removeInput.value = '0';
                    }

                    if (removeButton) {
                        removeButton.classList.remove('is-hidden');
                    }
                };

                reader.readAsDataURL(file);
            });

            input.dataset.previewMounted = 'true';
        });

        root.querySelectorAll('[data-remove-image-button]').forEach((button) => {
            if (button.dataset.removeMounted === 'true') {
                return;
            }

            button.addEventListener('click', () => {
                const targetName = button.dataset.removeImageButton;
                const hiddenInput = document.querySelector(`[data-remove-image-input="${targetName}"]`);
                const target = document.querySelector(`[data-image-preview-target="${targetName}"]`);
                const fileInput = document.querySelector(`[data-image-preview-input="${targetName}"]`);

                if (hiddenInput) {
                    hiddenInput.value = '1';
                }

                if (fileInput) {
                    fileInput.value = '';
                }

                if (target) {
                    const placeholder = document.createElement('span');
                    placeholder.className = 'image-preview-placeholder';
                    placeholder.setAttribute('data-image-preview-target', targetName);
                    placeholder.textContent = placeholderText(targetName);
                    target.replaceWith(placeholder);
                }

                button.classList.add('is-hidden');
            });

            button.dataset.removeMounted = 'true';
        });
    };

    const mountWhatsappSync = (root = document) => {
        root.querySelectorAll('[data-whatsapp-same-toggle]').forEach((toggle) => {
            if (toggle.dataset.whatsappMounted === 'true') {
                return;
            }

            const wrapper = toggle.closest('[data-whatsapp-sync-wrapper]');
            const primary = toggle.closest('form')?.querySelector(`[name="${toggle.dataset.primarySource}"]`);
            const whatsapp = toggle.closest('form')?.querySelector(`[name="${toggle.dataset.whatsappTarget}"]`);

            if (!primary || !whatsapp) {
                return;
            }

            const syncState = () => {
                const note = wrapper?.querySelector('[data-whatsapp-sync-note]');

                if (toggle.checked) {
                    whatsapp.value = primary.value;
                    whatsapp.readOnly = true;
                    wrapper?.classList.add('is-active');
                    if (note) {
                        note.hidden = false;
                    }
                } else {
                    whatsapp.readOnly = false;
                    wrapper?.classList.remove('is-active');
                    if (note) {
                        note.hidden = true;
                    }
                }
            };

            wrapper?.addEventListener('click', (event) => {
                if (event.target === toggle) {
                    return;
                }

                toggle.checked = !toggle.checked;
                toggle.dispatchEvent(new Event('change', { bubbles: true }));
            });

            wrapper?.addEventListener('keydown', (event) => {
                if (event.key !== ' ' && event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                toggle.checked = !toggle.checked;
                toggle.dispatchEvent(new Event('change', { bubbles: true }));
            });

            toggle.addEventListener('change', syncState);
            primary.addEventListener('input', () => {
                if (toggle.checked) {
                    whatsapp.value = primary.value;
                }
            });

            if (wrapper && !wrapper.hasAttribute('tabindex')) {
                wrapper.tabIndex = 0;
            }

            syncState();
            toggle.dataset.whatsappMounted = 'true';
        });
    };

    const mountCountryCodeDropdowns = (root = document) => {
        root.querySelectorAll('[data-country-code-dropdown]').forEach((dropdown) => {
            if (dropdown.dataset.countryCodeMounted === 'true') {
                return;
            }

            const trigger = dropdown.querySelector('[data-country-code-trigger]');
            const valueInput = dropdown.querySelector('[data-country-code-value]');
            const label = dropdown.querySelector('[data-country-code-label]');
            const panel = dropdown.querySelector('[data-country-code-panel]');
            const options = dropdown.querySelectorAll('[data-country-code-option]');
            let searchInput = null;
            let emptyState = null;

            const ensureSearchUi = () => {
                if (!panel || searchInput) {
                    return;
                }

                const searchWrap = document.createElement('div');
                searchWrap.className = 'country-code-search-wrap';

                searchInput = document.createElement('input');
                searchInput.type = 'search';
                searchInput.className = 'country-code-search-input';
                searchInput.placeholder = 'Search country or code';
                searchInput.setAttribute('autocomplete', 'off');
                searchInput.setAttribute('data-country-code-search', '');

                emptyState = document.createElement('div');
                emptyState.className = 'country-code-empty-state';
                emptyState.textContent = 'No matching country found';
                emptyState.hidden = true;

                searchWrap.appendChild(searchInput);
                panel.prepend(emptyState);
                panel.prepend(searchWrap);

                searchInput.addEventListener('input', () => {
                    filterOptions(searchInput.value);
                });

                searchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        setOpenState(false);
                        trigger?.focus();
                    }
                });
            };

            const setOpenState = (isOpen) => {
                dropdown.classList.toggle('is-open', isOpen);

                if (trigger) {
                    trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                }

                if (isOpen) {
                    ensureSearchUi();
                    filterOptions('');
                    window.requestAnimationFrame(() => {
                        searchInput?.focus();
                        scrollToSelectedOption();
                    });
                }
            };

            const syncSelectedState = () => {
                const selectedValue = valueInput?.value || '';

                options.forEach((option) => {
                    option.classList.toggle('is-selected', option.dataset.value === selectedValue);
                    option.setAttribute('aria-selected', option.dataset.value === selectedValue ? 'true' : 'false');
                });

                if (label && selectedValue) {
                    label.textContent = selectedValue;
                }
            };

            const filterOptions = (term) => {
                const normalizedTerm = term.trim().toLowerCase();
                let visibleCount = 0;

                options.forEach((option) => {
                    const haystack = `${option.textContent || ''} ${option.dataset.value || ''}`.toLowerCase();
                    const isVisible = normalizedTerm === '' || haystack.includes(normalizedTerm);
                    option.hidden = !isVisible;

                    if (isVisible) {
                        visibleCount += 1;
                    }
                });

                if (emptyState) {
                    emptyState.hidden = visibleCount > 0;
                }
            };

            const scrollToSelectedOption = () => {
                const selectedValue = valueInput?.value || '';
                const selectedOption = Array.from(options).find((option) => option.dataset.value === selectedValue && !option.hidden)
                    || Array.from(options).find((option) => !option.hidden);

                selectedOption?.scrollIntoView({
                    block: 'nearest',
                });
            };

            document.addEventListener('click', (event) => {
                const clickedDropdown = event.target.closest('[data-country-code-dropdown]');

                if (!clickedDropdown) {
                    setOpenState(false);
                    return;
                }

                if (clickedDropdown !== dropdown) {
                    setOpenState(false);
                    return;
                }

                document.querySelectorAll('[data-country-code-dropdown].is-open').forEach((openDropdown) => {
                    if (openDropdown === dropdown) {
                        return;
                    }

                    openDropdown.classList.remove('is-open');
                    openDropdown.querySelector('[data-country-code-trigger]')?.setAttribute('aria-expanded', 'false');
                });

                window.setTimeout(syncSelectedState, 0);
            });

            dropdown.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    if (document.activeElement === trigger && event.key.length === 1) {
                        event.preventDefault();
                        setOpenState(true);
                        ensureSearchUi();

                        if (searchInput) {
                            searchInput.value = event.key;
                            filterOptions(searchInput.value);
                            window.requestAnimationFrame(() => {
                                searchInput.focus();
                                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
                                scrollToSelectedOption();
                            });
                        }
                    }

                    return;
                }

                setOpenState(false);
                trigger?.focus();
            });

            syncSelectedState();
            dropdown.dataset.countryCodeMounted = 'true';
        });
    };

    const mountCopyButtons = (root = document) => {
        root.querySelectorAll('[data-copy-button]').forEach((button) => {
            if (button.dataset.copyMounted === 'true') {
                return;
            }

            const baseLabel = button.dataset.copyLabel || button.textContent.trim() || 'Copy';

            const resetLabelSoon = (label) => {
                button.textContent = label;
                window.clearTimeout(button._copyTimer);
                button._copyTimer = window.setTimeout(() => {
                    button.textContent = baseLabel;
                }, 1500);
            };

            button.addEventListener('click', async () => {
                const target = document.getElementById(button.dataset.copyTarget || '');
                const value = target?.value || target?.textContent || '';

                if (!value) {
                    resetLabelSoon('No text');
                    return;
                }

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(value);
                    } else {
                        const helper = document.createElement('textarea');
                        helper.value = value;
                        helper.setAttribute('readonly', '');
                        helper.style.position = 'absolute';
                        helper.style.left = '-9999px';
                        document.body.appendChild(helper);
                        helper.select();
                        document.execCommand('copy');
                        helper.remove();
                    }

                    resetLabelSoon('Copied');
                } catch (error) {
                    resetLabelSoon('Copy failed');
                }
            });

            button.dataset.copyMounted = 'true';
        });
    };

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

    const mountResendCountdowns = (root = document) => {
        root.querySelectorAll('[data-resend-button]').forEach((button) => {
            if (button.dataset.resendMounted === 'true') {
                return;
            }

            const baseLabel = button.dataset.resendLabel || button.textContent.trim() || 'Resend code';
            let remaining = Number(button.dataset.resendSeconds || 0);

            const render = () => {
                if (remaining > 0) {
                    button.disabled = true;
                    button.textContent = `Resend in ${remaining}s`;
                    button.classList.add('is-disabled');
                } else {
                    button.disabled = false;
                    button.textContent = baseLabel;
                    button.classList.remove('is-disabled');
                }
            };

            render();

            if (remaining > 0) {
                const timerId = window.setInterval(() => {
                    if (!document.body.contains(button)) {
                        window.clearInterval(timerId);
                        return;
                    }

                    remaining -= 1;
                    button.dataset.resendSeconds = String(Math.max(remaining, 0));
                    render();

                    if (remaining <= 0) {
                        window.clearInterval(timerId);
                    }
                }, 1000);
            }

            button.dataset.resendMounted = 'true';
        });
    };

    const mountExpiryCountdowns = (root = document) => {
        root.querySelectorAll('[data-expiry-countdown]').forEach((node) => {
            if (node.dataset.expiryMounted === 'true') {
                return;
            }

            let remaining = Number(node.dataset.expirySeconds || 0);

            const render = () => {
                if (remaining > 0) {
                    const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                    const seconds = String(remaining % 60).padStart(2, '0');
                    node.textContent = `Code expires in ${minutes}:${seconds}`;
                    node.classList.remove('is-expired');
                } else {
                    node.textContent = 'Code expired. Request a new OTP.';
                    node.classList.add('is-expired');
                }
            };

            render();

            if (remaining > 0) {
                const timerId = window.setInterval(() => {
                    if (!document.body.contains(node)) {
                        window.clearInterval(timerId);
                        return;
                    }

                    remaining -= 1;
                    node.dataset.expirySeconds = String(Math.max(remaining, 0));
                    render();

                    if (remaining <= 0) {
                        window.clearInterval(timerId);
                    }
                }, 1000);
            }

            node.dataset.expiryMounted = 'true';
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

            if (input.matches('[data-registration-check-input]')) {
                const fieldWrapper = input.closest('.join-auth-label') || input.parentElement;
                const status = fieldWrapper?.querySelector('[data-registration-check-status]');

                if (status) {
                    status.hidden = false;
                    status.textContent = error.textContent;
                    status.classList.remove('is-success', 'is-loading');
                    status.classList.add('is-error');
                }
            }
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

    const mountLoginMethodTabs = (root = document) => {
        root.querySelectorAll('[data-login-method-tabs]').forEach((tabRoot) => {
            if (tabRoot.dataset.loginMethodMounted === 'true') {
                return;
            }

            const form = tabRoot.closest('form');
            const channelInput = form?.querySelector('[data-login-channel-input]');
            const tabs = Array.from(tabRoot.querySelectorAll('[data-login-method-tab]'));
            const panels = Array.from(form?.querySelectorAll('[data-login-panel]') || []);

            const showChannel = (channel) => {
                if (!form || !channelInput) {
                    return;
                }

                channelInput.value = channel;

                tabs.forEach((tab) => {
                    const isActive = tab.dataset.loginMethodTab === channel;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    const isActive = panel.dataset.loginPanel === channel;
                    panel.hidden = !isActive;

                    panel.querySelectorAll('input').forEach((input) => {
                        if (input.type !== 'hidden') {
                            input.required = isActive && input.hasAttribute('data-login-identifier-input');
                        }
                    });
                });

                form.querySelectorAll('[data-login-identifier-status]').forEach((status) => {
                    status.hidden = true;
                    status.textContent = '';
                    status.classList.remove('is-success', 'is-error', 'is-loading');
                });
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    showChannel(tab.dataset.loginMethodTab || 'email');
                });
            });

            showChannel(channelInput?.value || 'email');
            tabRoot.dataset.loginMethodMounted = 'true';
        });
    };

    const mountLoginIdentifierCheck = (root = document) => {
        root.querySelectorAll('[data-login-identifier-input]').forEach((input) => {
            if (input.dataset.loginIdentifierMounted === 'true') {
                return;
            }

            const field = input.closest('.login-auth-label');
            const form = input.closest('form');
            const status = field?.querySelector('[data-login-identifier-status]');
            const checkUrl = input.dataset.loginCheckUrl;
            const countryCodeInput = field?.querySelector('[data-login-country-code-input]') || form?.querySelector('[data-login-country-code-input]');
            const channelInput = form?.querySelector('[data-login-channel-input]');
            const inputChannel = input.dataset.loginChannel || 'email';
            let timeoutId = null;
            let requestId = 0;

            const renderStatus = (message, type) => {
                if (!status) {
                    return;
                }

                if (!message) {
                    status.hidden = true;
                    status.textContent = '';
                    status.classList.remove('is-success', 'is-error', 'is-loading');
                    return;
                }

                status.hidden = false;
                status.textContent = message;
                status.classList.remove('is-success', 'is-error', 'is-loading');
                status.classList.add(`is-${type}`);
            };

            const runCheck = async () => {
                if ((channelInput?.value || 'email') !== inputChannel) {
                    renderStatus('', 'success');
                    return;
                }

                const identifier = input.value.trim();

                if (!identifier || identifier.length < 5 || !checkUrl) {
                    renderStatus('', 'success');
                    return;
                }

                const currentRequest = ++requestId;
                renderStatus('Checking account...', 'loading');

                try {
                    const formData = new FormData();
                    formData.append('login_channel', inputChannel);
                    formData.append('identifier', identifier);
                    formData.append('identifier_country_code', countryCodeInput?.value || '+880');
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '');

                    const response = await fetch(checkUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                        credentials: 'same-origin',
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (currentRequest !== requestId) {
                        return;
                    }

                    renderStatus(payload.exists ? '' : (payload.message || 'Unable to verify this account.'), payload.exists ? 'success' : 'error');
                } catch (error) {
                    if (currentRequest !== requestId) {
                        return;
                    }

                    renderStatus('Unable to verify right now. You can still try OTP login.', 'error');
                }
            };

            input.addEventListener('input', () => {
                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(runCheck, 350);
            });

            input.addEventListener('blur', runCheck);
            countryCodeInput?.addEventListener('change', () => {
                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(runCheck, 50);
            });
            channelInput?.addEventListener('change', runCheck);
            input.dataset.loginIdentifierMounted = 'true';
        });
    };

    const mountRegistrationFieldChecks = (root = document) => {
        root.querySelectorAll('[data-registration-check-input]').forEach((input) => {
            if (input.dataset.registrationCheckMounted === 'true') {
                return;
            }

            const fieldWrapper = input.closest('.join-auth-label') || input.parentElement;
            const status = fieldWrapper?.querySelector('[data-registration-check-status]');
            const checkUrl = input.dataset.registrationCheckUrl;
            const field = input.dataset.registrationCheckField;
            const mobileCountryCodeInput = input.form?.querySelector('[name="mobile_country_code"]');
            let timeoutId = null;
            let requestId = 0;

            const renderStatus = (message, type) => {
                if (!status) {
                    return;
                }

                if (!message) {
                    status.hidden = true;
                    status.textContent = '';
                    status.classList.remove('is-success', 'is-error', 'is-loading');
                    return;
                }

                status.hidden = false;
                status.textContent = message;
                status.classList.remove('is-success', 'is-error', 'is-loading');
                status.classList.add(`is-${type}`);
            };

            const runCheck = async () => {
                const value = input.value.trim();
                const mobileCountryCode = input.form?.querySelector('[name="mobile_country_code"]')?.value?.trim() || '+880';
                const contextMobileNumber = input.form?.querySelector('[name="mobile_number"]')?.value?.trim() || '';
                const mobileMinLength = mobileCountryCode === '+880' ? 10 : 4;

                if ((input.matches('[data-referral-code-input]') && input.hidden) || !value || !checkUrl || !field) {
                    renderStatus('', 'success');
                    return;
                }

                if ((field === 'email' && value.length < 5) || (field === 'mobile_number' && value.length < mobileMinLength) || (field === 'referral_code' && value.length < 4)) {
                    renderStatus('', 'success');
                    return;
                }

                const currentRequest = ++requestId;
                renderStatus('Checking...', 'loading');

                try {
                    const formData = new FormData();
                    formData.append('field', field);
                    formData.append('value', value);
                    formData.append('mobile_country_code', mobileCountryCode);
                    formData.append('context_email', input.form?.querySelector('[name="email"]')?.value?.trim() || '');
                    formData.append('context_mobile_number', contextMobileNumber);
                    formData.append('context_mobile_country_code', mobileCountryCode);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '');

                    const response = await fetch(checkUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                        credentials: 'same-origin',
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (currentRequest !== requestId) {
                        return;
                    }

                    if ((field === 'email' || field === 'mobile_number') && payload.valid) {
                        renderStatus('', 'success');
                        return;
                    }

                    renderStatus(payload.message || 'Unable to verify right now.', payload.type || (payload.valid ? 'success' : 'error'));
                } catch (error) {
                    if (currentRequest !== requestId) {
                        return;
                    }

                    renderStatus('Unable to verify right now.', 'error');
                }
            };

            input.addEventListener('input', () => {
                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(runCheck, 350);
            });

            input.addEventListener('blur', runCheck);
            if (field === 'mobile_number' && mobileCountryCodeInput) {
                mobileCountryCodeInput.addEventListener('change', () => {
                    window.clearTimeout(timeoutId);
                    timeoutId = window.setTimeout(runCheck, 50);
                });
            }
            input.dataset.registrationCheckMounted = 'true';
        });
    };

    const mountDiscoverySourceToggle = (root = document) => {
        root.querySelectorAll('[data-discovery-source-select]').forEach((select) => {
            if (select.dataset.discoveryMounted === 'true') {
                return;
            }

            const wrapper = select.closest('[data-discovery-field-wrapper]');
            const input = wrapper?.querySelector('[data-referral-code-input]');
            const status = wrapper?.querySelector('[data-registration-check-status]');
            const label = wrapper?.querySelector('[data-discovery-field-label]');
            const reset = wrapper?.querySelector('[data-referral-code-reset]');

            const sync = () => {
                const isReferral = select.value === 'Referral Code';

                if (!wrapper || !input) {
                    return;
                }

                select.hidden = isReferral;
                input.hidden = !isReferral;
                reset && (reset.hidden = !isReferral);
                if (label) {
                    label.textContent = isReferral ? 'Referral Code' : 'How did you find us?';
                }

                if (!isReferral) {
                    if (status) {
                        status.hidden = true;
                        status.textContent = '';
                        status.classList.remove('is-success', 'is-error', 'is-loading');
                    }

                    input.value = '';
                } else {
                    window.setTimeout(() => input.focus(), 0);
                }
            };

            select.addEventListener('change', sync);
            reset?.addEventListener('click', () => {
                select.hidden = false;
                select.value = '';
                sync();
                select.focus();
            });
            sync();
            select.dataset.discoveryMounted = 'true';
        });
    };

    const showSiteFlash = (message, type = 'success') => {
        if (!message) {
            return;
        }

        let stack = document.querySelector('.site-flash-stack');

        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'site-flash-stack';
            stack.setAttribute('aria-live', 'polite');
            stack.setAttribute('aria-atomic', 'true');
            document.body.appendChild(stack);
        }

        const flash = document.createElement('div');
        flash.className = `site-flash site-flash-${type}`;
        flash.dataset.siteFlash = 'true';

        const copy = document.createElement('div');
        copy.className = 'site-flash-copy';

        const title = document.createElement('strong');
        title.textContent = type === 'error' ? 'Error' : 'Success';

        const body = document.createElement('span');
        body.textContent = message;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'site-flash-close';
        close.dataset.siteFlashClose = 'true';
        close.setAttribute('aria-label', 'Close message');
        close.textContent = '×';

        copy.append(title, body);
        flash.append(copy, close);
        stack.appendChild(flash);

        window.setTimeout(() => {
            flash.remove();

            if (!stack.children.length) {
                stack.remove();
            }
        }, 4000);
    };

    document.addEventListener('click', (event) => {
        const closeButton = event.target.closest('[data-site-flash-close]');

        if (!closeButton) {
            return;
        }

        const flash = closeButton.closest('[data-site-flash]');
        const stack = flash?.closest('.site-flash-stack');

        flash?.remove();

        if (stack && !stack.children.length) {
            stack.remove();
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-ajax-form]');

        if (!form) {
            return;
        }

        event.preventDefault();
        clearFormErrors(form);

        const submitter = event.submitter;
        const submitterLabel = submitter?.querySelector('.button-loading-label');
        const originalSubmitterLabel = submitterLabel?.textContent || '';

        if (submitter) {
            submitter.disabled = true;
            submitter.classList.add('is-loading');

            if (submitterLabel && submitter.dataset.loadingText) {
                submitterLabel.textContent = submitter.dataset.loadingText;
            }
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
                    showSiteFlash('Please review the highlighted fields.', 'error');
                    return;
                }

                showSiteFlash(payload.message || 'Something went wrong. Please try again.', 'error');
                showFormMessage(form, payload.message || 'Something went wrong. Please try again.', 'alert-success alert-warning-like');
                return;
            }

            const mode = form.dataset.ajaxForm;

            if (mode === 'registration' && verificationModalRoot && payload.modal_html) {
                verificationModalRoot.innerHTML = payload.modal_html;
                mountOtpGroups(verificationModalRoot);
                mountResendCountdowns(verificationModalRoot);
                mountExpiryCountdowns(verificationModalRoot);
                return;
            }

            if (mode === 'verification' && verificationModalRoot && payload.modal_html) {
                verificationModalRoot.innerHTML = payload.modal_html;
                mountOtpGroups(verificationModalRoot);
                mountResendCountdowns(verificationModalRoot);
                mountExpiryCountdowns(verificationModalRoot);
                if (!payload.completed) {
                    showSiteFlash(payload.message, payload.flash_type || 'success');
                }
                return;
            }

            if (mode === 'verification' && payload.completed && payload.continue_url) {
                window.location.href = payload.continue_url;
                return;
            }

            if (mode === 'wizard') {
                showSiteFlash(payload.message, 'success');

                if (payload.submitted && payload.redirect_url) {
                    window.location.href = payload.redirect_url;
                    return;
                }

                if (payload.step_url) {
                    window.history.replaceState({}, '', payload.step_url);
                }

                updateWizardSummary(payload.summary);
                updateDraftButtons(payload.step_completion);

                if (payload.next_step && wizardRoot?.__showStep) {
                    wizardRoot.__showStep(Number(payload.next_step));
                }
            }
        } catch (error) {
            showSiteFlash('Something went wrong. Please try again.', 'error');
            showFormMessage(form, 'Something went wrong. Please try again.', 'alert-success alert-warning-like');
        } finally {
            if (submitter) {
                submitter.disabled = false;
                submitter.classList.remove('is-loading');

                if (submitterLabel && originalSubmitterLabel) {
                    submitterLabel.textContent = originalSubmitterLabel;
                }
            }
        }
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-close-verification-modal]');

        if (!trigger) {
            return;
        }

        event.preventDefault();
        closeVerificationModal(trigger.getAttribute('href'));
    });

    const mobileNavGroups = document.querySelectorAll('[data-mobile-nav-group]');

    mountOtpGroups();
    mountResendCountdowns();
    mountExpiryCountdowns();
    mountImagePreviews();
    mountWhatsappSync();
    mountCountryCodeDropdowns();
    mountCopyButtons();
    mountLoginMethodTabs();
    mountLoginIdentifierCheck();
    mountRegistrationFieldChecks();
    mountDiscoverySourceToggle();

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
        const stepLabel = document.querySelector('[data-wizard-step-label]');
        const stepItems = wizardRoot.querySelectorAll('[data-wizard-step-item]');
        const panels = wizardRoot.querySelectorAll('[data-wizard-panel]');
        const triggers = wizardRoot.querySelectorAll('[data-step-target]');
        const initialStep = Number(wizardRoot.dataset.initialStep || 2);
        const stepBaseUrl = wizardRoot.dataset.stepBaseUrl || '/membership/profile-completion';

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

            window.history.replaceState({}, '', `${stepBaseUrl}/${step}`);
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
