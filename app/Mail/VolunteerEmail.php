<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VolunteerEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $email;
    public $name;
    public $age;
    public $phone;
    public $academic;
    public $occupation;
    public $days;
    public $time;
    public $activities;

    public function __construct($data)
    {
        $this->email = $data->email;
        $this->name = $data->name;
        $this->age = $data->age;
        $this->phone = $data->phone;
        $this->academic = $data->academic;
        $this->occupation = $data->occupation;
        $this->days = $data->days;
        $this->time = $data->time;
        $this->activities = $data->activities;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Volunteer Email'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'volunteer-email-template',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
