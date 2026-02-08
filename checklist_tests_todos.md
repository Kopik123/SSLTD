# Test Tools Checklist (Dev Only)

Wszystkie funkcje z tej listy musza byc:
- aktywne tylko gdy `APP_DEBUG=1`
- zabezpieczone przed przypadkowym wystawieniem na produkcje (hard gate + IP gate)
- bez inline JS i bez inline CSS (CSP)
- logowane w `changelogs.lua`

## Backend (Dev Endpoints)

- [x] Dodac `GET /app/dev/tools/whoami` (JSON: current user lub null).
- [x] Dodac `GET /app/dev/tools/users` (JSON: lista userow do testow).
- [x] Dodac `POST /app/dev/tools/login-as` (CSRF) do szybkiego przelaczania roli przez zalogowanie jako inny user.
- [x] Dodac `POST /app/dev/tools/logout` (CSRF) do szybkiego logout.
- [x] Dodac `POST /app/dev/tools/ratelimit/clear` (CSRF) do czyszczenia limitow logowania podczas testow.
Gating:
- [x] Hard gate: tylko `APP_DEBUG=1` (w przeciwnym razie 404).
- [x] IP gate: minimum private IP; dla niebezpiecznych akcji preferuj loopback (127.0.0.1/::1) albo klucz dev (opcjonalnie).
- [x] Rate limit na endpointach dev tools.

## Frontend (Floating Overlay)

- [x] Dodac zakladki w oknie: `Logs` oraz `Tools`.
- [x] Tools: pokazywac aktualny stan sesji (whoami) + przycisk odswiez.
- [x] Tools: lista userow (z `GET /app/dev/tools/users`) i przyciski `Login as`.
- [x] Tools: `Logout` przycisk.
- [x] Tools: `Clear rate limits` przycisk.
- [x] Tools: szybkie linki do najwazniejszych ekranow (public/app/admin/client/health).
- [x] Tools: test autofill:
- [x] `/login?autofill=admin|pm|client|employee|sub|subworker` wypelnia email/haslo (tylko dev).
- [x] `/quote-request?mode=simple&autofill=1` wypelnia pola testowymi danymi.
- [x] `/quote-request?mode=advanced&autofill=1` wypelnia pola testowymi danymi.

## Docs / Safety

- [x] Opisac uzycie (kiedy widoczne, jak wylaczyc) w `docs/setup.md` albo osobnym docu.
- [x] Upewnic sie, ze po ustawieniu `APP_DEBUG=0` overlay + dev endpoints sa niedostepne.
