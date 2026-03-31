<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use App\Models\GalleryPhoto;
use App\Models\GalleryVideo;
use App\Models\MemorySubmission;
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
            'galleryPreviewPhotos' => GalleryPhoto::query()
                ->latest()
                ->take(4)
                ->get(),
            'heroMetrics' => [
                ['label' => 'Upcoming Events', 'text' => 'Find out about our latest events and reunions.', 'route' => route('events.upcoming'), 'action' => 'View Events'],
                ['label' => 'Alumni Directory', 'text' => 'Search and connect with fellow alumni and members.', 'route' => route('membership.members'), 'action' => 'Search Directory'],
                ['label' => 'Member Benefits', 'text' => 'Explore the value of joining the MUBCAA alumni association.', 'route' => route('membership.privilege'), 'action' => 'View Benefits'],
            ],
            'impactStats' => [
                ['value' => '5000+', 'label' => 'Members'],
                ['value' => '120+', 'label' => 'Volunteers'],
                ['value' => '35+', 'label' => 'Events'],
                ['value' => '15+', 'label' => 'Projects'],
            ],
            'serviceLinks' => [
                'About Us',
                'Membership',
                'Events',
                'News',
                'Photo Gallery',
                'Directory',
            ],
            'events' => [
                ['title' => 'Annual Alumni Meetup', 'meta' => '12 Apr 2026 | Dhaka', 'description' => 'Reconnect with fellow alumni through our flagship annual gathering.', 'route' => route('events.upcoming')],
                ['title' => 'Leadership Workshop', 'meta' => '03 May 2026 | Online', 'description' => 'A focused leadership session for active members and chapter organisers.', 'route' => route('events.upcoming')],
                ['title' => 'Community Service Day', 'meta' => '24 May 2026 | Rangpur', 'description' => 'Join MUBCAA volunteers in a community support and outreach initiative.', 'route' => route('events.upcoming')],
            ],
            'newsItems' => [
                ['title' => 'Annual reunion registration is now open.', 'text' => 'Members can now confirm attendance for the next MUBCAA reunion gathering.'],
                ['title' => 'Scholarship support round announced.', 'text' => 'New support opportunities are available for alumni families and student initiatives.'],
                ['title' => 'New chapter coordination meeting scheduled.', 'text' => 'Regional organisers will meet to review membership drives and event planning.'],
            ],
            'alumni' => [
                ['name' => 'Dr. Amina Rahman', 'meta' => 'Batch 1998 / Academic Leader'],
                ['name' => 'Mahmud Hasan', 'meta' => 'Batch 2002 / Entrepreneur'],
                ['name' => 'Farhana Kabir', 'meta' => 'Batch 2005 / Community Organiser'],
                ['name' => 'Tanvir Ahmed', 'meta' => 'Batch 2010 / Technology Professional'],
            ],
        ]);
    }

    public function page(string $key): View
    {
        abort_unless(array_key_exists($key, $this->pages()), 404);

        if ($key === 'memories-list') {
            return view('pages.memories', [
                'menu' => SiteNavigation::menu(),
                'page' => $this->pages()[$key],
                'memories' => MemorySubmission::query()
                    ->with('user')
                    ->where('status', 'approved')
                    ->latest('approved_at')
                    ->latest()
                    ->get(),
            ]);
        }

        if ($key === 'events-photos') {
            return view('pages.photo-gallery', [
                'menu' => SiteNavigation::menu(),
                'page' => $this->pages()[$key],
                'photos' => GalleryPhoto::query()
                    ->with('uploader')
                    ->latest()
                    ->get(),
            ]);
        }

        if ($key === 'events-videos') {
            return view('pages.video-gallery', [
                'menu' => SiteNavigation::menu(),
                'page' => $this->pages()[$key],
                'videos' => GalleryVideo::query()
                    ->with('uploader')
                    ->latest()
                    ->get(),
            ]);
        }

        return view('pages.standard', [
            'menu' => SiteNavigation::menu(),
            'page' => $this->pages()[$key],
        ]);
    }

    public function submitMemory(Request $request): View
    {
        return view('pages.submit-memory', [
            'menu' => SiteNavigation::menu(),
            'memoryUser' => $request->user(),
        ]);
    }

    public function storeMemory(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'memory' => ['required', 'string', 'max:3000'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'max:6144'],
        ]);

        $photoPaths = [];

        foreach ($request->file('photos', []) as $photo) {
            $photoPaths[] = $photo->store('memory-submissions', 'public');
        }

        MemorySubmission::query()->create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'memory' => $validated['memory'],
            'photos' => $photoPaths,
            'status' => 'pending_review',
        ]);

        return back()
            ->with('success', 'Thank you, '.$user->name.'. Your memory has been submitted and is now waiting for admin review.'.($photoPaths !== [] ? ' '.count($photoPaths).' photo'.(count($photoPaths) > 1 ? 's were' : ' was').' uploaded successfully.' : ''));
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

        ContactSubmission::query()->create($validated);

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
                'intro' => 'Browse event photos uploaded and managed by the admin team.',
                'body' => [
                    'This gallery highlights reunions, committee activities, volunteer programs, and other visual moments from the association.',
                    'Admins can keep the page current by uploading new images directly from the admin panel.',
                ],
                'aside_note' => 'The public gallery reflects photos uploaded from the admin panel.',
            ],
            'events-videos' => [
                'eyebrow' => 'Events',
                'title' => 'Video Gallery',
                'intro' => 'Watch event highlights and uploaded video moments from the admin-managed archive.',
            ],
            'memories-list' => [
                'eyebrow' => 'Memories',
                'title' => 'Memories',
                'intro' => 'Read the approved stories, milestones, and personal moments that give the association its living history.',
                'body' => [
                    'Each memory in this archive has been reviewed before publication so the page stays meaningful, relevant, and easy to trust.',
                    'Members can keep contributing new stories, photos, and moments while the admin team curates what becomes part of the public record.',
                ],
                'aside_note' => 'This archive brings together member stories that have already passed admin review.',
            ],
            'contact' => [
                'eyebrow' => 'Contact',
                'title' => 'Contact Us',
                'intro' => 'Reach the MUBCAA team for enquiries, membership questions, event coordination, or general communication.',
                'hide_narrative' => true,
                'body' => [],
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
