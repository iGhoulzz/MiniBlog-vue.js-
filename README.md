<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

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
