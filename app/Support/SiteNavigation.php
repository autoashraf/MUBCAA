<?php

namespace App\Support;

class SiteNavigation
{
    public static function menu(): array
    {
        return [
            [
                'label' => 'Home',
                'route' => route('home'),
                'active' => 'home',
                'children' => [],
            ],
            [
                'label' => 'About Us',
                'route' => route('about.mission'),
                'active' => 'about.*',
                'children' => [
                    ['label' => 'Mission & Vision', 'route' => route('about.mission')],
                    ['label' => 'Ad Hoc Committee', 'route' => route('about.adhoc')],
                    ['label' => 'Sub Committee', 'route' => route('about.subcommittee')],
                ],
            ],
            [
                'label' => 'Membership',
                'route' => route('membership.why'),
                'active' => 'membership.*',
                'children' => [
                    ['label' => 'Why Become a Member', 'route' => route('membership.why')],
                    ['label' => 'Apply Now', 'route' => route('membership.apply')],
                    ['label' => 'Membership Privilege', 'route' => route('membership.privilege')],
                    ['label' => 'Members', 'route' => route('membership.members')],
                ],
            ],
            [
                'label' => 'Events',
                'route' => route('events.upcoming'),
                'active' => 'events.*',
                'children' => [
                    ['label' => 'Upcoming Event', 'route' => route('events.upcoming')],
                    ['label' => 'Photo Gallery', 'route' => route('events.photos')],
                    ['label' => 'Video Gallery', 'route' => route('events.videos')],
                ],
            ],
            [
                'label' => 'Memories',
                'route' => route('memories.submit'),
                'active' => 'memories.*',
                'children' => [
                    ['label' => 'Submit Your Memory', 'route' => route('memories.submit')],
                    ['label' => 'Memories', 'route' => route('memories.list')],
                ],
            ],
            [
                'label' => 'Contact',
                'route' => route('contact'),
                'active' => 'contact',
                'children' => [],
            ],
        ];
    }
}
