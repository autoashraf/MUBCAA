@extends('layouts.print')

@section('content')
    <div class="print-toolbar">
        <button onclick="window.print()">Print / Save PDF</button>
    </div>

    <section class="certificate-sheet">
        <div class="certificate-frame">
            <p class="document-kicker centered">Alumni Membership Certificate</p>
            <h1 class="certificate-title">Certificate of Alumni Membership</h1>
            <p class="certificate-copy">This is to certify that</p>
            <h2 class="certificate-name">{{ $user->name }}</h2>
            <p class="certificate-copy">
                has been admitted as a
                <strong>Verified Alumni Member</strong>
                of the Membership Association and is recognized as an active member of the community.
            </p>

            <div class="certificate-meta">
                <div><span>Certificate No</span><strong>{{ $user->certificateNumber() }}</strong></div>
                <div><span>Member No</span><strong>{{ $user->memberNumber() }}</strong></div>
                <div><span>Issue Date</span><strong>{{ optional($application?->approved_at ?? $user->created_at)->format('d M Y') }}</strong></div>
            </div>

            <div class="certificate-signatures">
                <div>
                    <div class="signature-line"></div>
                    <span>Authorized Signatory</span>
                </div>
                <div>
                    <div class="signature-line"></div>
                    <span>President / Secretary</span>
                </div>
            </div>
        </div>
    </section>
@endsection
