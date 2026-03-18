<?php

namespace App\Http\Controllers;

use App\Support\SiteNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'menu' => SiteNavigation::menu(),
            'slides' => config('site.homepage.slides', []),
            'heroMetrics' => [
                ['label' => 'Total Members'],
                ['label' => 'Upcoming Events'],
                ['label' => 'Scholarships'],
                ['label' => 'Active Chapters'],
            ],
            'impactStats' => [
                ['value' => '5000+', 'label' => 'Members'],
                ['value' => '120+', 'label' => 'Volunteers'],
                ['value' => '35+', 'label' => 'Events'],
                ['value' => '15+', 'label' => 'Projects'],
            ],
            'highlights' => [
                [
                    'title' => 'Mission & Vision',
                    'text' => 'Present the purpose, values, and long-term direction that define MUBCAA as a trusted alumni organisation.',
                    'route' => route('about.mission'),
                    'label' => 'About the Association',
                ],
                [
                    'title' => 'Membership',
                    'text' => 'Guide alumni and members into the association with clear membership types, benefits, and onboarding steps.',
                    'route' => route('membership.why'),
                    'label' => 'Member Services',
                ],
                [
                    'title' => 'Events',
                    'text' => 'Showcase reunions, programs, galleries, and community moments that keep the alumni network active and visible.',
                    'route' => route('events.upcoming'),
                    'label' => 'Community Life',
                ],
            ],
            'serviceLinks' => [
                'Member Registration',
                'Alumni Directory',
                'Event Participation',
                'Career Networking',
                'Donation Support',
                'Volunteer Program',
                'Business Directory',
                'Community Support',
                'Scholarship Support',
            ],
            'events' => [
                ['title' => 'Annual Alumni Meetup', 'meta' => '12 Apr 2026 | Dhaka', 'description' => 'Reconnect with fellow alumni through our flagship annual gathering.', 'route' => route('events.upcoming')],
                ['title' => 'Leadership Workshop', 'meta' => '03 May 2026 | Online', 'description' => 'A focused leadership session for active members and chapter organisers.', 'route' => route('events.upcoming')],
                ['title' => 'Community Service Day', 'meta' => '24 May 2026 | Rangpur', 'description' => 'Join MUBCAA volunteers in a community support and outreach initiative.', 'route' => route('events.upcoming')],
            ],
            'newsItems' => [
                'Membership renewal notice for 2026 is now open.',
                'Applications invited for the MUBCAA scholarship support round.',
                'Upcoming chapter coordination meeting scheduled next month.',
                'Photo gallery from the latest reunion has been updated.',
            ],
            'alumni' => [
                ['name' => 'Dr. Amina Rahman', 'meta' => 'Batch 1998 / Academic Leader'],
                ['name' => 'Mahmud Hasan', 'meta' => 'Batch 2002 / Entrepreneur'],
                ['name' => 'Farhana Kabir', 'meta' => 'Batch 2005 / Community Organiser'],
                ['name' => 'Tanvir Ahmed', 'meta' => 'Batch 2010 / Technology Professional'],
            ],
            'galleryTiles' => range(1, 6),
            'testimonials' => [
                'MUBCAA helped me reconnect with mentors, classmates, and new professional opportunities.',
                'The association gives alumni a meaningful way to contribute back to the community.',
            ],
        ]);
    }

    public function page(string $key): View
    {
        abort_unless(array_key_exists($key, $this->pages()), 404);

        return view('pages.standard', [
            'menu' => SiteNavigation::menu(),
            'page' => $this->pages()[$key],
        ]);
    }

    public function submitMemory(): View
    {
        return view('pages.submit-memory', [
            'menu' => SiteNavigation::menu(),
        ]);
    }

    public function storeMemory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'memory' => ['required', 'string', 'max:3000'],
        ]);

        return back()
            ->withInput()
            ->with('success', 'Memory submitted by '.$validated['name'].'. Connect this form to moderation and storage when you are ready.');
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        return back()
            ->withInput()
            ->with('success', 'Thank you, '.$validated['name'].'. Your message has been received.');
    }

    private function pages(): array
    {
        return [
            'about-mission' => [
                'eyebrow' => 'About Us',
                'title' => 'Mission & Vision',
                'intro' => 'A strong membership organisation needs a clear purpose and a public promise.',
                'body' => [
                    'Our mission is to connect members through service, collaboration, and lifelong relationships that generate real value for the community.',
                    'Our vision is to grow into a trusted platform where members can build leadership, preserve shared history, and contribute to future programs.',
                ],
                'cards' => [
                    ['title' => 'Service', 'text' => 'Support members with relevant programs, useful communication, and meaningful representation.'],
                    ['title' => 'Community', 'text' => 'Create a place where members meet, mentor, and celebrate each other.'],
                    ['title' => 'Continuity', 'text' => 'Preserve institutional memory while developing the next generation of leaders.'],
                ],
            ],
            'about-adhoc' => [
                'eyebrow' => 'About Us',
                'title' => 'Ad Hoc Committee',
                'intro' => 'Short-term committees handle focused responsibilities with clear timelines.',
                'body' => [
                    'Ad hoc committees can be formed for special events, policy reviews, fundraising campaigns, or strategic projects requiring temporary leadership.',
                    'Each committee should operate with a defined scope, assigned members, and a reporting process to the central executive body.',
                ],
                'cards' => [
                    ['title' => 'Event Taskforce', 'text' => 'Plans annual conferences, reunions, and public programs.'],
                    ['title' => 'Membership Drive', 'text' => 'Targets outreach, onboarding, and category-based recruitment.'],
                    ['title' => 'Special Projects', 'text' => 'Delivers one-off initiatives that need fast coordination.'],
                ],
            ],
            'about-subcommittee' => [
                'eyebrow' => 'About Us',
                'title' => 'Sub Committee',
                'intro' => 'Sub committees support the organisation by owning recurring operational areas.',
                'body' => [
                    'These teams work under the main committee to manage regular functions such as communication, member welfare, events, and documentation.',
                    'A clear reporting structure helps each sub committee stay accountable while moving work quickly.',
                ],
                'cards' => [
                    ['title' => 'Communications', 'text' => 'Handles notices, social outreach, and newsletters.'],
                    ['title' => 'Welfare', 'text' => 'Coordinates support initiatives and member care.'],
                    ['title' => 'Archive', 'text' => 'Maintains records, photos, videos, and stories.'],
                ],
            ],
            'membership-why' => [
                'eyebrow' => 'Membership',
                'title' => 'Why Become a Member',
                'intro' => 'Membership should feel useful, visible, and worth keeping year after year.',
                'body' => [
                    'Members gain structured access to networking, updates, event participation, and recognition inside the association.',
                    'The organisation gains a stronger voice when more people formally join and take part in shared activities.',
                ],
                'cards' => [
                    ['title' => 'Network', 'text' => 'Meet peers, seniors, and future collaborators through the association.'],
                    ['title' => 'Recognition', 'text' => 'Show your connection with the community through official membership.'],
                    ['title' => 'Participation', 'text' => 'Vote, volunteer, serve, and influence programs.'],
                ],
            ],
            'membership-privilege' => [
                'eyebrow' => 'Membership',
                'title' => 'Membership Privilege',
                'intro' => 'Privileges should be explicit so members understand what they receive.',
                'body' => [
                    'Benefits can include priority registration, discounted fees, member-only notices, leadership eligibility, and directory inclusion.',
                    'You can expand this page later with real policy rules, fee structure, and terms for each member category.',
                ],
                'cards' => [
                    ['title' => 'Priority Access', 'text' => 'Early registration for major events and programs.'],
                    ['title' => 'Community Profile', 'text' => 'Inclusion in a trusted member list.'],
                    ['title' => 'Leadership Track', 'text' => 'Eligibility for committee roles and representation.'],
                ],
            ],
            'membership-members' => [
                'eyebrow' => 'Membership',
                'title' => 'Members',
                'intro' => 'A simple category-based directory gives immediate structure to your membership page.',
                'body' => [
                    'The current design separates Lifetime Members and General Members, matching the categories in your sketch.',
                    'Later you can replace these demo cards with a database-backed directory and search filters.',
                ],
                'cards' => [
                    ['title' => 'Lifetime Members', 'text' => 'Reserved for long-term supporters or members with permanent status.'],
                    ['title' => 'General Members', 'text' => 'The standard membership category for active community participants.'],
                ],
                'lists' => [
                    'Lifetime Members' => ['Dr. Amina Rahman', 'Md. Saiful Islam', 'Farhana Kabir'],
                    'General Members' => ['Nusrat Jahan', 'Mahmud Hasan', 'Sharmin Akter', 'Tanvir Ahmed'],
                ],
            ],
            'events-upcoming' => [
                'eyebrow' => 'Events',
                'title' => 'Upcoming Event',
                'intro' => 'Use this section to spotlight the next major gathering.',
                'body' => [
                    'For now this page presents sample event content. Replace it with dynamic event records when you introduce the database layer.',
                    'A strong event page should include schedule, speakers, venue, registration details, and supporting media.',
                ],
                'cards' => [
                    ['title' => 'Annual Reunion', 'text' => 'A flagship gathering for all member categories and guests.'],
                    ['title' => 'Leadership Meetup', 'text' => 'Focused session for committees, mentors, and organisers.'],
                    ['title' => 'Community Workshop', 'text' => 'Skill-based program supporting younger members.'],
                ],
            ],
            'events-photos' => [
                'eyebrow' => 'Events',
                'title' => 'Photo Gallery',
                'intro' => 'Photo collections make the site feel alive even before you add a CMS.',
                'body' => [
                    'This version uses styled placeholders that can be replaced later with gallery images from storage or a media package.',
                    'When you move to production, use proper image uploads, compression, and alt text for accessibility.',
                ],
                'gallery' => ['Reunion Day', 'Member Meetup', 'Award Ceremony', 'Workshop Session', 'Volunteer Program', 'Cultural Evening'],
            ],
            'events-videos' => [
                'eyebrow' => 'Events',
                'title' => 'Video Gallery',
                'intro' => 'Video highlights work well for speeches, testimonials, and recap reels.',
                'body' => [
                    'Right now these are placeholders for future embedded videos from YouTube, Vimeo, or self-hosted media.',
                    'Keep the layout simple so it can later accept thumbnails, duration labels, and watch pages.',
                ],
                'gallery' => ['Chairperson Message', 'Annual Event Recap', 'Member Story', 'Committee Update'],
            ],
            'memories-list' => [
                'eyebrow' => 'Memories',
                'title' => 'Memories',
                'intro' => 'A memory archive helps your community preserve personal stories and milestones.',
                'body' => [
                    'This starter version shows example memory excerpts. You can later connect this page to moderated submissions from members.',
                    'Consider adding year filters, event tags, contributor names, and photo attachments in the next phase.',
                ],
                'cards' => [
                    ['title' => 'First Reunion', 'text' => 'A story about reconnecting after years apart and meeting old friends again.'],
                    ['title' => 'Mentorship Journey', 'text' => 'A member reflects on guidance received from senior participants.'],
                    ['title' => 'Volunteer Day', 'text' => 'Shared work turned into one of the association’s most memorable moments.'],
                ],
            ],
            'contact' => [
                'eyebrow' => 'Contact',
                'title' => 'Contact Us',
                'intro' => 'Reach the MUBCAA team for enquiries, membership questions, event coordination, or general communication.',
                'body' => [
                    'Use the details below to contact the association directly, or send a message through the contact form for a response from the MUBCAA team.',
                    'This form currently validates and confirms submissions on the site. It can be connected next to email delivery, CRM storage, or admin notifications.',
                ],
                'cards' => [
                    ['title' => 'Office', 'text' => 'MUBCAA Office, Main Road, Dhaka, Bangladesh'],
                    ['title' => 'Email', 'text' => 'info@mubcaa.org'],
                    ['title' => 'Phone', 'text' => '+880 1234-567890'],
                ],
                'contact_form' => true,
            ],
        ];
    }
}
