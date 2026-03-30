@extends('layouts.admin')

@section('content')
    <div class="admin-workspace grid gap-6">
        <section id="overview" class="page-hero admin-page-hero flex flex-col gap-4 rounded-[2rem] border border-slate-200/90 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow">Admin Dashboard</p>
                <h1 class="text-4xl font-bold tracking-tight text-slate-950">Admin Panel</h1>
                <p class="lead">Review submitted applications and approve members.</p>
            </div>
        </section>

        <section class="admin-card-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Pending</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $summary['pending'] }}</strong>
            </article>
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Under Review</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $summary['under_review'] }}</strong>
            </article>
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Approved</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $summary['approved'] }}</strong>
            </article>
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Rejected</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $summary['rejected'] }}</strong>
            </article>
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Affiliates</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $affiliateOverview['members'] }}</strong>
            </article>
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Referral Signups</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $affiliateOverview['referrals'] }}</strong>
            </article>
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Verified Referrals</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $affiliateOverview['verified_referrals'] }}</strong>
            </article>
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Under Review Referrals</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $affiliateOverview['under_review_referrals'] }}</strong>
            </article>
        </section>
    </div>
@endsection
