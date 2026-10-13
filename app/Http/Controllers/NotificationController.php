<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

/** Foydalanuvchining ilova ichidagi bildirishnomalari (klinika xodimlari va super admin uchun umumiy). */
class NotificationController extends Controller
{
    public function index(Request $r)
    {
        $q = AppNotification::where('user_id', $r->user()->id)->latest('id');
        if ($r->query('f') === 'unread') {
            $q->whereNull('read_at');
        }

        return view('notifications.index', ['items' => $q->paginate(30)->withQueryString(), 'filter' => $r->query('f')]);
    }

    public function feed(Request $r)
    {
        $uid = $r->user()->id;
        $unread = AppNotification::where('user_id', $uid)->whereNull('read_at')->count();
        $items = AppNotification::where('user_id', $uid)->latest('id')->limit(8)->get()->map(fn ($n) => [
            'id' => $n->id, 'title' => $n->title, 'body' => $n->body, 'level' => $n->level, 'url' => $n->url,
            'time' => $n->created_at?->diffForHumans(), 'read' => (bool) $n->read_at,
        ]);

        return response()->json(['unread' => $unread, 'items' => $items]);
    }

    public function read(Request $r, AppNotification $notification)
    {
        abort_unless($notification->user_id === $r->user()->id, 404);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        if ($r->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return $notification->url ? redirect($notification->url) : back();
    }

    public function readAll(Request $r)
    {
        AppNotification::where('user_id', $r->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return $r->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', __("Hammasi o'qilgan deb belgilandi."));
    }
}
