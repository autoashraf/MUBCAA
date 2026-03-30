@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap narrow">
            <p class="eyebrow">Memories</p>
            <h1>Submit Your Memory</h1>
            <p class="lead">Collect stories from members and preserve the emotional side of the organisation alongside the official record.</p>
        </div>
    </section>

    <section class="section">
        <div class="wrap narrow">
            <form class="form-card memory-form" method="POST" action="{{ route('memories.store') }}">
                @csrf

                <div class="form-grid memory-form-top-grid">
                    <label>
                        <span>Your name</span>
                        <input type="text" value="{{ $memoryUser->name }}" readonly>
                    </label>

                    <label>
                        <span>Email address</span>
                        <input type="email" value="{{ $memoryUser->email }}" readonly>
                    </label>
                </div>

                <label>
                    <span>Memory title</span>
                    <input type="text" name="title" value="{{ old('title') }}" required>
                    @error('title') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Your memory</span>
                    <textarea name="memory" rows="8" required>{{ old('memory') }}</textarea>
                    @error('memory') <small>{{ $message }}</small> @enderror
                </label>

                <button class="button button-primary" type="submit">Send Memory</button>
            </form>
        </div>
    </section>
@endsection
