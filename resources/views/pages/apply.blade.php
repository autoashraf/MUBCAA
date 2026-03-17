@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="wrap">
            <p class="eyebrow">Online Registration</p>
            <h1>Complete your membership registration</h1>
            <p class="lead">This is now the real registration system. Members create an account, choose a membership type, and enter the profile data needed for approval.</p>
        </div>
    </section>

    <section class="section">
        <div class="wrap form-layout">
            <div class="list-card">
                <h3>Membership types and workflow</h3>
                <div class="type-stack">
                    @foreach ($membershipTypes as $type)
                        <article class="type-card">
                            <h4>{{ $type->name }}</h4>
                            <p>{{ $type->description }}</p>
                            <div class="step-list">
                                @foreach ($type->workflowSteps as $step)
                                    <div>Step {{ $step->step_number }}: {{ $step->title }}</div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <form class="form-card" method="POST" action="{{ route('membership.apply.store') }}">
                @csrf

                <div class="form-grid">
                    <label>
                        <span>Full name</span>
                        <input type="text" name="name" value="{{ old('name') }}" required>
                        @error('name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Email address</span>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Password</span>
                        <input type="password" name="password" required>
                        @error('password') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Confirm password</span>
                        <input type="password" name="password_confirmation" required>
                    </label>

                    <label>
                        <span>Phone</span>
                        <input type="text" name="phone" value="{{ old('phone') }}" required>
                        @error('phone') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Membership type</span>
                        <select name="membership_type_id" required>
                            <option value="">Select one</option>
                            @foreach ($membershipTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) old('membership_type_id') === (string) $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('membership_type_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Address</span>
                        <input type="text" name="address" value="{{ old('address') }}" required>
                        @error('address') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>City</span>
                        <input type="text" name="city" value="{{ old('city') }}" required>
                        @error('city') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Country</span>
                        <input type="text" name="country" value="{{ old('country', 'Bangladesh') }}" required>
                        @error('country') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Occupation</span>
                        <input type="text" name="occupation" value="{{ old('occupation') }}">
                        @error('occupation') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Date of birth</span>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
                        @error('date_of_birth') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Emergency contact name</span>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}">
                        @error('emergency_contact_name') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label>
                    <span>Emergency contact phone</span>
                    <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}">
                    @error('emergency_contact_phone') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Short bio</span>
                    <textarea name="bio" rows="5">{{ old('bio') }}</textarea>
                    @error('bio') <small>{{ $message }}</small> @enderror
                </label>

                <div class="action-row">
                    <button class="button button-primary" type="submit">Register</button>
                    <a class="button button-secondary" href="{{ route('login') }}">Already have an account?</a>
                </div>
            </form>
        </div>
    </section>
@endsection
