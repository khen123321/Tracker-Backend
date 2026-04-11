<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewFormRequestAlert extends Notification
{
    use Queueable;

    protected $request;
    protected $user;

    public function __construct($request, $user)
    {
        $this->request = $request;
        $this->user = $user;
    }

    public function via($notifiable): array
    {
        return ['database']; // This saves it to your notifications table
    }

    public function toArray($notifiable): array
    {
        return [
            'intern_name' => $this->user->first_name . ' ' . $this->user->last_name,
            'form_type'   => $this->request->type,
            'request_id'  => $this->request->id,
            'message'     => "submitted a new {$this->request->type} request.",
            'date_submitted' => now()->format('M d, Y h:i A'),
        ];
    }
}