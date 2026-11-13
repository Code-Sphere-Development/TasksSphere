# Anmeldung über AccountSphere (SSO)

Stand: 2026-11-13

## Ziel

TasksSphere authentifiziert nicht mehr selbst (Jetstream/Fortify), sondern über
**AccountSphere** mit dem Paket `code-sphere/oauth` – wie TimeSphere, InvoiceSphere,
DashSphere und UptimeSphere. Konten gehören AccountSphere, Aufgaben bleiben in TasksSphere.

Vorlage ist **UptimeSphere** (SSO im Web, Sanctum-Tokens für die API bleiben). Abweichungen
von den Schwesterprojekten sind unten begründet.

## Entscheidungen

| Frage | Entscheidung | Grund |
|---|---|---|
| Bestandskonten | **Manuelle Kopplung** per `php artisan users:link-codesphere` | Kein Übernahmerisiko über E-Mail-Gleichheit; überschaubare Nutzerzahl |
| Namen/E-Mails anderer Nutzer | **Lokaler Snapshot** (`first_name`, `last_name`, `email`, `language`), bei jedem Login aus AccountSphere überschrieben | Haushalte, Zuweisungen, „erledigt von“, API-JSON und Konsolenbefehle brauchen sie ohne Session des Betroffenen. Präzedenz: InvoiceSphere `NotificationSubscription` |
| `POST /api/login` | **Proxy zu AccountSphere** `POST /api/auth/login`, Antwortformat unverändert | Mobile-App, Home Assistant und Gehirn-Agent bleiben ohne Update lauffähig |
| `POST /api/register`, `/api/forgot-password` | **410 Gone** mit Verweis auf AccountSphere | Konten entstehen nur noch dort |

## Änderungen

### Abhängigkeiten
- `code-sphere/oauth: @dev` (wie TimeSphere/DashSphere, ohne `repositories`-Eintrag).
- `laravel/jetstream` (und damit Fortify) entfällt, `laravel/sanctum` bleibt.
- `php` bleibt `^8.3`: Die aktuelle Paketversion (`c2fc6ab`) braucht keinen PHP-8.5-Pipe-Operator
  mehr; die Schwesterprojekte stehen noch auf `88bac10`.

### Datenbank
- `2026_04_10_000000_add_codesphere_fields_to_users_table.php` **überschreibt** die
  gleichnamige Paket-Migration (Laravel schlüsselt Migrationen nach Dateinamen). Grund: die
  Paketversion legt `codesphere_id` als `NOT NULL UNIQUE` an – bei mehr als einem Bestandsnutzer
  bekämen alle den Wert 0 und die Migration bräche ab. Hier ist die Spalte `nullable`, bis
  ein Konto gekoppelt ist.
- Neue Migration entfernt `password`, `remember_token`, `email_verified_at`, `two_factor_*`,
  `profile_photo_path`, `current_team_id` sowie `password_reset_tokens`. `email` verliert den
  Unique-Index (ein noch ungekoppeltes Altkonto darf dieselbe Adresse tragen wie der neue
  SSO-Stub), behält aber einen normalen Index für die Haushaltssuche. `first_name`/`last_name`
  werden `nullable`.
- `personal_access_tokens` bleibt (Sanctum).
- Fremdschlüssel auf `users.id` (Aufgaben, Listen, Haushalte, Geräte, Zuweisungen,
  Erledigungen) bleiben unberührt, weil die lokale `id` bleibt.

### User-Modell
- `HasCodeSphereIdentity` statt `TwoFactorAuthenticatable`/`HasProfilePhoto`.
- **Wichtig:** Der Trait liefert `name`/`email`/`avatar` aus der Session – also immer die des
  *eingeloggten* Nutzers, auch wenn man `$anderesMitglied->email` liest. Das Modell
  überschreibt diese Accessoren deshalb mit dem lokalen Snapshot.
- `profile_photo_url` bleibt im JSON (Mobile-App), berechnet als Initialen-Avatar.

### Web
- Package-Routen `/login`, `/auth/redirect`, `/auth/callback`, `/auth/logout`.
- Middleware `auth:sanctum, jetstream.auth_session, verified` → `auth`.
- Listener `SyncProfileAfterCodeSphereLogin` schreibt den Snapshot (AccountSphere liefert
  nur `name`; aufgeteilt am ersten Leerzeichen wie bei der früheren Namens-Migration).
- Profilseite neu (`/user/profile`): Konto-Karte mit Link „In CodeSphere bearbeiten“,
  Benachrichtigungen (bestehend), Konto löschen (Bestätigung per E-Mail statt Passwort; die
  Datenschutzerklärung sagt diese Funktion zu).
- API-Tokens (`/user/api-tokens`): eigener Livewire-Manager statt Jetstream; Tokens mit `*`,
  siehe Entscheidung „Agent behält Vollrechte“.
- Entfernt: Login/Registrierung/Passwort/2FA/Sitzungen/Konto-löschen-Views, Fortify-Actions,
  Team-Reste.

### API
- `/api/login`: Prüfung gegen AccountSphere, Upsert per `codesphere_id`, Snapshot, Sanctum-
  Token mit `device_name` (Token-Name steuert weiterhin `TaskSource`, z. B. `gehirn-agent`).
- Pro API-Request **kein** Aufruf von AccountSphere (anders als UptimeSphere): TasksSphere
  braucht keine Gruppen, damit entfällt auch dessen „403 nach 30 Tagen“.
- `PUT /api/profile`: Name/E-Mail/Passwort → 422 „wird in AccountSphere verwaltet“;
  Sprache/Zeitzone bleiben änderbar.

### Kopplung von Bestandskonten
`php artisan users:link-codesphere {user : lokale ID oder E-Mail} {codesphere_id}`
- setzt `codesphere_id` am Altkonto; beim nächsten SSO-Login landet der Nutzer dort.
- Hat sich die Person bereits per SSO angemeldet, existiert ein Stub mit dieser
  `codesphere_id`: Er wird nur gelöscht, wenn er **keine** Daten besitzt, sonst Abbruch.

## Betrieb

1. In AccountSphere (Entwicklermodus) einen OAuth-Client anlegen, Redirect
   `https://tasks.code-sphere.de/auth/callback`.
2. `.env`: `CODESPHERE_CLIENT_ID`, `CODESPHERE_CLIENT_SECRET`, `CODESPHERE_REDIRECT_URI`,
   `CODESPHERE_APP_KEY=tasksphere` (so heißt der Key in AccountSphere `config/apps.php`).
3. `php artisan migrate`, danach je Bestandsnutzer `users:link-codesphere`.

## Bekannte Grenzen
- AccountSphere drosselt `/api/auth/login` auf 5 Versuche pro Minute **je IP**. Alle
  Proxy-Logins kommen von der IP des TasksSphere-Servers und teilen sich dieses Kontingent.
- Die Sprache ist lokal per API änderbar, wird aber beim nächsten Login wieder aus AccountSphere
  übernommen.
- Fehler im OAuth-Rückweg leitet das Paket auf `/login` um. Dessen eigene Route schickte sofort
  wieder zu AccountSphere, das einen angemeldeten Nutzer ohne Rückfrage zurückleitet – eine
  Endlosschleife, z. B. bei nicht erlaubtem Kontotyp. TasksSphere ersetzt die Route daher
  (`LoginController`, `register_login_route` in `config/codesphere.php` auf `false`): ohne Fehler
  geht es weiter direkt zu AccountSphere, mit Fehler wird die Meldung angezeigt, samt
  „Mit anderem Konto anmelden“ über `/oauth/logout`.
- Ein Konto, das noch nie angemeldet und nicht gekoppelt war, zeigt den letzten lokalen Namen.
- `/api/login`-Tokens aus AccountSphere sind Personal Access Tokens ohne Refresh; TasksSphere
  braucht sie nach dem Login nicht mehr.
