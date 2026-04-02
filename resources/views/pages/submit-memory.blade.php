@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap narrow">
            <div class="page-hero-card memory-hero-card">
                <p class="eyebrow">{{ __('Memories') }}</p>
                <h1>{{ __('Submit Your Memory') }}</h1>
                <p class="lead">{{ __('Share a moment, milestone, or personal story so the association keeps more than records. It keeps the human side of the alumni journey too.') }}</p>
                <div class="memory-hero-meta">
                    <span>{{ __('Write a title') }}</span>
                    <span>{{ __('Add your story') }}</span>
                    <span>{{ __('Attach photos') }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap narrow">
            <form class="form-card memory-form" method="POST" action="{{ route('memories.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-grid memory-form-top-grid">
                    <label>
                        <span>{{ __('Your name') }}</span>
                        <input type="text" value="{{ $memoryUser->name }}" readonly>
                    </label>

                    <label>
                        <span>{{ __('Email address') }}</span>
                        <input type="email" value="{{ $memoryUser->email }}" readonly>
                    </label>
                </div>

                <label>
                    <span>{{ __('Memory title') }}</span>
                    <input type="text" name="title" value="{{ old('title') }}" required>
                    @error('title') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>{{ __('Your memory') }}</span>
                    <textarea name="memory" rows="8" required>{{ old('memory') }}</textarea>
                    @error('memory') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>{{ __('Memory photos') }}</span>
                    <input type="file" name="photos[]" accept="image/*" multiple>
                    <small>{{ __('Add one or more photos to support your memory submission.') }}</small>
                    @error('photos') <small>{{ $message }}</small> @enderror
                    @error('photos.*') <small>{{ $message }}</small> @enderror
                </label>

                <button class="button button-primary" type="submit">{{ __('Send Memory') }}</button>
            </form>
        </div>
    </section>
@endsection
