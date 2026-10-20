<?php

namespace App\Notifications;

use App\Models\Farm;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FarmPendingValidationNotification extends Notification
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
        $producer = $this->farm->producer_name ?? ($this->farm->user?->name ?? __('A producer'));

        return [
            'type' => 'farm_pending',
            'farm_id' => $this->farm->id,
            'farm_name' => $this->farm->name,
            'producer_name' => $producer,
            'title' => __('New farm pending review: :name', ['name' => $this->farm->name]),
            'message' => __(':producer submitted a new farm request for validation.', ['producer' => $producer]),
            'action_url' => route('farms.show', $this->farm),
        ];
    }
}
