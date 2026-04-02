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
        $content = $this->translateContent([
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

        return view('pages.home', [
            'menu' => SiteNavigation::menu(),
            'slides' => config('site.homepage.slides', []),
            'galleryPreviewPhotos' => GalleryPhoto::query()
                ->latest()
                ->take(4)
                ->get(),
            'heroMetrics' => $content['heroMetrics'],
            'impactStats' => $content['impactStats'],
            'serviceLinks' => $content['serviceLinks'],
            'events' => $content['events'],
            'newsItems' => $content['newsItems'],
            'alumni' => $content['alumni'],
        ]);
    }

    public function page(string $key): View
    {
        abort_unless(array_key_exists($key, $this->pages()), 404);

        if (in_array($key, ['privacy-policy', 'terms-conditions', 'cookie-policy', 'disclaimer'], true)) {
            return view('pages.legal', [
                'menu' => SiteNavigation::menu(),
                'page' => $this->pages()[$key],
            ]);
        }

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
            ->with('success', __('Thank you, :name. Your memory has been submitted and is now waiting for admin review.', ['name' => $user->name]).($photoPaths !== [] ? ' '.__(':count photo:plural uploaded successfully.', ['count' => count($photoPaths), 'plural' => count($photoPaths) > 1 ? 's were' : ' was']) : ''));
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
            ->with('success', __('Thank you, :name. Your message has been received.', ['name' => $validated['name']]));
    }

    private function pages(): array
    {
        return $this->translateContent([
            'about-mission' => [
                'eyebrow' => 'About Us',
                'title' => 'About MUBCAA',
                'intro' => 'Monipur Uchcha Bidyalaya & College Alumni Association (MUBCAA) is a united platform for the former students of Monipur Uchcha Bidyalaya & College, built to keep alumni connected with one another and with the institution that shaped their memories, values, and journey.',
                'body' => [
                    'MUBCAA is more than an alumni network. It is a community built on connection, support, shared identity, and a common purpose to grow together and create positive impact.',
                ],
                'sections' => [
                    [
                        'title' => 'Why MUBCAA Was Founded',
                        'paragraphs' => [
                            'MUBCAA was founded with the belief that the bond between alumni and their institution should continue beyond student life. Over time, the need for a common platform became clear — a place where former students could reconnect, support one another, and work together in an organized and meaningful way.',
                            'It was established not only to preserve memories, but also to build unity, encourage cooperation, and create opportunities that benefit alumni, current students, and the wider community.',
                        ],
                    ],
                    [
                        'title' => 'Our Mission',
                        'paragraphs' => [
                            'Our mission is to unite the alumni of Monipur Uchcha Bidyalaya & College through a lifelong bond of connection, support, and shared purpose. MUBCAA aims to create a platform where alumni can stay engaged, strengthen relationships, and contribute to meaningful causes.',
                            'Through friendship, cooperation, mentorship, and service, we seek to turn alumni connection into real value for members, students, and society.',
                        ],
                    ],
                    [
                        'title' => 'Our Vision',
                        'paragraphs' => [
                            'Our vision is to build a proud, respected, and inspiring alumni community that reflects the spirit and values of Monipur Uchcha Bidyalaya & College. We aspire to create a lasting platform where alumni remain connected, support one another, and work together for positive change.',
                            'MUBCAA envisions a future where shared identity becomes a source of strength, leadership, service, and collective progress.',
                        ],
                    ],
                    [
                        'title' => 'Our Purpose',
                        'paragraphs' => [
                            'The purpose of MUBCAA is to bring alumni together on one meaningful platform and turn that connection into positive action. We aim to promote friendship, unity, networking, member support, personal growth, and community engagement.',
                            'MUBCAA also hopes to contribute to the educational development of current students and support initiatives that create wider social value.',
                        ],
                    ],
                    [
                        'title' => 'Our Core Values',
                        'paragraphs' => [
                            'MUBCAA is guided by values that define its spirit and direction. We believe in unity, because togetherness gives strength. We believe in respect, because every member deserves dignity and kindness. We believe in service and responsibility, because a strong alumni community stands beside others and works for the greater good.',
                            'We also believe in integrity, which helps us uphold honesty, trust, and transparency in all our efforts. Above all, we believe in lifelong connection — the lasting bond with our institution and fellow alumni that continues with pride, belonging, and purpose.',
                        ],
                    ],
                    [
                        'title' => 'Our Mission',
                        'paragraphs' => [
                            'MUBCAA is more than an alumni association. It is a bond of memory, identity, and belonging. Our mission is to reconnect the former students of Monipur Uchcha Bidyalaya & College through a platform built on unity, respect, and shared purpose.',
                            'We want to turn memories into meaningful connections, and connections into positive action. Through friendship, support, mentorship, and collective effort, we aim to build a caring alumni community that stands beside one another in times of need, celebrates success together, and works for the greater good.',
                            'MUBCAA is committed to inspiring alumni to stay connected with their roots, contribute their experience and strength, and play an active role in supporting fellow members, future generations, and society as a whole.',
                        ],
                    ],
                    [
                        'title' => 'Our Vision',
                        'paragraphs' => [
                            'Our vision is to build a proud, united, and inspiring alumni community that carries the spirit of Monipur Uchcha Bidyalaya & College far beyond the classroom.',
                            'We dream of a platform where every former student feels connected, valued, and welcomed. A platform where shared memories create lifelong bonds, where success is used to uplift others, and where the strength of unity becomes a force for service, progress, and hope.',
                            'MUBCAA aspires to become a respected and enduring association that preserves the pride of the past, empowers the present, and helps shape a better future for alumni, students, and the wider community.',
                        ],
                    ],
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
                'intro' => 'Becoming a member of MUBCAA means joining a strong, supportive, and trusted alumni community. It gives you the chance to stay connected with your institution, renew old friendships, and build valuable relationships with alumni from different batches.',
                'body' => [
                    'MUBCAA is not only about remembering the past. It is also about creating real opportunities and standing together as a community. As a member, you may take part in alumni events, receive important updates, expand your network, and benefit from future member-focused programs. Members may also receive special offers or discounts from selected service providers and partner organizations when such opportunities are arranged.',
                    'A true alumni association stands by its members. MUBCAA hopes to build a culture of care, responsibility, and mutual support. The association will try to take practical steps to assist members who are facing hardship and, whenever possible, support poor or distressed members in meaningful ways.',
                    'MUBCAA also aims to help members grow in their personal and professional lives. Through alumni connections, qualified members may receive support in finding suitable job opportunities. The association may also arrange career development programs, entrepreneurship support, training sessions, and skill-building initiatives to help members prepare for a better future.',
                    'At the same time, MUBCAA wants to create a wider positive impact. Through different initiatives, the association may contribute to the educational development of current students and take supportive steps for parents and families where needed. In this way, membership becomes more than personal belonging. It becomes a chance to be part of something meaningful and beneficial for others.',
                ],
                'sections' => [
                    [
                        'title' => 'Member Benefits',
                        'bullets' => [
                            'Stay connected with the alumni community',
                            'Reconnect with old friends and meet alumni from different batches',
                            'Join reunions, events, and special programs',
                            'Receive important notices, news, and updates',
                            'Build strong personal and professional connections',
                            'Enjoy special benefits or discounts from selected partners',
                            'Receive networking support for suitable job opportunities',
                            'Take part in career development and entrepreneurship initiatives',
                            'Join training, guidance, and skill development programs',
                            'Support and participate in initiatives for members facing hardship',
                            'Contribute to the educational growth of current students',
                            'Take part in meaningful activities for families and the wider community',
                            'Share ideas and contribute to the progress of the association',
                            'Be part of a respected, active, and growing alumni platform',
                        ],
                    ],
                    [
                        'title' => 'Lifelong Connection',
                        'paragraphs' => [
                            'By becoming a member, you are not simply joining an association. You are becoming part of a lifelong connection, a supportive network, and a community built on unity, care, and shared progress.',
                        ],
                    ],
                ],
            ],
            'membership-privilege' => [
                'eyebrow' => 'Membership',
                'title' => 'Membership Privilege',
                'intro' => 'Being a member of MUBCAA means enjoying more than just connection and community. It also gives you access to exclusive privileges designed to add real value to your everyday life.',
                'body' => [
                    'Through selected partner institutions and service providers, MUBCAA members may receive special offers, discounts, priority services, and other useful benefits.',
                    'These privileges may include support in areas such as healthcare, education, training, travel, lifestyle services, business support, and more.',
                    'Explore the available privileges and discover how your membership can open the door to practical advantages and meaningful opportunities.',
                ],
                'cards' => [
                    ['title' => 'Exclusive Offers', 'text' => 'Special offers and discounts from selected partner institutions and service providers when available.'],
                    ['title' => 'Priority Benefits', 'text' => 'Useful member advantages that may include priority services, program access, and value-added opportunities.'],
                    ['title' => 'Practical Value', 'text' => 'Membership is designed to create real-life benefits across healthcare, education, training, travel, lifestyle, and business support.'],
                ],
            ],
            'membership-members' => [
                'eyebrow' => 'Membership',
                'title' => 'Find Alumni',
                'intro' => 'This section is for finding alumni and reconnecting with former students of Monipur Uchcha Bidyalaya & College.',
                'body' => [
                    'Use this area to search, explore, and reconnect with alumni from different batches through the MUBCAA network.',
                    'As the directory grows, this section can become a trusted place to discover fellow alumni, rebuild connections, and stay engaged with the wider community.',
                ],
                'cards' => [
                    ['title' => 'Search by Batch', 'text' => 'Find alumni from your own batch or discover former students from other years.'],
                    ['title' => 'Reconnect', 'text' => 'Use the alumni directory as a bridge to rebuild friendships and strengthen community ties.'],
                    ['title' => 'Stay Engaged', 'text' => 'A stronger alumni directory helps members stay visible, connected, and active within MUBCAA.'],
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
            'privacy-policy' => [
                'eyebrow' => 'Legal',
                'title' => 'Privacy Policy',
                'effective_date' => '5 April 2026',
                'intro' => 'Welcome to the official website of Monipur Uchcha Bidyalaya & College Alumni Association (MUBCAA). We respect your privacy and are committed to protecting the information you share with us. This Privacy Policy explains how we collect, use, store, and protect your information when you visit our website or interact with our services.',
                'lead_note' => 'By using this website, you agree to the terms of this Privacy Policy.',
                'sections' => [
                    [
                        'title' => 'Information We Collect',
                        'bullets' => [
                            'Full name',
                            'Mobile number',
                            'Email address',
                            'Batch or passing year',
                            'Student ID or roll number',
                            'Address and location details',
                            'Professional information',
                            'Profile photo or uploaded materials',
                            'Messages, inquiries, and feedback',
                            'Event or membership-related details',
                            'Technical information such as browser type, IP address, device type, and browsing activity',
                        ],
                    ],
                    [
                        'title' => 'How We Collect Information',
                        'lead' => 'We may collect information when you:',
                        'bullets' => [
                            'Visit or browse our website',
                            'Fill out a contact form',
                            'Apply for membership',
                            'Register for events or programs',
                            'Subscribe to updates',
                            'Submit alumni information or profile details',
                            'Contact us through online forms or email',
                        ],
                    ],
                    [
                        'title' => 'How We Use Your Information',
                        'lead' => 'We use your information to:',
                        'bullets' => [
                            'Manage alumni membership and registration',
                            'Verify identity when necessary',
                            'Respond to inquiries and communication requests',
                            'Organize reunions, events, campaigns, and alumni programs',
                            'Maintain alumni records and directory features',
                            'Send notices, invitations, and important updates',
                            'Improve our website and user experience',
                            'Support administrative and organizational activities',
                            'Protect website security and prevent misuse',
                        ],
                    ],
                    [
                        'title' => 'Alumni Directory and Public Information',
                        'paragraphs' => [
                            'Some information provided by members may be displayed in alumni directories or member listing sections, depending on the website\'s features and association policies. We take reasonable care when handling such information. However, users are advised not to submit highly sensitive data unless specifically requested.',
                            'MUBCAA reserves the right to review, edit, limit, or remove publicly displayed information for privacy, security, or administrative reasons.',
                        ],
                    ],
                    [
                        'title' => 'Sharing of Information',
                        'lead' => 'We do not sell or rent personal information to third parties. We may share information only when necessary:',
                        'bullets' => [
                            'To operate and maintain the website',
                            'To work with trusted service providers supporting website or event functions',
                            'To comply with legal obligations',
                            'To protect the rights, security, and integrity of MUBCAA and its users',
                        ],
                    ],
                    [
                        'title' => 'Data Security',
                        'paragraphs' => [
                            'We take reasonable administrative and technical measures to protect your information from unauthorized access, misuse, loss, or disclosure. However, no online platform can guarantee complete security.',
                            'While we do our best to protect your data, absolute security cannot be guaranteed at all times.',
                        ],
                    ],
                    [
                        'title' => 'Cookies and Tracking',
                        'paragraphs' => [
                            'Our website may use cookies and similar technologies to improve website performance, remember preferences, analyze traffic, and enhance user experience. Please review our Cookie Policy for more details.',
                        ],
                    ],
                    [
                        'title' => 'Third-Party Links',
                        'paragraphs' => [
                            'Our website may include links to third-party websites, services, social media pages, or embedded tools. We are not responsible for the privacy practices or content of those third-party platforms. Users are encouraged to review their policies separately.',
                        ],
                    ],
                    [
                        'title' => 'Data Retention',
                        'paragraphs' => [
                            'We may retain your information for as long as necessary for membership, alumni engagement, administration, legal compliance, security, or operational purposes.',
                        ],
                    ],
                    [
                        'title' => 'Your Rights and Choices',
                        'lead' => 'You may contact us to:',
                        'bullets' => [
                            'Request correction of inaccurate information',
                            'Update profile or membership details',
                            'Ask questions about how your data is used',
                            'Request removal of certain submitted information where applicable',
                        ],
                        'paragraphs' => [
                            'All such requests will be reviewed in line with applicable administrative and legal requirements.',
                        ],
                    ],
                    [
                        'title' => 'Updates to This Policy',
                        'paragraphs' => [
                            'We may update this Privacy Policy from time to time. Any revised version will be posted on this page with the updated effective date. Your continued use of the website means you accept the updated policy.',
                        ],
                    ],
                ],
                'contact_details' => [
                    'Monipur Uchcha Bidyalaya & College Alumni Association (MUBCAA)',
                    'Email: mubcaa@gmail.com',
                    'Phone: 01700000000',
                    'Address: Road 9, Avenue 9, House 176, Mirpur DOHS',
                    'Website: mubcaa.com',
                ],
            ],
            'terms-conditions' => [
                'eyebrow' => 'Legal',
                'title' => 'Terms & Conditions',
                'effective_date' => '5 April 2026',
                'intro' => 'Welcome to the official website of Monipur Uchcha Bidyalaya & College Alumni Association (MUBCAA). By accessing or using this website, you agree to comply with and be bound by these Terms & Conditions. Please read them carefully.',
                'lead_note' => 'If you do not agree with any part of these terms, please do not use this website.',
                'sections' => [
                    [
                        'title' => 'Purpose of the Website',
                        'paragraphs' => [
                            'This website is designed to provide information about MUBCAA, its mission, alumni engagement activities, membership opportunities, programs, events, announcements, directories, and other related services.',
                        ],
                    ],
                    [
                        'title' => 'Acceptance of Terms',
                        'paragraphs' => [
                            'By using this website, submitting any form, registering as a member, joining any program, or communicating with MUBCAA through the website, you agree to these Terms & Conditions and related published policies.',
                        ],
                    ],
                    [
                        'title' => 'User Responsibilities',
                        'lead' => 'By using this website, you agree that:',
                        'bullets' => [
                            'The information you provide is true and accurate',
                            'You will use the website lawfully and respectfully',
                            'You will not misuse or attempt to disrupt the website',
                            'You will not submit false, harmful, misleading, or offensive content',
                        ],
                    ],
                    [
                        'title' => 'Membership and Registration',
                        'paragraphs' => [
                            'This website may offer online membership forms, profile submissions, or registration features. Submission of any form does not automatically guarantee approval.',
                            'MUBCAA reserves the right to verify submitted information and may accept, reject, suspend, or remove any application, listing, or account according to its policies and administrative judgment.',
                        ],
                    ],
                    [
                        'title' => 'Use of Alumni Directory',
                        'paragraphs' => [
                            'Any alumni directory or member listing provided on the website is intended only for alumni engagement and association-related communication.',
                        ],
                        'lead' => 'Users must not:',
                        'bullets' => [
                            'Copy or scrape directory information',
                            'Use member data for marketing or spam',
                            'Share private member information without permission',
                            'Use directory access for harmful or commercial purposes',
                        ],
                    ],
                    [
                        'title' => 'Events and Participation',
                        'paragraphs' => [
                            'MUBCAA may publish details of reunions, meetings, campaigns, training sessions, social activities, and alumni events on this website.',
                            'Submitting a registration or participation form does not always guarantee final participation unless it is officially confirmed. MUBCAA may revise schedules, venues, details, or participation conditions when necessary.',
                        ],
                    ],
                    [
                        'title' => 'Donations and Contributions',
                        'paragraphs' => [
                            'This website may include donation, sponsorship, fundraising, or contribution-related information to support alumni initiatives and association activities.',
                            'Unless otherwise clearly stated, financial contributions made to the association may be treated as non-refundable after processing. MUBCAA reserves the right to verify and manage contributions according to its internal policies.',
                        ],
                    ],
                    [
                        'title' => 'Intellectual Property',
                        'paragraphs' => [
                            'All content on this website, including text, logos, graphics, photographs, designs, documents, and notices, belongs to MUBCAA or is used with permission unless otherwise stated.',
                            'No content from this website may be copied, reproduced, republished, distributed, modified, or commercially used without prior written permission.',
                        ],
                    ],
                    [
                        'title' => 'Acceptable Use',
                        'lead' => 'You must not use this website to:',
                        'bullets' => [
                            'Violate any law or regulation',
                            'Harm the reputation or operation of MUBCAA',
                            'Attempt unauthorized access to systems or data',
                            'Distribute malware, spam, or harmful content',
                            'Harass, threaten, or abuse others',
                            'Submit unlawful, false, or offensive materials',
                        ],
                    ],
                    [
                        'title' => 'Accuracy of Information',
                        'paragraphs' => [
                            'We aim to keep the website content accurate and updated. However, MUBCAA does not guarantee that all information will always be complete, current, or error-free.',
                            'Content may be revised, updated, delayed, or removed without prior notice.',
                        ],
                    ],
                    [
                        'title' => 'Third-Party Links',
                        'paragraphs' => [
                            'This website may contain links to third-party websites or services. MUBCAA does not control and is not responsible for the content, policies, availability, or practices of such third-party platforms.',
                        ],
                    ],
                    [
                        'title' => 'Privacy and Cookies',
                        'paragraphs' => [
                            'Your use of this website is also governed by our Privacy Policy and Cookie Policy. By using the website, you agree to those policies as well.',
                        ],
                    ],
                    [
                        'title' => 'Limitation of Liability',
                        'paragraphs' => [
                            'MUBCAA does not guarantee uninterrupted access, error-free operation, or complete security of the website at all times.',
                        ],
                        'lead' => 'MUBCAA shall not be liable for any direct or indirect loss, damage, interruption, or inconvenience arising from:',
                        'bullets' => [
                            'Use of or inability to use the website',
                            'Website downtime or technical issues',
                            'Inaccurate or outdated information',
                            'Unauthorized access to submitted data',
                            'Use of third-party links or services',
                        ],
                    ],
                    [
                        'title' => 'Right to Restrict Access',
                        'paragraphs' => [
                            'MUBCAA reserves the right to suspend, restrict, or terminate access to any user, form submission, listing, or feature if misuse, policy violations, or security concerns are identified.',
                        ],
                    ],
                    [
                        'title' => 'Changes to These Terms',
                        'paragraphs' => [
                            'We may update these Terms & Conditions from time to time. Updated versions will be posted on this page with the revised effective date. Continued use of the website means you accept the updated terms.',
                        ],
                    ],
                    [
                        'title' => 'Governing Law',
                        'paragraphs' => [
                            'These Terms & Conditions shall be governed by the applicable laws of Bangladesh.',
                        ],
                    ],
                ],
                'contact_details' => [
                    'Monipur Uchcha Bidyalaya & College Alumni Association (MUBCAA)',
                    'Email: mubcaa@gmail.com',
                    'Phone: 01700000000',
                    'Address: Road 9, Avenue 9, House 176, Mirpur DOHS',
                    'Website: mubcaa.com',
                ],
            ],
            'cookie-policy' => [
                'eyebrow' => 'Legal',
                'title' => 'Cookie Policy',
                'effective_date' => '6 April 2026',
                'intro' => 'This Cookie Policy explains how Monipur Uchcha Bidyalaya & College Alumni Association (MUBCAA) uses cookies and similar technologies when you visit our website.',
                'lead_note' => 'By continuing to browse or use our website, you agree to the use of cookies as described in this policy.',
                'sections' => [
                    [
                        'title' => 'What Are Cookies?',
                        'paragraphs' => [
                            'Cookies are small text files stored on your device when you visit a website. They help websites function properly, remember your preferences, and understand how users interact with the site.',
                        ],
                    ],
                    [
                        'title' => 'Why We Use Cookies',
                        'lead' => 'We may use cookies to:',
                        'bullets' => [
                            'Ensure the website works properly',
                            'Improve website speed and performance',
                            'Remember user preferences and settings',
                            'Support login or member-related functions',
                            'Understand visitor behavior and traffic patterns',
                            'Improve website content and user experience',
                            'Support security and reduce misuse',
                        ],
                    ],
                    [
                        'title' => 'Types of Cookies We May Use',
                        'bullets' => [
                            'Essential Cookies: These cookies are necessary for the basic operation of the website. Without them, certain parts of the website may not work properly.',
                            'Functional Cookies: These cookies help remember user settings and preferences, making the browsing experience easier and more convenient.',
                            'Analytics and Performance Cookies: These cookies help us understand how visitors use the website, which pages are visited most often, and where improvements are needed.',
                            'Security Cookies: These cookies help identify suspicious activity and protect the website and its users.',
                        ],
                    ],
                    [
                        'title' => 'Third-Party Cookies',
                        'paragraphs' => [
                            'Some features of the website may depend on trusted third-party services such as analytics tools, embedded videos, forms, social media plugins, maps, or payment services. These third-party services may use their own cookies, and those are governed by their own policies.',
                            'MUBCAA does not directly control third-party cookies.',
                        ],
                    ],
                    [
                        'title' => 'Managing Cookies',
                        'paragraphs' => [
                            'Most web browsers allow you to control, block, or delete cookies through browser settings. You may disable cookies if you prefer.',
                            'Please note that disabling cookies may affect certain website functions and may reduce the quality of your browsing experience.',
                        ],
                    ],
                    [
                        'title' => 'Cookie Consent',
                        'paragraphs' => [
                            'Where required, our website may display a cookie banner or notice. By clicking "Accept," continuing to browse, or using site features after seeing such notice, you consent to our use of cookies in accordance with this policy.',
                        ],
                    ],
                    [
                        'title' => 'Updates to This Policy',
                        'paragraphs' => [
                            'We may update this Cookie Policy from time to time to reflect changes in technology, website features, or legal requirements. Any revised version will be posted on this page with the updated effective date.',
                        ],
                    ],
                ],
                'contact_details' => [
                    'Monipur Uchcha Bidyalaya & College Alumni Association (MUBCAA)',
                    'Email: mubcaa@gmail.com',
                    'Phone: 01700000000',
                    'Address: Road 9, Avenue 9, House 176, Mirpur DOHS',
                    'Website: mubcaa.com',
                ],
            ],
            'disclaimer' => [
                'eyebrow' => 'Legal',
                'title' => 'Disclaimer',
                'effective_date' => '6 April 2026',
                'intro' => 'The information provided on the official website of Monipur Uchcha Bidyalaya & College Alumni Association (MUBCAA) is for general information, communication, and alumni engagement purposes only.',
                'lead_note' => 'By using this website, you acknowledge and agree to this Disclaimer.',
                'sections' => [
                    [
                        'title' => 'General Information',
                        'paragraphs' => [
                            'The content on this website is published to share information about the association, membership opportunities, alumni activities, programs, events, notices, announcements, and related matters.',
                            'While we make reasonable efforts to keep the information accurate and updated, MUBCAA does not guarantee that all content will always be complete, correct, reliable, or current.',
                        ],
                    ],
                    [
                        'title' => 'No Automatic Approval',
                        'paragraphs' => [
                            'Any information related to membership, event registration, directory inclusion, donations, contributions, or alumni activities is subject to review, verification, and approval where applicable.',
                            'Submitting a form or expressing interest through the website does not automatically guarantee acceptance, confirmation, approval, or listing.',
                        ],
                    ],
                    [
                        'title' => 'No Professional Advice',
                        'paragraphs' => [
                            'The content available on this website does not constitute legal, financial, technical, or other professional advice. Users should seek appropriate professional consultation where necessary before making decisions based on any content found on this website.',
                        ],
                    ],
                    [
                        'title' => 'External Links Disclaimer',
                        'paragraphs' => [
                            'This website may include links to third-party websites, social platforms, embedded tools, payment gateways, or external services for convenience.',
                            'MUBCAA does not endorse or guarantee the content, accuracy, privacy practices, security, or availability of any third-party website or service. Users access such third-party services at their own risk.',
                        ],
                    ],
                    [
                        'title' => 'User Responsibility',
                        'paragraphs' => [
                            'Users are solely responsible for how they use the information provided on this website. Any action taken based on website content is at the user\'s own discretion and risk.',
                        ],
                    ],
                    [
                        'title' => 'Technical Availability',
                        'paragraphs' => [
                            'We do not guarantee that the website will always remain available, secure, uninterrupted, or free from technical errors, malware, or harmful digital elements.',
                        ],
                    ],
                    [
                        'title' => 'Limitation of Liability',
                        'lead' => 'To the fullest extent permitted by law, MUBCAA shall not be held liable for any direct, indirect, incidental, or consequential loss or damage arising from:',
                        'bullets' => [
                            'Use of this website',
                            'Reliance on information published on the website',
                            'Website downtime or service interruption',
                            'Errors or omissions in content',
                            'Unauthorized access to user data',
                            'Use of third-party links or services',
                        ],
                    ],
                    [
                        'title' => 'Content Changes',
                        'paragraphs' => [
                            'MUBCAA reserves the right to modify, update, remove, or replace any content on this website at any time without prior notice.',
                        ],
                    ],
                ],
                'contact_details' => [
                    'Monipur Uchcha Bidyalaya & College Alumni Association (MUBCAA)',
                    'Email: mubcaa@gmail.com',
                    'Phone: 01700000000',
                    'Address: Road 9, Avenue 9, House 176, Mirpur DOHS',
                    'Website: mubcaa.com',
                ],
            ],
        ]);
    }

    private function translateContent(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->translateContent($item);
            }

            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        if ($value === '' || str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return __($value);
    }
}
