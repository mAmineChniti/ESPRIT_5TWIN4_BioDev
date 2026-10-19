<?php

namespace App\Notifications;

use App\Models\Farm;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FarmApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Farm $farm) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'farm_approved',
            'farm_id' => $this->farm->id,
            'farm_name' => $this->farm->name,
            'title' => __('Farm Approved: :name', ['name' => $this->farm->name]),
            'message' => __('Your farm ":name" has been approved! The official certificate with QR code is ready.', ['name' => $this->farm->name]),
            'action_url' => route('farms.certificate', $this->farm),
        ];
    }
}
