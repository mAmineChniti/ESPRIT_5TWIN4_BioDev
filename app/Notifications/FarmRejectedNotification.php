<?php

namespace App\Notifications;

use App\Models\Farm;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FarmRejectedNotification extends Notification
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
            'type' => 'farm_rejected',
            'farm_id' => $this->farm->id,
            'farm_name' => $this->farm->name,
            'rejection_reason' => $this->farm->rejection_reason,
            'title' => __('Farm Rejected: :name', ['name' => $this->farm->name]),
            'message' => __('Your farm ":name" was rejected by the administrator.', ['name' => $this->farm->name]),
            'action_url' => route('farms.show', $this->farm),
        ];
    }
}
