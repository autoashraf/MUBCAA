@extends('layouts.print')

@section('content')
    <div class="print-toolbar">
        <button onclick="window.print()">{{ __('Print / Save PDF') }}</button>
    </div>

    <section class="certificate-sheet">
        <div class="certificate-frame">
            <p class="document-kicker centered">{{ __('Alumni Membership Certificate') }}</p>
            <h1 class="certificate-title">{{ __('Certificate of Alumni Membership') }}</h1>
            <p class="certificate-copy">{{ __('This is to certify that') }}</p>
            <h2 class="certificate-name">{{ $user->name }}</h2>
            <p class="certificate-copy">
                {{ __('has been admitted as a') }}
                <strong>{{ __('Verified Alumni Member') }}</strong>
                {{ __('of the Membership Association and is recognized as an active member of the community.') }}
            </p>

            <div class="certificate-meta">
                <div><span>{{ __('Certificate No') }}</span><strong>{{ $user->certificateNumber() }}</strong></div>
                <div><span>{{ __('Member No') }}</span><strong>{{ $user->memberNumber() }}</strong></div>
                <div><span>{{ __('Issue Date') }}</span><strong>{{ optional($application?->approved_at ?? $user->created_at)->format('d M Y') }}</strong></div>
            </div>

            <div class="certificate-signatures">
                <div>
                    <div class="signature-line"></div>
                    <span>{{ __('Authorized Signatory') }}</span>
                </div>
                <div>
                    <div class="signature-line"></div>
                    <span>{{ __('President / Secretary') }}</span>
                </div>
            </div>
        </div>
    </section>
@endsection
