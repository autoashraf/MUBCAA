@extends('layouts.admin')

@section('content')
    <div class="admin-workspace">
        <section id="overview" class="page-hero admin-page-hero">
            <div>
                <p class="eyebrow">Admin Dashboard</p>
                <h1>Admin Panel</h1>
                <p class="lead">Review submitted applications and approve members.</p>
            </div>
        </section>

        <section class="admin-card-grid">
            <article class="admin-summary-card">
                <span>Pending</span>
                <strong>{{ $summary['pending'] }}</strong>
            </article>
            <article class="admin-summary-card">
                <span>Under Review</span>
                <strong>{{ $summary['under_review'] }}</strong>
            </article>
            <article class="admin-summary-card">
                <span>Approved</span>
                <strong>{{ $summary['approved'] }}</strong>
            </article>
            <article class="admin-summary-card">
                <span>Rejected</span>
                <strong>{{ $summary['rejected'] }}</strong>
            </article>
        </section>
    </div>
@endsection
