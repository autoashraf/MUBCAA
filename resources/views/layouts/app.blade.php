<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
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
                @include('partials.flash-messages')
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

                    function closeVerificationModal(closeUrl) {
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
                    }

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

                    function updateDraftButtons(stepCompletion) {
                        if (!stepCompletion) {
                            return;
                        }

                        Object.entries(stepCompletion).forEach(function (entry) {
                            var step = entry[0];
                            var isComplete = entry[1];
                            var button = document.querySelector('[data-draft-action-step="' + step + '"]');

                            if (!button) {
                                return;
                            }

                            button.hidden = Boolean(isComplete);
                        });
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

                    function mountResendCountdowns(root) {
                        (root || document).querySelectorAll('[data-resend-button]').forEach(function (button) {
                            if (button.dataset.resendMounted === 'true') {
                                return;
                            }

                            var baseLabel = button.dataset.resendLabel || button.textContent.trim() || 'Resend code';
                            var remaining = Number(button.dataset.resendSeconds || 0);

                            function render() {
                                if (remaining > 0) {
                                    button.disabled = true;
                                    button.textContent = 'Resend in ' + remaining + 's';
                                    button.classList.add('is-disabled');
                                } else {
                                    button.disabled = false;
                                    button.textContent = baseLabel;
                                    button.classList.remove('is-disabled');
                                }
                            }

                            render();

                            if (remaining > 0) {
                                var timerId = window.setInterval(function () {
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
                    }

                    function mountExpiryCountdowns(root) {
                        (root || document).querySelectorAll('[data-expiry-countdown]').forEach(function (node) {
                            if (node.dataset.expiryMounted === 'true') {
                                return;
                            }

                            var remaining = Number(node.dataset.expirySeconds || 0);

                            function render() {
                                if (remaining > 0) {
                                    var minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                                    var seconds = String(remaining % 60).padStart(2, '0');
                                    node.textContent = 'Code expires in ' + minutes + ':' + seconds;
                                    node.classList.remove('is-expired');
                                } else {
                                    node.textContent = 'Code expired. Request a new OTP.';
                                    node.classList.add('is-expired');
                                }
                            }

                            render();

                            if (remaining > 0) {
                                var timerId = window.setInterval(function () {
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

                    function mountWhatsappSync(root) {
                        (root || document).querySelectorAll('[data-whatsapp-same-toggle]').forEach(function (toggle) {
                            if (toggle.dataset.whatsappMounted === 'true') {
                                return;
                            }

                            var wrapper = toggle.closest('[data-whatsapp-sync-wrapper]');
                            var form = toggle.closest('form');
                            var primary = form ? form.querySelector('[name="' + toggle.dataset.primarySource + '"]') : null;
                            var whatsapp = form ? form.querySelector('[name="' + toggle.dataset.whatsappTarget + '"]') : null;

                            if (!primary || !whatsapp) {
                                return;
                            }

                            function syncState() {
                                var note = wrapper ? wrapper.querySelector('[data-whatsapp-sync-note]') : null;

                                if (toggle.checked) {
                                    whatsapp.value = primary.value;
                                    whatsapp.readOnly = true;
                                    if (wrapper) {
                                        wrapper.classList.add('is-active');
                                    }
                                    if (note) {
                                        note.hidden = false;
                                    }
                                } else {
                                    whatsapp.readOnly = false;
                                    if (wrapper) {
                                        wrapper.classList.remove('is-active');
                                    }
                                    if (note) {
                                        note.hidden = true;
                                    }
                                }
                            }

                            if (wrapper) {
                                wrapper.addEventListener('click', function (event) {
                                    if (event.target === toggle) {
                                        return;
                                    }

                                    toggle.checked = !toggle.checked;
                                    toggle.dispatchEvent(new Event('change', { bubbles: true }));
                                });

                                wrapper.addEventListener('keydown', function (event) {
                                    if (event.key !== ' ' && event.key !== 'Enter') {
                                        return;
                                    }

                                    event.preventDefault();
                                    toggle.checked = !toggle.checked;
                                    toggle.dispatchEvent(new Event('change', { bubbles: true }));
                                });

                                if (!wrapper.hasAttribute('tabindex')) {
                                    wrapper.tabIndex = 0;
                                }
                            }

                            toggle.addEventListener('change', syncState);
                            primary.addEventListener('input', function () {
                                if (toggle.checked) {
                                    whatsapp.value = primary.value;
                                }
                            });

                            syncState();
                            toggle.dataset.whatsappMounted = 'true';
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

                    function mountLoginIdentifierCheck(root) {
                        (root || document).querySelectorAll('[data-login-identifier-input]').forEach(function (input) {
                            if (input.dataset.loginIdentifierMounted === 'true') {
                                return;
                            }

                            var status = input.parentElement ? input.parentElement.querySelector('[data-login-identifier-status]') : null;
                            var checkUrl = input.dataset.loginCheckUrl;
                            var timeoutId = null;
                            var requestId = 0;

                            function renderStatus(message, type) {
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
                                status.classList.add('is-' + type);
                            }

                            function runCheck() {
                                var identifier = input.value.trim();

                                if (!identifier || identifier.length < 5 || !checkUrl) {
                                    renderStatus('', 'success');
                                    return;
                                }

                                var currentRequest = ++requestId;
                                renderStatus('Checking account...', 'loading');

                                var formData = new FormData();
                                formData.append('identifier', identifier);
                                formData.append('_token', (document.querySelector('meta[name="csrf-token"]') || {}).content || (document.querySelector('input[name="_token"]') || {}).value || '');

                                fetch(checkUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: formData,
                                    credentials: 'same-origin'
                                })
                                    .then(function (response) {
                                        return response.json().catch(function () { return {}; });
                                    })
                                    .then(function (payload) {
                                        if (currentRequest !== requestId) {
                                            return;
                                        }

                                        renderStatus(payload.message || 'Unable to verify this account.', payload.exists ? 'success' : 'error');
                                    })
                                    .catch(function () {
                                        if (currentRequest !== requestId) {
                                            return;
                                        }

                                        renderStatus('Unable to verify right now. You can still try OTP login.', 'error');
                                    });
                            }

                            input.addEventListener('input', function () {
                                window.clearTimeout(timeoutId);
                                timeoutId = window.setTimeout(runCheck, 350);
                            });

                            input.addEventListener('blur', runCheck);
                            input.dataset.loginIdentifierMounted = 'true';
                        });
                    }

                    function mountRegistrationFieldChecks(root) {
                        (root || document).querySelectorAll('[data-registration-check-input]').forEach(function (input) {
                            if (input.dataset.registrationCheckMounted === 'true') {
                                return;
                            }

                            var status = input.parentElement ? input.parentElement.querySelector('[data-registration-check-status]') : null;
                            var checkUrl = input.dataset.registrationCheckUrl;
                            var field = input.dataset.registrationCheckField;
                            var timeoutId = null;
                            var requestId = 0;

                            function renderStatus(message, type) {
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
                                status.classList.add('is-' + type);
                            }

                            function runCheck() {
                                var value = input.value.trim();

                                if ((input.matches('[data-referral-code-input]') && input.hidden) || !value || !checkUrl || !field) {
                                    renderStatus('', 'success');
                                    return;
                                }

                                if ((field === 'email' && value.length < 5) || (field === 'mobile_number' && value.length < 11) || (field === 'referral_code' && value.length < 4)) {
                                    renderStatus('', 'success');
                                    return;
                                }

                                var currentRequest = ++requestId;
                                renderStatus('Checking...', 'loading');

                                var formData = new FormData();
                                formData.append('field', field);
                                formData.append('value', value);
                                formData.append('_token', (document.querySelector('meta[name="csrf-token"]') || {}).content || (document.querySelector('input[name="_token"]') || {}).value || '');

                                fetch(checkUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: formData,
                                    credentials: 'same-origin'
                                })
                                    .then(function (response) {
                                        return response.json().catch(function () { return {}; });
                                    })
                                    .then(function (payload) {
                                        if (currentRequest !== requestId) {
                                            return;
                                        }

                                        renderStatus(payload.message || 'Unable to verify right now.', payload.type || (payload.valid ? 'success' : 'error'));
                                    })
                                    .catch(function () {
                                        if (currentRequest !== requestId) {
                                            return;
                                        }

                                        renderStatus('Unable to verify right now.', 'error');
                                    });
                            }

                            input.addEventListener('input', function () {
                                window.clearTimeout(timeoutId);
                                timeoutId = window.setTimeout(runCheck, 350);
                            });

                            input.addEventListener('blur', runCheck);
                            input.dataset.registrationCheckMounted = 'true';
                        });
                    }

                    function mountDiscoverySourceToggle(root) {
                        (root || document).querySelectorAll('[data-discovery-source-select]').forEach(function (select) {
                            if (select.dataset.discoveryMounted === 'true') {
                                return;
                            }

                            var wrapper = select.closest('[data-discovery-field-wrapper]');
                            var input = wrapper ? wrapper.querySelector('[data-referral-code-input]') : null;
                            var status = wrapper ? wrapper.querySelector('[data-registration-check-status]') : null;
                            var label = wrapper ? wrapper.querySelector('[data-discovery-field-label]') : null;
                            var reset = wrapper ? wrapper.querySelector('[data-referral-code-reset]') : null;

                            function sync() {
                                var isReferral = select.value === 'Referral Code';

                                if (!wrapper || !input) {
                                    return;
                                }

                                select.hidden = isReferral;
                                input.hidden = !isReferral;
                                if (reset) {
                                    reset.hidden = !isReferral;
                                }
                                if (label) {
                                    label.textContent = isReferral ? 'Referral Code' : 'How did you find us?';
                                }

                                if (!isReferral && status) {
                                    status.hidden = true;
                                    status.textContent = '';
                                    status.classList.remove('is-success', 'is-error', 'is-loading');
                                }

                                if (!isReferral) {
                                    input.value = '';
                                } else {
                                    window.setTimeout(function () {
                                        input.focus();
                                    }, 0);
                                }
                            }

                            select.addEventListener('change', sync);
                            if (reset) {
                                reset.addEventListener('click', function () {
                                    select.hidden = false;
                                    select.value = '';
                                    sync();
                                    select.focus();
                                });
                            }
                            sync();
                            select.dataset.discoveryMounted = 'true';
                        });
                    }

                    function showSiteFlash(message, type) {
                        if (!message) {
                            return;
                        }

                        var stack = document.querySelector('.site-flash-stack');

                        if (!stack) {
                            stack = document.createElement('div');
                            stack.className = 'site-flash-stack';
                            stack.setAttribute('aria-live', 'polite');
                            stack.setAttribute('aria-atomic', 'true');
                            document.body.appendChild(stack);
                        }

                        var flash = document.createElement('div');
                        flash.className = 'site-flash site-flash-' + (type || 'success');

                        var title = document.createElement('strong');
                        title.textContent = (type || 'success') === 'error' ? 'Error' : 'Success';

                        var body = document.createElement('span');
                        body.textContent = message;

                        flash.appendChild(title);
                        flash.appendChild(body);
                        stack.appendChild(flash);

                        window.setTimeout(function () {
                            flash.remove();

                            if (!stack.children.length) {
                                stack.remove();
                            }
                        }, 4000);
                    }

                    document.addEventListener('submit', function (event) {
                        var form = event.target.closest('form[data-ajax-form]');

                        if (! form) {
                            return;
                        }

                        event.preventDefault();
                        clearFormErrors(form);

                        var submitter = event.submitter;
                        var submitterLabel = submitter ? submitter.querySelector('.button-loading-label') : null;
                        var originalSubmitterLabel = submitterLabel ? submitterLabel.textContent : '';

                        if (submitter) {
                            submitter.disabled = true;
                            submitter.classList.add('is-loading');

                            if (submitterLabel && submitter.dataset.loadingText) {
                                submitterLabel.textContent = submitter.dataset.loadingText;
                            }
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
                                        showSiteFlash('Please review the highlighted fields.', 'error');
                                        return;
                                    }

                                    showSiteFlash(payload.message || 'Something went wrong. Please try again.', 'error');
                                    showFormMessage(form, payload.message || 'Something went wrong. Please try again.', 'alert-success alert-warning-like');
                                    return;
                                }

                                var mode = form.dataset.ajaxForm;

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

                                    if (payload.next_step && wizardRoot && wizardRoot.__showStep) {
                                        wizardRoot.__showStep(Number(payload.next_step));
                                    }
                                }
                            })
                            .catch(function () {
                                showSiteFlash('Something went wrong. Please try again.', 'error');
                                showFormMessage(form, 'Something went wrong. Please try again.', 'alert-success alert-warning-like');
                            })
                            .finally(function () {
                                if (submitter) {
                                    submitter.disabled = false;
                                    submitter.classList.remove('is-loading');

                                    if (submitterLabel && originalSubmitterLabel) {
                                        submitterLabel.textContent = originalSubmitterLabel;
                                    }
                                }
                            });
                    });

                    document.addEventListener('click', function (event) {
                        var trigger = event.target.closest('[data-close-verification-modal]');

                        if (!trigger) {
                            return;
                        }

                        event.preventDefault();
                        closeVerificationModal(trigger.getAttribute('href'));
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

                    mountLoginIdentifierCheck();
                    mountRegistrationFieldChecks();
                    mountDiscoverySourceToggle();

                    mountOtpGroups(document);
                    mountResendCountdowns(document);
                    mountExpiryCountdowns(document);
                    mountImagePreviews(document);
                    mountWhatsappSync(document);

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
                        var stepBaseUrl = wizardRoot.dataset.stepBaseUrl || '/membership/profile-completion';

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

                            window.history.replaceState({}, '', stepBaseUrl + '/' + step);
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
