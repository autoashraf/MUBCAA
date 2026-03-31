@extends('layouts.admin')

@section('content')
    <div class="admin-workspace">
        <section class="page-hero admin-page-hero">
            <div>
                <p class="eyebrow">Gallery Management</p>
                <h1>Photo Gallery</h1>
                <p class="lead">Upload photos here and they will appear on the public photo gallery page.</p>
            </div>
        </section>

        <section class="admin-card-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="admin-summary-card rounded-[1.5rem] border border-cyan-100 bg-cyan-50/80 px-5 py-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-800">Gallery Photos</span>
                <strong class="mt-3 block text-3xl font-bold tracking-tight text-slate-950">{{ $gallerySummary['photos'] }}</strong>
            </article>
        </section>

        <section class="workspace-panel">
            <form class="form-card memory-form" method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <label>
                        <span>Photo title</span>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="Optional photo title">
                        @error('title') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Upload photo</span>
                        <input type="file" name="photo" accept="image/*" required>
                        @error('photo') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="form-span-2">
                        <span>Caption</span>
                        <textarea name="caption" rows="4" placeholder="Optional caption for this photo">{{ old('caption') }}</textarea>
                        @error('caption') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
                <button class="button button-primary" type="submit">Upload Photo</button>
            </form>
        </section>

        <section class="workspace-panel admin-queue-panel">
            <div class="memory-archive-grid">
                @forelse ($galleryPhotos as $galleryPhoto)
                    <article class="memory-archive-card">
                        <div class="memory-archive-photo">
                            <img src="{{ asset('storage/'.$galleryPhoto->photo_path) }}" alt="{{ $galleryPhoto->title ?: 'Gallery photo' }}">
                        </div>
                        <div class="memory-archive-body">
                            <div class="memory-archive-meta">
                                <span>{{ $galleryPhoto->uploader->name }}</span>
                                <span>{{ $galleryPhoto->created_at?->format('d M Y') }}</span>
                            </div>
                            <h3>{{ $galleryPhoto->title ?: 'Untitled Photo' }}</h3>
                            @if (filled($galleryPhoto->caption))
                                <p>{{ $galleryPhoto->caption }}</p>
                            @endif
                            <form method="POST" action="{{ route('admin.gallery.destroy', $galleryPhoto) }}">
                                @csrf
                                <button class="button button-secondary" type="submit">Remove Photo</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="list-card memory-empty-state">
                        <p class="aside-label">Gallery Status</p>
                        <h3>No gallery photos yet</h3>
                        <p class="dashboard-copy">Upload the first photo to start the public gallery.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
