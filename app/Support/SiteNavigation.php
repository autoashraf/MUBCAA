<?php

namespace App\Support;

class SiteNavigation
{
    public static function menu(): array
    {
        return [
            [
                'label' => __('Home'),
                'route' => route('home'),
                'active' => 'home',
                'children' => [],
            ],
            [
                'label' => __('About Us'),
                'route' => route('about.mission'),
                'active' => 'about.*',
                'children' => [
                    ['label' => __('About MUBCAA'), 'route' => route('about.mission')],
                    ['label' => __('Ad Hoc Committee'), 'route' => route('about.adhoc')],
                    ['label' => __('Sub Committee'), 'route' => route('about.subcommittee')],
                ],
            ],
            [
                'label' => __('Membership'),
                'route' => route('membership.why'),
                'active' => 'membership.*',
                'children' => [
                    ['label' => __('Why Become a Member'), 'route' => route('membership.why')],
                    ['label' => __('Apply Now'), 'route' => route('membership.apply')],
                    ['label' => __('Membership Privilege'), 'route' => route('membership.privilege')],
                    ['label' => __('Find Alumni'), 'route' => route('membership.members')],
                ],
            ],
            [
                'label' => __('Events'),
                'route' => route('events.upcoming'),
                'active' => 'events.*',
                'children' => [
                    ['label' => __('Upcoming Event'), 'route' => route('events.upcoming')],
                    ['label' => __('Photo Gallery'), 'route' => route('events.photos')],
                    ['label' => __('Video Gallery'), 'route' => route('events.videos')],
                ],
            ],
            [
                'label' => __('Memories'),
                'route' => route('memories.submit'),
                'active' => 'memories.*',
                'children' => [
                    ['label' => __('Submit Your Memory'), 'route' => route('memories.submit')],
                    ['label' => __('Memories'), 'route' => route('memories.list')],
                ],
            ],
            [
                'label' => __('Contact'),
                'route' => route('contact'),
                'active' => 'contact',
                'children' => [],
            ],
        ];
    }
}
