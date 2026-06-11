@extends('redirect.layout')

@section('title', 'QR Expired')

@section('content')
    <div class="icon">&#8987;</div>
    <h1>This QR code has expired</h1>
    <p>This QR code is no longer active. Please contact the owner for an updated link.</p>
    <div class="report">
        <a href="{{ route('qr.report.create', $slug) }}">Report this QR</a>
    </div>
@endsection
