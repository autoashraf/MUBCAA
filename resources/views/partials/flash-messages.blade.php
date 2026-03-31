@if (session('success') || session('error'))
    <div class="site-flash-stack" aria-live="polite" aria-atomic="true">
        @if (session('success'))
            <div class="site-flash site-flash-success" data-site-flash>
                <div class="site-flash-copy">
                    <strong>Success</strong>
                    <span>{{ session('success') }}</span>
                </div>
                <button class="site-flash-close" type="button" data-site-flash-close aria-label="Close message">×</button>
            </div>
        @endif

        @if (session('error'))
            <div class="site-flash site-flash-error" data-site-flash>
                <div class="site-flash-copy">
                    <strong>Error</strong>
                    <span>{{ session('error') }}</span>
                </div>
                <button class="site-flash-close" type="button" data-site-flash-close aria-label="Close message">×</button>
            </div>
        @endif
    </div>
@endif
