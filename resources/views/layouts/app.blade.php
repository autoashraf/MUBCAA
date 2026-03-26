<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Membership Site' }}</title>
        <meta name="description" content="{{ $description ?? 'Membership association website built with Laravel.' }}">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
        @endif
    </head>
    <body>
        @php
            $brandName = config('site.brand.name', 'Membership Association');
            $brandTagline = config('site.brand.tagline', 'Membership, events, committees, and memories');
            $logoPath = config('site.brand.logo_path');
            $memberLanding = auth()->check() && ! auth()->user()->isAdmin() && auth()->user()->profile && ! auth()->user()->hasCompletedContactVerification()
                ? route('member.verification.show')
                : route('member.dashboard');
            $mobileProfileLink = auth()->check()
                ? (auth()->user()->isAdmin() ? route('admin.dashboard') : $memberLanding)
                : route('login');
            $mobileProfileLabel = auth()->check()
                ? (auth()->user()->isAdmin() ? 'Open admin dashboard' : 'Open member area')
                : 'Open login page';
        @endphp
        <div class="page-shell">
            <div class="site-backdrop" aria-hidden="true">
                <div class="backdrop-orb backdrop-orb-one"></div>
                <div class="backdrop-orb backdrop-orb-two"></div>
                <div class="backdrop-grid"></div>
            </div>

            <header class="site-header">
                <div class="wrap header-bar">
                    <a class="brand brand-desktop" href="{{ route('home') }}">
                        @if ($logoPath)
                            <span class="brand-logo">
                                <img src="{{ asset($logoPath) }}" alt="{{ $brandName }} logo">
                            </span>
                        @else
                            <span class="brand-mark">M</span>
                        @endif
                        <span>
                            <strong>{{ $brandName }}</strong>
                            <small>{{ $brandTagline }}</small>
                        </span>
                    </a>
                    <div class="nav-shell">
                        <input id="nav-toggle" class="nav-toggle" type="checkbox">
                        <label class="nav-button" for="nav-toggle">
                            <span class="nav-button-icon" aria-hidden="true">
                                <i></i>
                                <i></i>
                                <i></i>
                            </span>
                            <span>Menu</span>
                        </label>
                        <a class="brand brand-mobile" href="{{ route('home') }}">
                            <strong>{{ $brandName }}</strong>
                        </a>
                        <div class="mobile-quick-actions">
                            <a class="mobile-quick-link" href="{{ $mobileProfileLink }}" aria-label="{{ $mobileProfileLabel }}">
                                @auth
                                    <span class="mobile-user-initial">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                @else
                                    <span class="mobile-icon mobile-icon-user"></span>
                                @endauth
                            </a>
                        </div>
                        <nav class="site-nav">
                            @foreach ($menu as $item)
                                <div class="nav-group @if ($item['children']) has-children @endif" @if ($item['children']) data-mobile-nav-group @endif>
                                    @if ($item['children'])
                                        <button
                                            class="nav-link nav-toggle-link @if (request()->routeIs($item['active'])) is-active @endif"
                                            type="button"
                                            data-mobile-nav-trigger
                                            aria-expanded="@if (request()->routeIs($item['active'])) true @else false @endif"
                                        >
                                            {{ $item['label'] }}
                                        </button>
                                    @else
                                        <a href="{{ $item['route'] }}" class="nav-link @if (request()->routeIs($item['active'])) is-active @endif">
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                    @if ($item['children'])
                                        <div class="nav-dropdown @if (request()->routeIs($item['active'])) is-open @endif" data-mobile-nav-panel>
                                            @foreach ($item['children'] as $child)
                                                <a href="{{ $child['route'] }}">{{ $child['label'] }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </nav>
                    </div>
                    <div class="header-actions">
                        @auth
                            <details class="profile-tray">
                                <summary class="profile-tray-toggle" aria-label="Open profile menu">
                                    <span class="profile-tray-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                </summary>
                                <div class="profile-tray-menu">
                                    <div class="profile-tray-head">
                                        <strong>{{ auth()->user()->name }}</strong>
                                        <span>{{ auth()->user()->isAdmin() ? 'Admin' : 'Member' }}</span>
                                    </div>
                                    @unless (auth()->user()->isAdmin())
                                        <a class="profile-tray-link" href="{{ route('member.profile') }}">Profile</a>
                                    @endunless
                                    <a class="profile-tray-link" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : $memberLanding }}">
                                        {{ auth()->user()->isAdmin() ? 'Admin Dashboard' : 'Dashboard' }}
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="profile-tray-link profile-tray-button" type="submit">Logout</button>
                                    </form>
                                </div>
                            </details>
                        @else
                            <a class="mini-link" href="{{ route('login') }}">Login</a>
                            <a class="mini-link mini-link-primary" href="{{ route('membership.apply') }}">Join Us</a>
                        @endauth
                    </div>
                </div>
            </header>

            <main>
                @yield('content')
            </main>

            <footer class="site-footer">
                <div class="wrap footer-bar">
                    <div class="footer-column">
                        <h3 class="footer-title">Stay Connected</h3>
                        <p class="footer-copy">Subscribe to our newsletter for updates.</p>
                        <form class="footer-subscribe" action="{{ route('contact') }}" method="get">
                            <input type="email" name="footer_email" placeholder="Your Email">
                            <button type="submit">Subscribe</button>
                        </form>
                    </div>
                    <div class="footer-column">
                        <h3 class="footer-title">Quick Links</h3>
                        <nav class="footer-list">
                            <a href="{{ route('about.mission') }}">About Us</a>
                            <a href="{{ route('events.upcoming') }}">Events</a>
                            <a href="{{ route('memories.list') }}">News</a>
                            <a href="{{ route('contact') }}">Contact</a>
                        </nav>
                    </div>
                    <div class="footer-column">
                        <h3 class="footer-title">Resources</h3>
                        <div class="footer-list">
                            <span>Mentorship Program</span>
                            <span>Career Support</span>
                            <span>Volunteer Opportunities</span>
                        </div>
                    </div>
                    <div class="footer-column">
                        <h3 class="footer-title">Contact Us</h3>
                        <div class="footer-list footer-contact-list">
                            <span>Email: info@mubcaa.org</span>
                            <span>Phone: +880 1773-658804</span>
                        </div>
                    </div>
                </div>
                <div class="wrap footer-bottom-bar">
                    <p>&copy; {{ now()->year }} {{ $brandName }}</p>
                    <p>All rights reserved.</p>
                </div>
            </footer>
        </div>
        @if (! file_exists(public_path('build/manifest.json')) && ! file_exists(public_path('hot')))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var verificationModalRoot = document.querySelector('[data-verification-modal-root]');
                    var panelRoot = document.querySelector('[data-admin-panels]');
                    var triggers = document.querySelectorAll('[data-admin-panel-trigger]');
                    var sliderRoot = document.querySelector('[data-home-slider]');
                    var slides = document.querySelectorAll('[data-home-slide]');
                    var dots = document.querySelectorAll('[data-home-slider-dot]');
                    var mobileNavGroups = document.querySelectorAll('[data-mobile-nav-group]');
                    var wizardRoot;

                    function updateWizardSummary(summary) {
                        if (!summary) {
                            return;
                        }

                        var statusPill = document.querySelector('[data-wizard-status-pill]');
                        var completionChip = document.querySelector('[data-wizard-completion-chip]');
                        var stepLabel = document.querySelector('[data-wizard-step-label]');

                        if (statusPill) {
                            statusPill.className = 'status-pill status-' + summary.status;
                            statusPill.textContent = summary.status_label;
                        }

                        if (completionChip) {
                            completionChip.textContent = 'Profile ' + summary.completion + '% complete';
                        }

                        if (stepLabel) {
                            stepLabel.textContent = 'Step ' + summary.step + ' of 10';
                        }
                    }

                    function mountOtpGroups(root) {
                        (root || document).querySelectorAll('[data-otp-group]').forEach(function (group) {
                            var form = group.closest('form');
                            var hiddenInput = form ? form.querySelector('input[name="code"]') : null;
                            var digits = Array.from(group.querySelectorAll('.otp-digit'));

                            if (!form || !hiddenInput || !digits.length || group.dataset.otpMounted === 'true') {
                                return;
                            }

                            function syncHidden() {
                                hiddenInput.value = digits.map(function (input) {
                                    return input.value;
                                }).join('');

                                digits.forEach(function (input) {
                                    input.classList.toggle('is-filled', input.value !== '');
                                });
                            }

                            digits.forEach(function (input, index) {
                                input.addEventListener('input', function () {
                                    input.value = input.value.replace(/\D/g, '').slice(0, 1);
                                    syncHidden();

                                    if (input.value && digits[index + 1]) {
                                        digits[index + 1].focus();
                                    }
                                });

                                input.addEventListener('keydown', function (event) {
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

                                input.addEventListener('paste', function (event) {
                                    event.preventDefault();

                                    var pasted = ((event.clipboardData && event.clipboardData.getData('text')) || '')
                                        .replace(/\D/g, '')
                                        .slice(0, digits.length);

                                    if (!pasted) {
                                        return;
                                    }

                                    digits.forEach(function (digitInput, digitIndex) {
                                        digitInput.value = pasted[digitIndex] || '';
                                    });

                                    syncHidden();
                                    digits[Math.min(pasted.length, digits.length) - 1].focus();
                                });
                            });

                            form.addEventListener('submit', syncHidden);
                            group.dataset.otpMounted = 'true';
                            syncHidden();
                        });
                    }

                    function mountImagePreviews(root) {
                        function placeholderText(targetName) {
                            return targetName === 'cover_photo' ? 'Cover Preview' : 'Photo Preview';
                        }

                        (root || document).querySelectorAll('[data-image-upload-trigger]').forEach(function (button) {
                            if (button.dataset.triggerMounted === 'true') {
                                return;
                            }

                            button.addEventListener('click', function () {
                                var input = document.getElementById(button.dataset.imageUploadTrigger);

                                if (input) {
                                    input.click();
                                }
                            });

                            button.dataset.triggerMounted = 'true';
                        });

                        (root || document).querySelectorAll('[data-image-preview-input]').forEach(function (input) {
                            if (input.dataset.previewMounted === 'true') {
                                return;
                            }

                            input.addEventListener('change', function () {
                                var targetName = input.dataset.imagePreviewInput;
                                var target = document.querySelector('[data-image-preview-target="' + targetName + '"]');
                                var removeInput = document.querySelector('[data-remove-image-input="' + targetName + '"]');
                                var removeButton = document.querySelector('[data-remove-image-button="' + targetName + '"]');
                                var file = input.files && input.files[0];

                                if (!target || !file || !file.type.startsWith('image/')) {
                                    return;
                                }

                                var reader = new FileReader();

                                reader.onload = function (event) {
                                    if (target.tagName === 'IMG') {
                                        target.src = event.target.result;
                                    } else {
                                        var image = document.createElement('img');
                                        image.src = event.target.result;
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

                        (root || document).querySelectorAll('[data-remove-image-button]').forEach(function (button) {
                            if (button.dataset.removeMounted === 'true') {
                                return;
                            }

                            button.addEventListener('click', function () {
                                var targetName = button.dataset.removeImageButton;
                                var hiddenInput = document.querySelector('[data-remove-image-input="' + targetName + '"]');
                                var target = document.querySelector('[data-image-preview-target="' + targetName + '"]');
                                var fileInput = document.querySelector('[data-image-preview-input="' + targetName + '"]');

                                if (hiddenInput) {
                                    hiddenInput.value = '1';
                                }

                                if (fileInput) {
                                    fileInput.value = '';
                                }

                                if (target) {
                                    var placeholder = document.createElement('span');
                                    placeholder.className = 'image-preview-placeholder';
                                    placeholder.setAttribute('data-image-preview-target', targetName);
                                    placeholder.textContent = placeholderText(targetName);
                                    target.replaceWith(placeholder);
                                }

                                button.classList.add('is-hidden');
                            });

                            button.dataset.removeMounted = 'true';
                        });
                    }

                    function clearFormErrors(form) {
                        form.querySelectorAll('.ajax-error').forEach(function (node) {
                            node.remove();
                        });

                        form.querySelectorAll('.is-invalid').forEach(function (node) {
                            node.classList.remove('is-invalid');
                        });
                    }

                    function applyFormErrors(form, errors) {
                        Object.entries(errors || {}).forEach(function (entry) {
                            var field = entry[0];
                            var messages = entry[1];
                            var input = form.querySelector('[name="' + field + '"]');

                            if (! input) {
                                return;
                            }

                            input.classList.add('is-invalid');

                            var error = document.createElement('small');
                            error.className = 'ajax-error';
                            error.textContent = Array.isArray(messages) ? messages[0] : messages;
                            input.insertAdjacentElement('afterend', error);
                        });
                    }

                    function showFormMessage(form, message, className) {
                        form.querySelectorAll('.ajax-form-alert').forEach(function (node) {
                            node.remove();
                        });

                        if (! message) {
                            return;
                        }

                        var alert = document.createElement('div');
                        alert.className = (className || 'alert-success') + ' ajax-form-alert';
                        alert.textContent = message;
                        form.insertAdjacentElement('afterbegin', alert);
                    }

                    document.addEventListener('submit', function (event) {
                        var form = event.target.closest('form[data-ajax-form]');

                        if (! form) {
                            return;
                        }

                        event.preventDefault();
                        clearFormErrors(form);

                        var submitter = event.submitter;

                        if (submitter) {
                            submitter.disabled = true;
                        }

                        var formData = new FormData(form);

                        if (submitter && submitter.name && ! formData.has(submitter.name)) {
                            formData.append(submitter.name, submitter.value || '1');
                        }

                        fetch(form.action, {
                            method: form.method || 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData,
                            credentials: 'same-origin'
                        })
                            .then(function (response) {
                                return response.json()
                                    .catch(function () { return {}; })
                                    .then(function (payload) { return { response: response, payload: payload }; });
                            })
                            .then(function (result) {
                                var response = result.response;
                                var payload = result.payload;

                                if (! response.ok) {
                                    if (response.status === 422) {
                                        applyFormErrors(form, payload.errors || {});
                                        showFormMessage(form, payload.message || 'Please review the highlighted fields.', 'alert-success alert-warning-like');
                                        return;
                                    }

                                    showFormMessage(form, payload.message || 'Something went wrong. Please try again.', 'alert-success alert-warning-like');
                                    return;
                                }

                                var mode = form.dataset.ajaxForm;

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

                                    if (payload.step_url) {
                                        window.history.replaceState({}, '', payload.step_url);
                                    }

                                    updateWizardSummary(payload.summary);

                                    if (payload.next_step && wizardRoot && wizardRoot.__showStep) {
                                        wizardRoot.__showStep(Number(payload.next_step));
                                    }
                                }
                            })
                            .catch(function () {
                                showFormMessage(form, 'Something went wrong. Please try again.', 'alert-success alert-warning-like');
                            })
                            .finally(function () {
                                if (submitter) {
                                    submitter.disabled = false;
                                }
                            });
                    });

                    mobileNavGroups.forEach(function (group) {
                        var trigger = group.querySelector('[data-mobile-nav-trigger]');
                        var panel = group.querySelector('[data-mobile-nav-panel]');

                        if (!trigger || !panel) {
                            return;
                        }

                        trigger.addEventListener('click', function () {
                            var willOpen = !panel.classList.contains('is-open');

                            mobileNavGroups.forEach(function (item) {
                                item.querySelector('[data-mobile-nav-panel]')?.classList.remove('is-open');
                                var itemTrigger = item.querySelector('[data-mobile-nav-trigger]');

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

                    mountOtpGroups(document);
                    mountImagePreviews(document);

                    if (panelRoot && triggers.length) {
                        var panels = panelRoot.querySelectorAll('[data-admin-panel]');

                        function setActivePanel(target) {
                            panels.forEach(function (panel) {
                                panel.classList.toggle('is-active', panel.dataset.adminPanel === target);
                            });

                            triggers.forEach(function (trigger) {
                                trigger.classList.toggle('is-active', trigger.dataset.adminPanelTrigger === target);
                            });
                        }

                        triggers.forEach(function (trigger) {
                            trigger.addEventListener('click', function () {
                                setActivePanel(trigger.dataset.adminPanelTrigger);
                            });
                        });
                    }

                    if (sliderRoot && slides.length >= 2) {
                        var activeIndex = 0;
                        var timerId = null;

                        function showSlide(index) {
                            activeIndex = index;

                            slides.forEach(function (slide, slideIndex) {
                                slide.classList.toggle('is-active', slideIndex === index);
                            });

                            dots.forEach(function (dot, dotIndex) {
                                dot.classList.toggle('is-active', dotIndex === index);
                            });
                        }

                        function startSlider() {
                            timerId = window.setInterval(function () {
                                showSlide((activeIndex + 1) % slides.length);
                            }, 5000);
                        }

                        function resetSlider() {
                            if (timerId) {
                                window.clearInterval(timerId);
                            }

                            startSlider();
                        }

                        dots.forEach(function (dot, index) {
                            dot.addEventListener('click', function () {
                                showSlide(index);
                                resetSlider();
                            });
                        });

                        sliderRoot.addEventListener('mouseenter', function () {
                            if (timerId) {
                                window.clearInterval(timerId);
                            }
                        });

                        sliderRoot.addEventListener('mouseleave', function () {
                            resetSlider();
                        });

                        showSlide(0);
                        startSlider();
                    }

                    wizardRoot = document.querySelector('[data-profile-wizard]');

                    if (wizardRoot) {
                        var stepInputs = wizardRoot.querySelectorAll('input[name="wizard_step"]');
                        var stepLabel = document.querySelector('[data-wizard-step-label]');
                        var stepItems = wizardRoot.querySelectorAll('[data-wizard-step-item]');
                        var panels = wizardRoot.querySelectorAll('[data-wizard-panel]');
                        var triggers = wizardRoot.querySelectorAll('[data-step-target]');
                        var initialStep = Number(wizardRoot.dataset.initialStep || 2);

                        function showWizardStep(step) {
                            stepInputs.forEach(function (input) {
                                input.disabled = Number(input.value) !== step;
                            });

                            panels.forEach(function (panel) {
                                var isActive = Number(panel.dataset.wizardPanel) === step;

                                panel.classList.toggle('is-active', isActive);

                                panel.querySelectorAll('input, select, textarea, button').forEach(function (control) {
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

                            stepItems.forEach(function (item) {
                                item.classList.toggle('is-active', Number(item.dataset.wizardStepItem) === step);
                            });

                            if (stepLabel) {
                                stepLabel.textContent = 'Step ' + step + ' of 10';
                            }

                            window.history.replaceState({}, '', '/dashboard/profile/complete/' + step);
                        }

                        triggers.forEach(function (trigger) {
                            trigger.addEventListener('click', function () {
                                showWizardStep(Number(trigger.dataset.stepTarget));
                            });
                        });

                        wizardRoot.__showStep = showWizardStep;
                        showWizardStep(initialStep);
                    }
                });
            </script>
        @endif
    </body>
</html>
