# Leaf It To Me — Project Reference

A plant-sitting marketplace. Owners list plants that need care while they're away; sitters claim listings first-come, first-served. Every plant keeps a permanent **Plant Passport** — a timeline of every sit it's ever had.

---

## Tech Stack

- **Backend:** PHP (procedural, `mysqli` — prepared statements, no PDO, no OOP abstraction)
- **Database:** MySQL via phpMyAdmin
- **Frontend:** Vanilla HTML/CSS/JS with `fetch()` (no Axios on disk despite the original plan)
- **Auth:** JWT via `firebase/php-jwt` (v7.1.0), installed through Composer
- **Local environment:** XAMPP

**Coding conventions carried over from the Bottle project:**

- Every endpoint follows `include → validate → query → respond` with a flat `$response` array
- JSON response shape: `{"success": true/false, "data": {...}}` or `{"success": false, "error": "..."}`
- No frameworks, no query builders — plain SQL strings with `prepare()` / `bind_param()` / `execute()`

---

## Design System

### Colors

```css
:root {
  --paper: #f0f2ea; /* background — pale, slightly green-tinted off-white */
  --fern: #2f5233; /* display text, primary actions */
  --moss: #6b8f5c; /* secondary green, softer UI elements */
  --stamp-ink: #c1521f; /* passport-stamp motif, accents */
  --sunbeam: #e8b23d; /* highlights, hover/active glow */
  --soil: #3a2e22; /* body text (warm dark brown, not black) */
}
```

### Fonts

```css
@import url("https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Karla:wght@400;500;700&family=IBM+Plex+Mono:wght@400;500&display=swap");

:root {
  --font-display: "Lora", serif; /* headlines, wordmark */
  --font-body: "Karla", sans-serif; /* body copy, UI */
  --font-mono: "IBM Plex Mono", monospace; /* dates, timestamps */
}
```

### Theme direction

"Field journal / plant passport" — a passport page with an ink stamp landing on it as the recurring visual motif. Deliberately avoiding the generic plant-app look (sage + terracotta + cream). Tone: warm, a little funny, not twee.

---

## Database Schema

Database name: `leaf`

### `users`

| Field         | Type     | Length | Attributes                  |
| ------------- | -------- | ------ | --------------------------- |
| id            | INT      |        | AUTO_INCREMENT, PRIMARY KEY |
| username      | VARCHAR  | 50     | UNIQUE                      |
| email         | VARCHAR  | 100    | UNIQUE                      |
| password_hash | VARCHAR  | 255    |                             |
| created_at    | DATETIME |        | Default: CURRENT_TIMESTAMP  |

### `plants` — the permanent passport record

| Field      | Type     | Length | Attributes                     |
| ---------- | -------- | ------ | ------------------------------ |
| id         | INT      |        | AUTO_INCREMENT, PRIMARY KEY    |
| owner_id   | INT      |        | FK → users.id                  |
| name       | VARCHAR  | 100    |                                |
| species    | VARCHAR  | 100    |                                |
| care_notes | TEXT     |        | AI-generated once, on creation |
| created_at | DATETIME |        | Default: CURRENT_TIMESTAMP     |

### `sits` — both the open listing and the timeline entry (same row, whole lifecycle)

| Field       | Type     | Length | Attributes                      |
| ----------- | -------- | ------ | ------------------------------- |
| id          | INT      |        | AUTO_INCREMENT, PRIMARY KEY     |
| plant_id    | INT      |        | FK → plants.id                  |
| sitter_id   | INT      |        | FK → users.id, **NULL allowed** |
| start_date  | DATE     |        |                                 |
| end_date    | DATE     |        |                                 |
| status      | VARCHAR  | 20     | Default: `'open'`               |
| sitter_note | TEXT     |        | **NULL allowed**                |
| created_at  | DATETIME |        | Default: CURRENT_TIMESTAMP      |
| claimed_at  | DATETIME |        | **NULL allowed**                |

**Status values** (plain strings, no enum table): `open` → `claimed` → `completed`, or `cancelled`.

**Design notes:**

- No `owner_id` on `sits` — reachable via `sits.plant_id → plants.id → plants.owner_id`, avoids storing the same fact twice.
- No `completed_at` — timeline sorts fine on `start_date` or `created_at`; cut for being redundant with `status = 'completed'`.
- Matching is first-come/first-claim (like Bottle's `draw.php`) — no request/approve flow.

**Build order in phpMyAdmin:** `users` → `plants` → `sits` (each references the one before it). Engine: InnoDB throughout.

---

## Auth Flow (JWT)

1. `signup.php` — validates input, checks username/email uniqueness, hashes password with `password_hash()`, inserts into `users`.
2. `login.php` — verifies password with `password_verify()`, builds a payload (`user_id`, `username`, `exp`), signs it with `JWT::encode()` using the shared secret, returns the token.
3. Client stores the token and sends it back as `Authorization: Bearer <token>` on every request that needs identity.
4. Protected endpoints verify the token's signature against the same secret and pull `user_id` out of the payload — no session table, no database lookup needed to confirm identity.

**Known trade-off:** no server-side "log out" — a stolen token works until it expires. Fine for a class project; would need a blocklist or refresh-token setup to fix properly.

---

## Files Built So Far

### `config.php`

```php
<?php
define('JWT_SECRET', 'change-this-to-a-long-random-string');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'leaf');
?>
```

Generate a real secret once with:

```php
<?php
echo bin2hex(random_bytes(32));
?>
```

### `connection.php`

```php
<?php
require_once __DIR__ . '/config.php';

$mysql = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if($mysql->connect_error){
    die("Connection failed: " . $mysql->connect_error);
}
?>
```

### `signup.php`

Validates username/email/password present, password ≥ 8 characters, checks for duplicate username/email, hashes password, inserts new user. Returns `{"success": true, "data": {"message": "account created"}}` or an `error` string.

### `login.php`

Looks up user by username, verifies password against stored hash, builds and signs a JWT (24-hour expiry), returns `{"success": true, "data": {"token": "...", "username": "..."}}`.

### `auth.php`

JWT verification helper. Defines two functions:

- `get_bearer_token()` — pulls the token out of `Authorization: Bearer <token>` (checks `getallheaders()`, `HTTP_AUTHORIZATION`, and `REDIRECT_HTTP_AUTHORIZATION` so XAMPP setups always find it).
- `require_auth()` — verifies signature (HS256) against `JWT_SECRET`, returns the decoded payload (contains `user_id`, `username`). On failure responds with JSON and exits: `missing authorization header` / `token expired` / `invalid token`.

Usage from any protected endpoint:

```php
require_once __DIR__ . '/auth.php';
$user = require_auth(); // $user["user_id"]
```

Note: both `login.php` and `auth.php` load the Composer autoloader via `__DIR__ . '/../vendor/autoload.php'` (vendor/ sits at the project root, not inside server/).

**Full code for both is in the conversation history — ask if you need them reprinted.**

---

## Setup Notes

**Composer / firebase/php-jwt:**

```bash
composer require firebase/php-jwt
```

If you hit `The zip extension and unzip/7z commands are both missing`: open `C:\xampp\php\php.ini`, find `;extension=zip`, remove the leading semicolon, save, **restart your terminal**, then re-run the command.

Any file using JWT needs (note: `vendor/` sits at the project root, so from inside `server/` use `__DIR__ . '/../vendor/autoload.php'`):

```php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
```

**`config.php` should never be web-accessible directly** — only reached via `include`/`require`. If using git, `.gitignore` it and commit a `config.example.php` with fake values instead.

---

## Still To Build

- [ ] `create-plant.php` — creates a plant passport, triggers one-time AI care-notes generation
- [ ] `create-sit.php` — owner posts a listing (open sit) for one of their plants
- [ ] `claim-sit.php` — sitter claims an open sit (first-come, first-served)
- [ ] `complete-sit.php` — sitter marks a sit done, adds `sitter_note`
- [ ] `passport.php` — returns one plant's full record + its `sits` timeline
- [ ] `find-listing.php` backend — browsable list of open sits
- [x] Frontend wiring for `login.html`, `signup.html` — forms submit via `fetch()` (no Axios on disk despite the tech-stack note), store token/username in `localStorage`, auth-aware nav (greeting + logout)
- [ ] Frontend wiring for `create-listing.html`, `find-listing.html` (backend endpoints not built yet)
- [ ] Passport-page visual design (parchment/stamp motif)
- [x] Nav + footer — already built, reused across pages

> **Path note (XAMPP):** the project lives at `C:\xampp\htdocs\Final-Project`, so it's served under `http://localhost/Final-Project/...`. All HTML/JS links are **relative** (never root-absolute like `/client/...`) so it works in any subdirectory. Logo asset is `client/images/logo-placeholder.svg`.

---

## Frontend Pieces Already Built

- **Nav:** logo + site name on the left (acts as Home link), Create / Find / Login / Sign Up on the right
- **Footer:** copyright + Instagram/Facebook/TikTok links, styled with `--fern` background
- **Hero section:** dual CTA ("List a Plant" / "Find a Sit"), passport-page visual with a stamp mid-fall
- **File structure so far:** `client/index.html`, `client/pages/create-listing.html`, `client/pages/find-listing.html`, `client/pages/login.html`, `client/pages/signup.html`, `client/scripts/auth.js`, `client/images/logo-placeholder.svg`
