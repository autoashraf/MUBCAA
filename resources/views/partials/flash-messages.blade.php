@if (session('success') || session('error'))
    <div class="site-flash-stack" aria-live="polite" aria-atomic="true">
        @if (session('success'))
            <div class="site-flash site-flash-success">
                <strong>Success</strong>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="site-flash site-flash-error">
                <strong>Error</strong>
                <span>{{ session('error') }}</span>
            </div>
        @endif
    </div>
@endif
