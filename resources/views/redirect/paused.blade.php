@extends('redirect.layout')

@section('title', 'QR Paused')

@section('content')
    <div class="icon">&#9208;&#65039;</div>
    <h1>This QR code is currently paused</h1>
    <p>The owner has temporarily deactivated this QR code. Please check back later.</p>
    <div class="report">
        <a href="{{ route('qr.report.create', $slug) }}">Report this QR</a>
    </div>
@endsection
