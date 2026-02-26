# MiniBlog — CV / Resume Project Description

Below are copy-paste-ready descriptions you can use in your CV, resume, portfolio, or cover letter. Pick the format that best fits the space you have.

---

## Short (1–2 lines)

**MiniBlog** — A full-stack social blogging platform built with Vue 3 and Laravel 11, featuring real-time direct messaging (Pusher), post/comment reactions, image uploads, dark mode, and token-based authentication (Sanctum).

---

## Medium (bullet-point format)

**MiniBlog — Full-Stack Social Blogging Platform**
*Vue 3 · Laravel 11 · Tailwind CSS · Pusher · SQLite/MySQL*

- Designed and developed a single-page application (SPA) for creating, sharing, and engaging with blog posts, complete with comments, emoji reactions, and image galleries.
- Implemented real-time private messaging with Pusher WebSockets, including read receipts, per-user inbox channels, and draft persistence.
- Built a secure RESTful API with Laravel Sanctum token authentication, policy-based authorization, and polymorphic media uploads.
- Developed a responsive, dark-mode-enabled UI using Tailwind CSS 4, Vue Router, and Pinia state management with the Composition API.
- Integrated cursor-based pagination, global search (posts, users, messages), and real-time toast notifications for an optimized user experience.

---

## Detailed (paragraph format)

**MiniBlog — Full-Stack Social Blogging Platform**
*Tech Stack: Vue 3 (Composition API), Laravel 11, Tailwind CSS 4, Pinia, Vue Router, Axios, Pusher, Laravel Sanctum, SQLite/MySQL, Vite*

Built a feature-rich, full-stack social blogging platform as a single-page application. The frontend is developed with Vue 3 using the Composition API and `<script setup>` syntax, with Pinia for state management, Vue Router for client-side routing with auth guards, and Tailwind CSS 4 for a fully responsive, dark-mode-enabled interface. The backend exposes a RESTful API through Laravel 11, secured with Sanctum token-based authentication and policy-driven authorization.

Core social features include CRUD operations for posts and comments, polymorphic emoji reactions, image uploads with gallery previews, user profiles with avatar management, and cursor-paginated feeds. A global search system allows users to find posts, users, and messages across the platform.

The application features a real-time direct messaging system powered by Pusher WebSockets. Private conversation channels are authorized through Laravel Broadcasting, enabling instant message delivery, read receipts, and live unread-count badges without polling. A per-user inbox channel broadcasts newly created conversations so all participants see updates immediately. The chat UI supports multiple floating chat windows, draft persistence, message hiding, and sound notifications for incoming messages.

Additional highlights include optimistic UI updates for a snappy user experience, toast notifications for user feedback, atomic database transactions for data integrity, and a clean separation between authenticated and guest layouts.

---

## Skills / Technologies to List

| Category            | Technologies                                                  |
| ------------------- | ------------------------------------------------------------- |
| Frontend            | Vue 3, Composition API, Pinia, Vue Router, Axios, Vite        |
| Backend             | Laravel 11, PHP, Eloquent ORM, Laravel Sanctum                |
| Real-Time           | Pusher, Laravel Broadcasting, WebSockets                      |
| Styling             | Tailwind CSS 4, Responsive Design, Dark Mode                  |
| Database            | SQLite, MySQL, Migrations, Polymorphic Relations               |
| Auth & Security     | Token-based Auth (Sanctum), Policy-based Authorization, CORS  |
| Dev Tools           | Vite, Composer, npm, Git                                      |
