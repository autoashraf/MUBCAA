@extends('layouts.admin')

@section('content')
    <div class="admin-workspace">
        <section class="page-hero admin-page-hero">
            <div>
                <p class="eyebrow">Video Management</p>
                <h1>Video Gallery</h1>
                <p class="lead">Upload videos here and they will appear on the public video gallery page.</p>
            </div>
        </section>

        <section class="admin-card-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Gallery Videos</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $videoSummary['videos'] }}</strong>
            </article>
        </section>

        <section class="workspace-panel">
            <form class="form-card memory-form" method="POST" action="{{ route('admin.videos.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <label>
                        <span>Video title</span>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="Optional video title">
                        @error('title') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Upload video</span>
                        <input type="file" name="video" accept="video/mp4,video/webm,video/ogg,video/quicktime" required>
                        @error('video') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="form-span-2">
                        <span>Caption</span>
                        <textarea name="caption" rows="4" placeholder="Optional caption for this video">{{ old('caption') }}</textarea>
                        @error('caption') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
                <button class="button button-primary" type="submit">Upload Video</button>
            </form>
        </section>

        <section class="workspace-panel admin-queue-panel">
            <div class="memory-archive-grid">
                @forelse ($galleryVideos as $galleryVideo)
                    <article class="memory-archive-card">
                        <div class="video-gallery-frame">
                            <video controls preload="metadata">
                                <source src="{{ asset('storage/'.$galleryVideo->video_path) }}">
                            </video>
                        </div>
                        <div class="memory-archive-body">
                            <div class="memory-archive-meta">
                                <span>{{ $galleryVideo->uploader->name }}</span>
                                <span>{{ $galleryVideo->created_at?->format('d M Y') }}</span>
                            </div>
                            <h3>{{ $galleryVideo->title ?: 'Untitled Video' }}</h3>
                            @if (filled($galleryVideo->caption))
                                <p>{{ $galleryVideo->caption }}</p>
                            @endif
                            <form method="POST" action="{{ route('admin.videos.destroy', $galleryVideo) }}">
                                @csrf
                                <button class="button button-secondary" type="submit">Remove Video</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="list-card memory-empty-state">
                        <p class="aside-label">Gallery Status</p>
                        <h3>No gallery videos yet</h3>
                        <p class="dashboard-copy">Upload the first video to start the public video gallery.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
