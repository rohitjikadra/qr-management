@extends('redirect.layout')

@section('title', 'Report QR')

@section('content')
    <div class="icon">&#128680;</div>
    <h1>Report this QR code</h1>
    <p>If this QR code leads to spam, phishing or harmful content, let us know.</p>

    @if (session('reported'))
        <p class="success">Thank you. Our team will review this QR code.</p>
    @else
        <form method="POST" action="{{ route('qr.report.store', $slug) }}">
            @csrf
            <label for="reason">What's wrong with it?</label>
            <textarea id="reason" name="reason" required minlength="10" maxlength="500"
                placeholder="Describe the problem...">{{ old('reason') }}</textarea>
            @error('reason')
                <div class="error">{{ $message }}</div>
            @enderror
            <button type="submit">Submit report</button>
        </form>
    @endif
@endsection
