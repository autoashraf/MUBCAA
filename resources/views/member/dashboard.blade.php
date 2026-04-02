@extends('layouts.app')

@section('content')
    @php
        $documentStatus = $application?->status ?? $user->membership_status;
        $documentsUnlocked = in_array($documentStatus, ['approved', 'verified'], true) || $user->membership_status === 'verified';
        $certificateUnlocked = $user->membership_status === 'verified';
        $resumeWizardStep = max(2, min(10, $application?->current_step ?? $user->profile?->completion_step ?? 2));
        $completedWizardStep = max(1, min(10, $user->profile?->completion_step ?? 1));
        $missingFieldCount = collect($missingSubmissionItems)->sum(fn ($item) => count($item['missing']));
        $nextMissingBlock = $missingSubmissionItems[0] ?? null;
        $documentCount = $documentsUnlocked ? ($certificateUnlocked ? 3 : 2) : 0;
        $statusLabel = match ($documentStatus) {
            'approved', 'verified' => __('Approved'),
            'pending_review', 'under_review' => __('Under Review'),
            'in_progress' => __('In Progress'),
            default => __('Profile Started'),
        };
        $statusTone = match ($documentStatus) {
            'approved', 'verified' => 'verified',
            'pending_review', 'under_review' => 'under_review',
            default => 'pending',
        };
        $workflowCurrentStep = match (true) {
            $documentsUnlocked => 4,
            in_array($documentStatus, ['pending_review', 'under_review'], true) => 3,
            $profileCompletion > 0 => 2,
            default => 1,
        };
        $documents = [
            [
                'title' => 'A4 Profile',
                'description' => __('Printable full profile for your records.'),
                'available' => $documentsUnlocked,
                'route' => route('member.documents.profile'),
                'action' => __('Open PDF'),
            ],
            [
                'title' => __('Member ID Card'),
                'description' => __('Compact member card for quick reference.'),
                'available' => $documentsUnlocked,
                'route' => route('member.documents.id-card'),
                'action' => __('Open ID'),
            ],
            [
                'title' => __('Certificate'),
                'description' => __('Membership certificate after final verification.'),
                'available' => $certificateUnlocked,
                'route' => route('member.documents.certificate'),
                'action' => __('Open Certificate'),
            ],
        ];
    @endphp

    <section class="page-hero">
        <div class="wrap">
            <div class="dashboard-hero dashboard-hero-member dashboard-member-workspace">
                <div class="dashboard-hero-copy">
                    <div class="dashboard-hero-kicker">
                        <span class="panel-card-label">{{ __('Member Dashboard') }}</span>
                        <span class="status-pill status-{{ $statusTone }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="dashboard-profile-band">
                        <div>
                            <h1>{{ $user->name }}</h1>
                            <p class="lead">{{ __('Track what is finished, resume the current draft step, and manage your alumni profile, referrals, and member documents.') }}</p>
                        </div>
                    </div>
                    <div class="hero-actions">
                        @if ($profileCompletion < 100)
                            <a class="button button-primary" href="{{ route('member.profile.complete', ['step' => $resumeWizardStep]) }}">
                                {{ __('Resume Profile Wizard') }}
                            </a>
                        @endif
                        <a class="button button-secondary" href="{{ route('member.profile') }}">{{ __('Open Profile Summary') }}</a>
                        @if ($documentsUnlocked)
                            <a class="button button-secondary" href="{{ route('member.documents.profile') }}" target="_blank">{{ __('Open Documents') }}</a>
                        @endif
                    </div>
                </div>
                <div class="dashboard-hero-meta">
                    <article class="dashboard-progress-card">
                        <span class="panel-card-label">{{ __('Completion Snapshot') }}</span>
                        <div class="dashboard-progress-score">
                            <strong>{{ $profileCompletion }}%</strong>
                            <span>{{ $profileCompletion >= 100 ? __('Ready for review') : __('Profile in progress') }}</span>
                        </div>
                        <div class="dashboard-progress-bar" aria-hidden="true">
                            <span style="width: {{ max(8, $profileCompletion) }}%"></span>
                        </div>
                        <p class="dashboard-progress-copy">
                            {{ $profileCompletion >= 100 ? __('All required sections are complete.') : __('Complete the remaining required fields to move the application forward.') }}
                        </p>
                        <div class="dashboard-progress-meta">
                            <div class="dashboard-progress-meta-card">
                                <small>{{ __('Next Step') }}</small>
                                <strong>{{ $resumeWizardStep }}</strong>
                            </div>
                            <div class="dashboard-progress-meta-card">
                                <small>{{ __('Missing Items') }}</small>
                                <strong>{{ $missingFieldCount }}</strong>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap dashboard-workspace-stack">
            <div class="dashboard-main-grid">
                <article class="profile-summary-card dashboard-roadmap-card">
                    <div class="dashboard-card-head">
                        <div>
                            <span class="panel-card-label">{{ __('Progress Roadmap') }}</span>
                            <h3>{{ __('Membership Journey') }}</h3>
                        </div>
                        <span class="dashboard-chip">{{ __('Stage :current of :total', ['current' => $workflowCurrentStep, 'total' => count($workflowSteps)]) }}</span>
                    </div>
                    <div class="dashboard-roadmap-list">
                        @foreach ($workflowSteps as $step)
                            @php
                                $isComplete = $step->step_number < $workflowCurrentStep;
                                $isCurrent = $step->step_number === $workflowCurrentStep;
                            @endphp
                            <article class="dashboard-roadmap-step @if ($isComplete) is-complete @endif @if ($isCurrent) is-current @endif">
                                <span class="dashboard-roadmap-index">{{ str_pad((string) $step->step_number, 2, '0', STR_PAD_LEFT) }}</span>
                                <div class="dashboard-roadmap-copy">
                                    <strong>{{ $step->title }}</strong>
                                    <p>{{ $step->description }}</p>
                                </div>
                                <span class="dashboard-roadmap-status">
                                    @if ($isComplete)
                                        {{ __('Complete') }}
                                    @elseif ($isCurrent)
                                        {{ __('Active') }}
                                    @else
                                        {{ __('Upcoming') }}
                                    @endif
                                </span>
                            </article>
                        @endforeach
                    </div>
                </article>

                @if ($nextMissingBlock)
                    <aside class="profile-summary-card dashboard-action-card">
                        <div class="dashboard-card-head">
                            <div>
                                <span class="panel-card-label">{{ __('Action Center') }}</span>
                                <h3>{{ __('Next Focus: :title', ['title' => __($nextMissingBlock['title'])]) }}</h3>
                            </div>
                        </div>
                        <p class="dashboard-copy">{{ __('Finish the next required step to move your application closer to review. These are the fields still missing in your current priority block.') }}</p>
                        <div class="dashboard-missing-grid">
                            @foreach (array_slice($nextMissingBlock['missing'], 0, 6) as $field)
                                <span class="dashboard-missing-pill">{{ __($field) }}</span>
                            @endforeach
                        </div>
                        <div class="dashboard-action-buttons">
                            <a class="button button-primary" href="{{ route('member.profile.complete', ['step' => $nextMissingBlock['step']]) }}">{{ __('Open Step :step', ['step' => $nextMissingBlock['step']]) }}</a>
                            <a class="button button-secondary" href="{{ route('member.profile.complete', ['step' => $resumeWizardStep]) }}">{{ __('Resume Step :step', ['step' => $resumeWizardStep]) }}</a>
                        </div>
                    </aside>
                @else
                    <article class="profile-summary-card dashboard-document-card">
                        <div class="dashboard-card-head">
                            <div>
                                <span class="panel-card-label">{{ __('Document Desk') }}</span>
                                <h3>{{ __('Member Files') }}</h3>
                            </div>
                            <span class="dashboard-chip">{{ __(':count available', ['count' => $documentCount]) }}</span>
                        </div>
                        <div class="dashboard-document-list">
                            @foreach ($documents as $document)
                                <article class="dashboard-document-row @if ($document['available']) is-available @else is-locked @endif">
                                    <div class="dashboard-document-copy">
                                        <strong>{{ $document['title'] }}</strong>
                                        <span>{{ $document['description'] }}</span>
                                    </div>
                                    @if ($document['available'])
                                        <a class="mini-link" href="{{ $document['route'] }}" target="_blank">{{ $document['action'] }}</a>
                                    @else
                                        <span class="dashboard-document-badge">{{ __('Locked') }}</span>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </article>
                @endif
            </div>

            <div class="dashboard-secondary-grid">
                @if ($nextMissingBlock)
                    <article class="profile-summary-card dashboard-document-card dashboard-secondary-card dashboard-secondary-card-document">
                        <div class="dashboard-card-head">
                            <div>
                                <span class="panel-card-label">{{ __('Document Desk') }}</span>
                                <h3>{{ __('Member Files') }}</h3>
                            </div>
                            <span class="dashboard-chip">{{ __(':count available', ['count' => $documentCount]) }}</span>
                        </div>
                        <div class="dashboard-document-list">
                            @foreach ($documents as $document)
                                <article class="dashboard-document-row @if ($document['available']) is-available @else is-locked @endif">
                                    <div class="dashboard-document-copy">
                                        <strong>{{ $document['title'] }}</strong>
                                        <span>{{ $document['description'] }}</span>
                                    </div>
                                    @if ($document['available'])
                                        <a class="mini-link" href="{{ $document['route'] }}" target="_blank">{{ $document['action'] }}</a>
                                    @else
                                        <span class="dashboard-document-badge">{{ __('Locked') }}</span>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </article>
                @endif

                <article class="profile-summary-card dashboard-affiliate-card dashboard-secondary-card @if ($nextMissingBlock) dashboard-secondary-card-affiliate @else dashboard-secondary-card-full @endif">
                    <div class="dashboard-card-head">
                        <div>
                            <span class="panel-card-label">{{ __('Affiliate') }}</span>
                            <h3>{{ __('Invite Alumni') }}</h3>
                        </div>
                        <div class="dashboard-affiliate-metrics">
                            <span class="dashboard-chip">{{ __(':count referrals', ['count' => $affiliateSummary['total']]) }}</span>
                            <span class="dashboard-chip">{{ __(':count verified', ['count' => $affiliateSummary['verified']]) }}</span>
                            <span class="dashboard-chip">{{ __(':count under review', ['count' => $affiliateSummary['under_review']]) }}</span>
                        </div>
                    </div>
                    <p class="dashboard-copy">{{ __('Share your referral link to bring other alumni into the network and keep your invitation tools in one place.') }}</p>

                    <div class="dashboard-affiliate-grid">
                        <label class="label-wide">
                            <span>{{ __('Your Referral Link') }}</span>
                            <div class="dashboard-affiliate-copy-row">
                                <input id="affiliate-link" type="text" value="{{ $affiliateSummary['link'] }}" readonly>
                                <button class="button button-secondary dashboard-copy-button" type="button" data-copy-button data-copy-target="affiliate-link" data-copy-label="{{ __('Copy Link') }}">
                                    {{ __('Copy Link') }}
                                </button>
                            </div>
                        </label>
                    </div>

                    @if ($affiliateSummary['recent']->isNotEmpty())
                        <div class="dashboard-affiliate-list">
                            @foreach ($affiliateSummary['recent'] as $referral)
                                <div class="dashboard-affiliate-row">
                                    <div>
                                        <strong>{{ $referral->name }}</strong>
                                        <span>{{ $referral->email }}</span>
                                    </div>
                                    <span class="status-pill status-{{ $referral->membership_status }}">{{ __(str($referral->membership_status)->replace('_', ' ')->title()) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="dashboard-copy">{{ __('No referred members yet.') }}</p>
                    @endif
                </article>

            </div>
        </div>
    </section>

@endsection
