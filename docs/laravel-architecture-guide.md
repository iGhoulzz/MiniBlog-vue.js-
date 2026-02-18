# Laravel Architecture Guide — When & Why to Use Each Layer

> A practical guide based on the MiniBlog conversation system refactor.
> Written: February 16, 2026

---

## Table of Contents

1. [The Big Picture](#the-big-picture)
2. [How the Controller Connects to the Service](#how-the-controller-connects-to-the-service)
3. [Business Logic vs. Controller Logic](#business-logic-vs-controller-logic)
4. [Layer 1: Form Requests — Validation](#layer-1-form-requests--validation)
5. [Layer 2: Policies — Authorization](#layer-2-policies--authorization)
6. [Layer 3: Controllers — Coordination](#layer-3-controllers--coordination)
7. [Layer 4: Services — Business Logic](#layer-4-services--business-logic)
8. [Layer 5: Model Scopes — Reusable Queries](#layer-5-model-scopes--reusable-queries)
9. [When to Use What — Decision Guide](#when-to-use-what--decision-guide)
10. [Real Example: The Conversation Refactor](#real-example-the-conversation-refactor)

---

## The Big Picture

Every HTTP request in Laravel passes through several layers. Each layer has **one specific job**:

```
HTTP Request
    │
    ▼
┌──────────────────────────┐
│  1. Routes               │  "Which controller handles this URL?"
└──────────┬───────────────┘
           ▼
┌──────────────────────────┐
│  2. Middleware            │  "Is the user logged in? Rate limited?"
└──────────┬───────────────┘
           ▼
┌──────────────────────────┐
│  3. Form Request         │  "Is the incoming data valid?"
└──────────┬───────────────┘
           ▼
┌──────────────────────────┐
│  4. Controller           │  "Coordinate the work + return a response"
└──────────┬───────────────┘
           ▼
┌──────────────────────────┐
│  5. Policy               │  "Is this user ALLOWED to do this?"
└──────────┬───────────────┘
           ▼
┌──────────────────────────┐
│  6. Service              │  "Execute the business logic"
└──────────┬───────────────┘
           ▼
┌──────────────────────────┐
│  7. Model + Scopes       │  "Talk to the database"
└──────────┘───────────────┘
           ▼
       Response
```

The key principle: **each layer does ONE thing well and doesn't worry about the others.**

---

## How the Controller Connects to the Service

The connection happens through **Dependency Injection (DI)**. Here's exactly how it works
step by step.

### What You Write

```php
class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService
    ) {}
}
```

### What Happens Behind the Scenes (Automatically)

When Laravel receives a request that routes to `ConversationController`:

```
Step 1:  Laravel sees: "I need to create a ConversationController"
              │
              ▼
Step 2:  Laravel reads the constructor: "It needs a ConversationService"
              │
              ▼
Step 3:  Laravel creates: new ConversationService()
              │
              ▼
Step 4:  Laravel creates: new ConversationController($thatService)
              │
              ▼
Step 5:  Your controller now has $this->conversationService ready to use
```

**You never write `new ConversationService()` yourself.** Laravel's **Service Container**
does it for you. This is what people mean by "dependency injection" — the dependency
(the service) is *injected* into your class automatically.

### Why Not Just Do `new ConversationService()` Manually?

You *could* write this:

```php
public function store(StoreConversationRequest $request)
{
    $service = new ConversationService();  // ← this works but is BAD practice
    $service->findOrCreateConversation(...);
}
```

But there are problems:

| Manual `new`                                    | Dependency Injection                          |
|-------------------------------------------------|-----------------------------------------------|
| ❌ Creates a new instance every method call      | ✅ One instance shared across all methods      |
| ❌ Hard to test (can't swap with a mock)         | ✅ Easy to test (swap with a fake in tests)    |
| ❌ If Service has its own dependencies, you must build them manually | ✅ Laravel resolves the whole chain automatically |

### The Simplest Way to Think About It

> **The `__construct` is like a shopping list.**
> You list what you need, and Laravel delivers it.

```php
// "I need a ConversationService please"
public function __construct(
    private readonly ConversationService $conversationService
) {}

// Now I can use it in ANY method of this controller:
public function index() {
    $this->conversationService->getConversationsForUser(...);
}

public function store() {
    $this->conversationService->findOrCreateConversation(...);
}

public function markAsRead() {
    $this->conversationService->markAsRead(...);
}
```

### Do You Need to Register the Service Anywhere?

**No!** For simple service classes (no interface, no constructor parameters), Laravel
resolves them automatically. You just:

1. Create the file:  `app/Services/ConversationService.php`
2. Type-hint it in the controller constructor
3. Done — Laravel handles the rest

You only need to register in `AppServiceProvider` if your service:
- Implements an interface (e.g., `ConversationServiceInterface`)
- Needs constructor parameters that Laravel can't guess
- Needs to be a singleton (one shared instance for the entire app)

---

## Business Logic vs. Controller Logic

This is the most confusing part for beginners. Here's the clearest way to think about it.

### The Test: "Does This Care About HTTP?"

Ask yourself: **"Would this logic change if I used it from a CLI command instead of a web request?"**

- If **YES** → it's **controller logic** (HTTP-specific stuff)
- If **NO** → it's **business logic** (your application's rules)

### Line-by-Line Example from the OLD ConversationController

Let's label every single line in the original `store()` method:

```php
public function store(Request $request)                                // CONTROLLER — accepting HTTP request
{
    $user = $request->user();                                          // CONTROLLER — getting user from HTTP session
    $attributes = $request->validated();                               // CONTROLLER — extracting validated HTTP input

    $participantIds = array_unique(                                    // BUSINESS — deduplicating participants
        array_merge([$user->id], $attributes['user_ids'])              // BUSINESS — merging sender with recipients
    );
    sort($participantIds);                                             // BUSINESS — sorting for comparison

    $existingConversation = Conversation::whereHas('users', ...)       // BUSINESS — finding existing conversation
        ->withCount('users')                                           // BUSINESS — counting participants
        ->get()                                                        // BUSINESS — executing query
        ->filter(function ($conv) use ($participantIds) {              // BUSINESS — exact matching logic
            return $conv->users_count === count($participantIds)
                && $conv->users->pluck('id')->sort()->...;
        })
        ->first();                                                     // BUSINESS — getting the result

    if ($existingConversation) {                                       // BUSINESS — decision branching
        $existingConversation->users()->updateExistingPivot(...);      // BUSINESS — un-deleting
        $existingConversation->touch();                                // BUSINESS — updating timestamp
        Message::create([...]);                                        // BUSINESS — creating message

        return response()->json($existingConversation, 200);           // CONTROLLER — HTTP response
    }

    $conversation = DB::transaction(function () use (...) {            // BUSINESS — database transaction
        $newConversation = Conversation::create();                     // BUSINESS — creating conversation
        $newConversation->users()->attach($participantIds);            // BUSINESS — attaching users
        Message::create([...]);                                        // BUSINESS — creating message
        return $newConversation->load(...);                            // BUSINESS — loading relations
    });

    foreach ($conversation->users as $participant) {                   // BUSINESS — event broadcasting
        event(new ConversationCreated($conversation, $participant));   // BUSINESS — dispatching event
    }

    return response()->json($conversation, 201);                       // CONTROLLER — HTTP response
}
```

**Count it up:**
- Controller logic (HTTP stuff): **~5 lines**
- Business logic (application rules): **~25 lines**

The business logic was drowning the controller. It was hard to read and hard to reuse.

### After Refactor — Clean Separation

**Controller** — only HTTP-aware lines:

```php
public function store(StoreConversationRequest $request)
{
    $attributes = $request->validated();                    // CONTROLLER — get HTTP input

    $conversation = $this->conversationService             // DELEGATE to service
        ->findOrCreateConversation(
            $request->user(),                              // CONTROLLER — get user from request
            $attributes['user_ids'],
            $attributes['content']
        );

    $status = $conversation->wasRecentlyCreated ? 201 : 200;  // CONTROLLER — HTTP status

    return response()->json($conversation, $status);       // CONTROLLER — HTTP response
}
```

**Service** — no HTTP knowledge at all:

```php
public function findOrCreateConversation(User $sender, array $userIds, string $content): Conversation
{
    // Notice: the parameters are User, array, string — NOT Request!
    // This method has no idea it's being called from a web request.

    $participantIds = collect([$sender->id, ...$userIds])
        ->unique()->sort()->values()->all();

    $existing = $this->findExactConversation($participantIds);

    if ($existing) {
        return $this->addMessageToExisting($existing, $sender, $content);
    }

    return $this->createNewConversation($sender, $participantIds, $content);
}
```

### The Cheat Sheet — What Goes Where

| CONTROLLER logic (HTTP stuff)      | BUSINESS logic (app rules)                    |
|------------------------------------|-----------------------------------------------|
| `$request->validated()`            | Database queries & transactions               |
| `$request->user()`                 | Creating / updating / deleting models         |
| `$this->authorize(...)`            | Event dispatching                             |
| `response()->json(...)`            | Sending emails / notifications                |
| `return redirect(...)`             | Calculations and rules                        |
| HTTP status codes (200, 201, 404)  | If/else business decisions                    |
| Reading cookies / session          | Calling external APIs                         |
| Setting headers                    | Data transformations                          |

### The Restaurant Analogy

Think of it like a restaurant:

```
┌──────────────────────────────────────────────────────────┐
│  CONTROLLER = The Waiter                                  │
│                                                           │
│  • Takes the customer's order (Request)                   │
│  • Checks if they're allowed to order alcohol (Policy)    │
│  • Passes the order to the kitchen (Service)              │
│  • Brings the food back to the table (Response)           │
│                                                           │
│  ⚠️  The waiter does NOT cook the food!                   │
├──────────────────────────────────────────────────────────┤
│  SERVICE = The Chef                                       │
│                                                           │
│  • Receives the order from the waiter                     │
│  • Knows the recipes (business rules)                     │
│  • Prepares the food (creates/queries models)             │
│  • Doesn't care WHO ordered it or HOW it's served         │
│                                                           │
│  ⚠️  The chef does NOT talk to customers!                 │
├──────────────────────────────────────────────────────────┤
│  MODEL + SCOPES = The Ingredients & Kitchen Tools         │
│                                                           │
│  • The raw materials (database tables)                    │
│  • Pre-made sauces (scopes = reusable query filters)      │
│  • The chef uses these to cook                            │
└──────────────────────────────────────────────────────────┘
```

**The waiter (controller) never cooks. The chef (service) never talks to customers.**

If the restaurant switches from dine-in to delivery (web to CLI), the **waiter changes**
(how orders are taken), but the **chef stays the same** (recipes don't change).
That's exactly why we separate the two.

---



## Layer 1: Form Requests — Validation

### What It Does

A Form Request validates incoming HTTP data **before** the controller even runs.
If validation fails, Laravel automatically returns a 422 error with the validation messages.

### File Location

```
app/Http/Requests/StoreConversationRequest.php
```

### Example from MiniBlog

```php
class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any authenticated user can create a conversation
    }

    public function rules(): array
    {
        return [
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id|distinct',
            'content'    => 'required|string|min:1|max:1000',
        ];
    }
}
```

### How the Controller Uses It

```php
// Instead of Request $request, type-hint the Form Request:
public function store(StoreConversationRequest $request)
{
    $attributes = $request->validated(); // already validated!
    // ... no validation code needed here
}
```

### Without a Form Request (BAD — validation clutters the controller)

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'user_ids'   => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id|distinct',
        'content'    => 'required|string|min:1|max:1000',
    ]);

    // ... 50 more lines of business logic mixed in
}
```

### Comparison

| Aspect                | Inline validate()            | Form Request Class           |
|-----------------------|------------------------------|------------------------------|
| Reusability           | ❌ Copy-paste if used elsewhere | ✅ Reuse the same class       |
| Controller size       | ❌ Adds 5-10 lines per method  | ✅ Zero validation in controller |
| Custom error messages | ❌ Clutters the method         | ✅ Clean `messages()` method  |
| Authorization         | ❌ Mixed with validation       | ✅ Separate `authorize()` method |
| Testing               | ❌ Must test through HTTP      | ✅ Can unit test rules directly |

### When to Skip Form Requests

- For very simple endpoints with 1-2 rules (e.g., `'id' => 'required|integer'`)
- If validation is used in exactly one place and is trivial
- Quick prototyping / early development

### How to Create One

```bash
php artisan make:request StoreConversationRequest
```

---

## Layer 2: Policies — Authorization

### What It Does

Policies answer one question: **"Can THIS user do THIS action on THIS model?"**

### File Location

```
app/Policies/ConversationPolicy.php
```

### Example from MiniBlog

```php
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        // Only participants can view a conversation
        return $conversation->users->contains($user->id);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        // Only participants can delete a conversation
        return $conversation->users->contains($user->id);
    }
}
```

### How the Controller Uses It

```php
public function show(Conversation $conversation)
{
    $this->authorize('view', $conversation);
    // If user is NOT a participant → automatic 403 Forbidden
    // If authorized → code continues normally
}
```

### Without a Policy (BAD — authorization clutters the controller)

```php
public function show(Conversation $conversation)
{
    if (!$conversation->users->contains(auth()->id())) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    // ... rest of method
}
```

### Why Policies Are Better

| Aspect              | Inline if checks             | Policy Class                        |
|---------------------|------------------------------|-------------------------------------|
| Consistency         | ❌ Each dev writes differently | ✅ One source of truth               |
| Reusability         | ❌ Copy-paste the same check  | ✅ `$this->authorize()` everywhere   |
| Blade integration   | ❌ Can't use in views         | ✅ `@can('view', $conversation)`     |
| API / Gates         | ❌ Can't use in gates         | ✅ `Gate::allows('view', $conversation)` |

### When to Skip Policies

- When authorization is simple and NOT model-specific
  (e.g., "only admins access this") → use **Middleware** instead
- When there's no specific model to authorize against

### How to Create One

```bash
php artisan make:policy ConversationPolicy --model=Conversation
```

---

## Layer 3: Controllers — Coordination

### What It SHOULD Do (thin controller)

1. Accept the validated request (via Form Request)
2. Check authorization (via Policy)
3. **Delegate** to a Service for business logic
4. Return an HTTP response

### Example from MiniBlog (After Refactor)

```php
class ConversationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ConversationService $conversationService
    ) {}

    public function store(StoreConversationRequest $request)
    {
        $attributes = $request->validated();

        $conversation = $this->conversationService->findOrCreateConversation(
            $request->user(),
            $attributes['user_ids'],
            $attributes['content']
        );

        $status = $conversation->wasRecentlyCreated ? 201 : 200;

        return response()->json($conversation, $status);
    }
}
```

Notice:
- No validation code (Form Request handles it)
- No authorization in `store()` (any authenticated user can create — handled by middleware)
- No business logic (Service handles it)
- Just: get data → delegate → respond

### What a Controller Should NEVER Do

- ❌ Complex database queries with multiple joins
- ❌ Business logic (if X then Y else Z branching)
- ❌ Event dispatching
- ❌ Sending emails or notifications
- ❌ Validation rules (use Form Requests)
- ❌ Authorization checks beyond `$this->authorize()` (use Policies)

### Constructor Injection Explained

```php
public function __construct(
    private readonly ConversationService $conversationService
) {}
```

This is **dependency injection**. Laravel's service container automatically creates a
`ConversationService` instance and passes it to the constructor. You don't need to
do `new ConversationService()` anywhere.

- `private` — only this class can access it
- `readonly` — can't be accidentally overwritten after construction

---

## Layer 4: Services — Business Logic

### What It Does

A Service class contains **business rules** — the logic that defines how your
application works regardless of whether it's accessed via web, API, CLI, or a queue job.

### File Location

```
app/Services/ConversationService.php
```

### Example from MiniBlog

```php
class ConversationService
{
    /**
     * Find an existing conversation between exactly the given participants,
     * or create a new one. Either way, attach the first message.
     */
    public function findOrCreateConversation(User $sender, array $userIds, string $content): Conversation
    {
        // Business rule: deduplicate + sort participant IDs
        $participantIds = collect([$sender->id, ...$userIds])
            ->unique()->sort()->values()->all();

        // Business rule: reuse existing conversation if same participants
        $existing = $this->findExactConversation($participantIds);

        if ($existing) {
            return $this->addMessageToExisting($existing, $sender, $content);
        }

        return $this->createNewConversation($sender, $participantIds, $content);
    }
}
```

### Why Services Are Powerful — Reusability

The same service can be used from ANYWHERE:

```php
// From a Controller (HTTP):
$conversation = $this->conversationService->findOrCreateConversation($user, $ids, $content);

// From an Artisan command (CLI):
$conversation = app(ConversationService::class)->findOrCreateConversation($user, $ids, $content);

// From a Job (queue):
$conversation = $this->conversationService->findOrCreateConversation($bot, $ids, $welcome);
```

The business logic is **framework-independent**. It doesn't know about HTTP,
requests, or responses.

### Service vs. Controller — What Goes Where?

| Belongs in Controller              | Belongs in Service                        |
|------------------------------------|-------------------------------------------|
| `$request->validated()`           | Database transactions                     |
| `$this->authorize()`             | Complex queries across multiple models    |
| `response()->json()`             | Event dispatching                         |
| HTTP status codes (200, 201, 404) | Sending emails/notifications              |
| Redirects                          | Business rules (if X then Y else Z)       |
| Session/cookie management          | External API calls                        |

### When to Skip Services

| Controller Complexity                                | Use a Service?                |
|------------------------------------------------------|-------------------------------|
| Simple CRUD (1-3 lines of logic)                     | ❌ No — keep in controller    |
| Medium (some conditions, 10-20 lines)                | ⚠️ Maybe — use your judgment |
| Complex (multiple models, transactions, events)      | ✅ Yes — always              |

**Example of when a Service is overkill:**

```php
// This is fine directly in a controller — no Service needed
public function destroy(Post $post)
{
    $this->authorize('delete', $post);
    $post->delete();
    return response()->json(null, 204);
}
```

Creating a `PostService` with a `deletePost()` method that just calls `$post->delete()`
would be over-engineering.

---

## Layer 5: Model Scopes — Reusable Queries

### What It Does

A scope is a **reusable query building block** defined on the model.
Think of it as a named filter you can chain onto queries.

### File Location

Scopes live inside the model file itself:

```
app/Models/Message.php
```

### Example from MiniBlog

```php
class Message extends Model
{
    /**
     * Scope: exclude messages that the given user has hidden.
     */
    public function scopeVisibleTo($query, User $user)
    {
        return $query->whereDoesntHave('hiddenByUsers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}
```

### How It's Used

```php
// Clean and readable — reads like English
$query->visibleTo($user)->with('user', 'readByUsers');
```

### Without a Scope (BAD — duplicated query logic)

```php
// In ConversationController@index:
$query->whereDoesntHave('hiddenByUsers', function ($q) use ($user) {
    $q->where('user_id', $user->id);
});

// In ConversationController@show (SAME CODE copy-pasted):
$query->whereDoesntHave('hiddenByUsers', function ($q) use ($user) {
    $q->where('user_id', $user->id);
});
```

### Common Scope Examples

```php
class Post extends Model
{
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByAuthor($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}

// Usage — scopes chain beautifully:
Post::published()->byAuthor($userId)->recent()->paginate(10);
```

### Naming Convention

- Method name must start with `scope` (e.g., `scopePublished`)
- When calling it, drop the `scope` prefix (e.g., `Post::published()`)
- Laravel handles this automatically via the `__callStatic` magic method

### When to Use Scopes

- **Always** when you write the same `where`/`whereHas` clause in 2+ places
- When the filter is a **concept of the model** (e.g., "published", "visible", "active")
- When you want queries to read like English

---

## When to Use What — Decision Guide

### Quick Decision Cheat Sheet

| Question                                          | If YES →         | If NO →                    |
|---------------------------------------------------|------------------|----------------------------|
| Is validation used in 2+ endpoints?               | Form Request     | Inline `validate()` is fine |
| Does authorization depend on a specific model?    | Policy           | Middleware is fine          |
| Is the controller method > 20 lines of logic?     | Service class    | Keep in controller         |
| Is the same query filter used in 2+ places?       | Model Scope      | Inline `where()` is fine   |
| Is there a DB transaction with multiple models?   | Service class    | Keep in controller         |
| Are events/emails fired as part of business logic?| Service class    | Keep in controller         |

### Architecture by Feature Complexity

**Small/Simple Feature (basic CRUD):**
```
Route → Controller (with inline validation) → Model
```
Totally fine. Don't create 4 extra files for a 10-line controller.

**Medium Feature:**
```
Route → Form Request → Controller → Model (with scopes)
```
Add Form Requests and scopes when you see duplication. Skip the Service.

**Complex Feature (like the messaging system):**
```
Route → Form Request → Controller → Policy → Service → Model (with scopes)
```
Use the full stack when complexity demands it.

### The Golden Rule

> **Start simple. Refactor when complexity grows.**
>
> Don't pre-build Services for every controller on day one.
> Add them when a controller method grows beyond ~20 lines of business logic,
> or when the same logic is needed in multiple places.

---

## Real Example: The Conversation Refactor

### Before (Fat Controller — 186 lines)

Everything was in `ConversationController.php`:
- Validation mixed with business logic
- Complex queries inline in controller methods
- Hidden-message filtering duplicated in `index()` and `show()`
- Event broadcasting mixed with database operations
- `store()` method was 53 lines handling 4+ responsibilities

### After (Clean Architecture)

| File                              | Role              | Lines |
|-----------------------------------|--------------------|-------|
| `StoreConversationRequest.php`    | Validation         | 34    |
| `ConversationPolicy.php`         | Authorization      | 27    |
| `ConversationController.php`     | HTTP coordination  | 90    |
| `ConversationService.php`        | Business logic     | 195   |
| `Message.php` (scope added)      | Reusable query     | 77    |

### What Each File Handles

```
StoreConversationRequest  →  "Are user_ids valid? Is content provided?"
ConversationPolicy        →  "Is this user a participant in this conversation?"
ConversationController    →  "Validate → Authorize → Delegate → Respond"
ConversationService       →  "Find/create conversations, mark as read, filter messages, broadcast events"
Message::scopeVisibleTo() →  "Exclude messages hidden by this user" (used in index + show)
```

### The Key Improvement

The controller's `store()` went from this (53 lines of mixed concerns):

```php
public function store(Request $request)
{
    // validation...
    // deduplicate IDs...
    // query for existing conversation...
    // filter exact match...
    // un-delete if exists...
    // create message...
    // OR create new conversation in transaction...
    // attach users...
    // create message...
    // load relationships...
    // broadcast events...
    // return response...
}
```

To this (12 lines of clean coordination):

```php
public function store(StoreConversationRequest $request)
{
    $attributes = $request->validated();

    $conversation = $this->conversationService->findOrCreateConversation(
        $request->user(),
        $attributes['user_ids'],
        $attributes['content']
    );

    $status = $conversation->wasRecentlyCreated ? 201 : 200;

    return response()->json($conversation, $status);
}
```

---

## Summary

These patterns exist to **manage complexity, not create it**. Use them when they
save you time and brainpower, not just to follow a rule. A simple 3-line
`destroy()` method doesn't need a Service class, but a 53-line `store()` method
that handles finding, creating, messaging, and broadcasting absolutely does.

**Start simple → notice pain points → refactor into the right layer.**

That's the Laravel way. 🚀
