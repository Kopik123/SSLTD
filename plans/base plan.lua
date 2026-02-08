1) Założenia i logika całości

Jedna platforma (backend + baza + storage) → dwa fronty:

WWW (office-first): planowanie, kosztorysy, akceptacje, dokumenty, CRM, zamówienia, rozliczenia, administracja.

Android (site-first): szybkie raporty, zdjęcia, czas pracy, checklisty na budowie, problemy/awarie, odbiory, podpisy, offline.

To samo działa tu i tu, ale UI i skróty są inne:

Web ma „pełne formularze” i przeglądy tabelaryczne.

App ma „szybkie akcje” + offline cache + automatyczne uploady zdjęć.

Obszar działania: MCR + okolice do ~60 mil (w systemie jako service_area_radius_miles + walidacja adresu).

2) Role i uprawnienia (spójnie)

Role (systemowe):

Admin – pełny dostęp, konfiguracja, weryfikacje, przydziały, zamówienia, polityki, raporty.

Project Manager – prowadzi projekt, harmonogram, kontakt, akceptacje, raporty, zleca materiały.

Employee – zadania, raporty, timesheet, zdjęcia, checklisty.

Subcontractor (firma) – widzi przypisane projekty, dodaje własnych pracowników, raportuje, proponuje materiały/narzędzia.

Subcontractor Worker – jak employee, ale w ramach podwykonawcy.

Client – zapytanie, akceptacje, podgląd postępu, komunikacja, prośby zmian, zgłoszenia problemów.

Uprawnienia jako RBAC + per-projektowe ACL (np. podwykonawca widzi tylko przypisane projekty).

3) Struktura WWW: część publiczna + portal (office)
A) Public website (marketing + leady)

Menu:

Home

USP (jakość/lux standard), zakres regionu, CTA „Request a quote”

About us

standard, proces pracy, certyfikaty, ubezpieczenia, opinie

Services

lista usług + krótkie opisy + widełki „od” (opcjonalnie) + CTA

Gallery

realizacje, filtry (kuchnia/łazienka/extension…), przed/po

Contact

telefon/email/mapa, godziny, formularz prosty

Request a Quote

pełny formularz z uploadem zdjęć + adres + zakres prac + preferowane terminy

Login / Register

logowanie do portalu (client / staff / subcontractor)

B) Portal (po zalogowaniu) – główna nawigacja

Układ: lewy sidebar + topbar (search, notifications, quick add).

1. Dashboard

kafelki: projekty (w toku/oczekujące), nowe zapytania, alerty (brak akceptacji, kolizje zasobów, opóźnione dostawy), szybkie akcje.

2. Leads & Quotes (CRM)

Zapytania (Quote Requests): statusy, przypisanie managera, komunikacja, konwersja do projektu.

Szablony kosztorysów i checklist (wg usługi).

3. Projects

lista projektów + filtry statusów

widok projektu (zakładki):

Overview (status, daty, adres, budżet, osoby)

Scope/Checklist (pozycje usług, tryb wyceny: sqm/hours/fixed)

Schedule (kalendarz i zasoby)

Materials & Tools (plan/ zamówienia/ zużycie)

Files (zdjęcia, dokumenty, linki z Drive/Dropbox)

Messages (wątek projektu)

Reports (postęp, zdjęcia, problemy)

Billing (faktury, płatności) – jeśli wchodzisz w to teraz lub później

4. Schedule (kalendarz zasobów)

widok: dzień/tydzień/miesiąc

zasoby: ekipy, pracownicy, podwykonawcy, sprzęt

drag&drop, kolizje, statusy eventów

„propozycja terminu” do klienta + akceptacja

5. Inventory: Materials & Tools

katalog materiałów + dostawcy + cennik

narzędzia: własne / przypisane / serwis / lokalizacja

stany (opcjonalnie) + rezerwacje na projekt

6. People

Clients (CRM)

Employees (stawka, doświadczenie/kompetencje, certyfikaty)

Subcontractors (firmy) + ich pracownicy (wymagają zatwierdzenia admina)

dostęp i uprawnienia

7. Messages (globalne)

skrzynka/wątki, filtry: projekty, grupy, nieprzeczytane

wiadomości do: pojedynczych osób / roli / grupy (np. tylko pracownicy)

integracja z #tagami (#material #tool #project)

8. Admin / Settings

usługi i cenniki, szablony checklist

integracje (Google Drive, Dropbox, SMTP, powiadomienia)

logi, audyt, backup, polityki RODO, role/permissions

4) Struktura Android (teren)

Dolna nawigacja (5 zakładek):

Today

dzisiejsze zadania, dojazd, szybkie „Start/Stop time”, szybki upload zdjęć

Projects

lista przypisanych + szybkie: checklist, raport postępu, zgłoś problem

Camera / Upload

aparat → wybór projektu → opis → tagi → upload (kolejka offline)

Messages

czat/wątki projektowe + powiadomienia push

Profile

timesheets, szkolenia, certyfikaty, ustawienia offline

Kluczowe funkcje w app:

szybkie zdjęcia „przed/w trakcie/po” + automatyczne przypięcie do projektu

offline queue (zdjęcia/raporty) + synchronizacja

checklisty terenowe + podpis klienta przy odbiorze (opcjonalnie)

zgłoszenie problemu/awarii (z lokalizacją, zdjęciami)

5) Spójny flow: od zapytania do zamknięcia projektu

A. Lead / Quote

Client składa Request a Quote (opis, zakres, zdjęcia, adres, preferencje terminu)

Admin: kwalifikacja → przypisuje Project Managera

PM: kontakt + umawia spotkanie (kalendarz)

PM tworzy Checklist (draft) + wstępny kosztorys

PM wysyła do klienta → submitted

Client: akceptuje / odrzuca / prosi o zmiany

Po akceptacji → powstaje Project

B. Planning
8) Materials plan → zamówienia + delivery dates
9) Harmonogram plan → propozycja terminów → akceptacja klienta
10) Przypisanie podwykonawców/pracowników + zadań

C. Execution
11) Start prac (warunek: akceptacja terminu + kluczowe dostawy)
12) Raporty postępu (zdjęcia, notatki), timesheets, problemy/zmiany (change request)

D. Completion
13) Odbiór (checklist końcowa, ewentualne poprawki)
14) Status: completed → closed (z archiwizacją i podsumowaniem)

6) Wiadomości: jeden system, mocno zintegrowany

Model:

Thread (np. projekt / temat / zgłoszenie)

Message (nadawca, odbiorcy, treść, czas, status read/unread)

Attachments (pliki + linki z Drive/Dropbox)

Funkcje:

wiadomości do ról (np. „wszyscy pracownicy”), do projektu, do firmy podwykonawczej, do jednej osoby

tagowanie w treści: #material:tile_adhesive #tool:laser_level #project:PRJ-1024
→ automatyczne linki do rekordów

wątki automatyczne:

„Change Request #CR-xxx”

„Issue #ISS-xxx”

„Delivery #DEL-xxx”

7) Upload zdjęć i plików (PC i telefon)

Źródła:

WWW (PC): dysk lokalny + Google Drive + Dropbox

Android: aparat + galeria/plik + Drive/Dropbox

Zasady:

pliki trafiają do jednego storage (np. S3/Blob) + metadane w DB

każdy plik ma: projekt, typ (photo/doc), etap (before/during/after), autor, data, lokalizacja (opcjonalnie)

wersjonowanie + prawa dostępu (klient widzi tylko „client-visible”)

8) Baza danych: rdzeń (najważniejsze encje)

Minimalny, logiczny zestaw:

Users (role, status, dane, 2FA opcjonalnie)

Clients

Subcontractors + SubcontractorWorkers

Employees (stawka, kompetencje, certyfikaty)

QuoteRequests + attachments

Projects (status, adres, budżet, osoby)

Checklists + ChecklistItems (service_id, pricing_mode, qty/hours/fixed)

Services + PriceBook

Materials / Tools

ProjectMaterials / ProjectTools (required/ordered/used)

Deliveries

ScheduleEvents

Tasks

Timesheets

Issues (problem/awaria) + ChangeRequests

Threads/Messages/Attachments

AuditLog + Notifications

9) Co powinno być “must-have” w MVP vs później

MVP (żeby ruszyć biznesowo szybko):

Public site + Request a Quote z uploadem

Portal: Leads → Projects → Checklist → Schedule → Messages

App: zdjęcia + raport postępu + timesheet + messages

RBAC + audit + podstawowe integracje (Drive/Dropbox linki lub upload)

Phase 2:

magazyn/stany, pełne zamówienia, dostawy

faktury/płatności, podpisy, rozbudowane raporty

automaty (przypomnienia, SLA, eskalacje), rozbudowane szablony