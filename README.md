# MiniBlog

A full-stack social blogging platform built with **Vue 3** and **Laravel 11**, featuring real-time direct messaging, post/comment reactions, image uploads, dark mode, and token-based authentication.

## Tech Stack

- **Frontend:** Vue 3 (Composition API), Pinia, Vue Router, Tailwind CSS 4, Axios, Vite
- **Backend:** Laravel 11, Eloquent ORM, Laravel Sanctum
- **Real-Time:** Pusher WebSockets, Laravel Broadcasting
- **Database:** SQLite / MySQL

## Features

- **Posts & Comments** — Create, edit, and delete posts with image galleries; threaded comments with reactions
- **Emoji Reactions** — Polymorphic reaction system on both posts and comments
- **Real-Time Chat** — Private direct messaging with read receipts, unread badges, draft persistence, and sound notifications
- **Authentication** — Secure token-based auth via Laravel Sanctum with route guards
- **User Profiles** — Editable profiles with avatar uploads
- **Search** — Global full-text search across posts, users, and messages
- **Dark Mode** — Toggle between light and dark themes, persisted in localStorage
- **Responsive UI** — Fully responsive design with Tailwind CSS 4

> 📝 Looking for a copy-paste-ready project description for your CV? See [PROJECT_DESCRIPTION.md](PROJECT_DESCRIPTION.md).

## Frontend Chat Setup

This project now includes a Vue-based direct messaging UI wired to the existing Laravel chat backend. To receive live updates, configure Laravel broadcasting (Pusher, Laravel WebSockets, Reverb, etc.) and expose the following Vite variables in your `.env`:

```env
VITE_PUSHER_APP_KEY=your-websocket-key
VITE_PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_HOST=127.0.0.1       # Optional when using a self-hosted server
VITE_PUSHER_PORT=6001            # Optional when using a self-hosted server
VITE_PUSHER_FORCE_TLS=0          # Set to 1 when using TLS
```

The Vue client authenticates private `conversation.{id}` channels via `/broadcasting/auth`, so ensure Sanctum tokens are accepted by that endpoint. Once broadcasting is configured, conversations and unread counts will update in real time through the web socket connection without requiring Laravel Echo on the frontend.

## Realtime Setup

1. Install dependencies and build assets once: `composer install`, `npm install`, and `npm run build`.
2. Copy `.env.example` to `.env` (if needed), then set `APP_URL`/`VITE_APP_URL` to the URL the SPA will be served from.
3. Set `BROADCAST_CONNECTION=pusher` and provide the credentials for your provider by filling in `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`, and the optional host/port/scheme fields (leave host blank when using hosted Pusher).
4. Mirror those values for the Vite client via `VITE_PUSHER_*` so the browser points at the same WebSocket endpoint; the defaults in `.env.example` already reference the server-side values.
5. Run your preferred broadcasting backend (hosted Pusher, Laravel WebSockets, or Laravel Reverb) and keep a queue worker online (`php artisan queue:listen`) so `MessageSent` events are broadcast immediately.
6. Start the application locally with `php artisan serve` (or Sail) and `npm run dev` for hot module reloading. Authenticate in the SPA; private `conversation.{id}` channels will authorize via Sanctum and deliver realtime updates without Laravel Echo.

### Per-user inbox channel

By default the frontend subscribes to a per-user inbox channel (`private-user.{id}`) so participants instantly see newly created conversations without polling. If you are not running the broadcast channel below, set `VITE_CHAT_INBOX_ENABLED=false` before building the frontend. Otherwise, register the matching channel on the backend:

```php
Broadcast::channel('user.{user}', function (User $current, User $target) {
    return $current->id === $target->id;
});
```

With the channel defined, keep `VITE_CHAT_INBOX_ENABLED=true` (default) so every user’s browser receives `ConversationCreated` events in real time. If you need to inspect socket lifecycle events in the browser, set `VITE_CHAT_DEBUG=true` and rebuild; the chat store will emit `console.log` traces whenever it connects, disconnects, or (un)subscribes channels.
