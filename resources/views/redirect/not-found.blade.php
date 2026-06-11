@extends('redirect.layout')

@section('title', 'QR Not Found')

@section('content')
    <div class="icon">&#10067;</div>
    <h1>This QR code doesn't exist</h1>
    <p>The QR code you scanned could not be found. It may have been deleted by its owner.</p>
    <div class="report">
        <a href="{{ route('qr.report.create', $slug) }}">Report this QR</a>
    </div>
@endsection
