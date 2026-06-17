<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionGraceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Subscription $subscription,
        public int $graceDay,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your subscription needs attention',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.subscription-grace',
        );
    }
}
