<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function visit(string $notificationId): RedirectResponse
    {
        $notification = Auth::user()->notifications()->whereKey($notificationId)->firstOrFail();
        $url = $this->safeInternalUrl($notification->data['url'] ?? null);
        abort_unless($url !== null, 404);
        $notification->markAsRead();

        return redirect()->to($url);
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render(): View
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->limit(8)->get();
        $notifications->each(function (DatabaseNotification $notification): void {
            $notification->setAttribute('safe_url', $this->safeInternalUrl($notification->data['url'] ?? null));
        });

        return view('livewire.notifications.notification-bell', [
            'unreadCount' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    private function safeInternalUrl(mixed $url): ?string
    {
        if (! is_string($url)
            || ! str_starts_with($url, '/')
            || str_starts_with($url, '//')
            || str_contains($url, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return $url;
    }
}
