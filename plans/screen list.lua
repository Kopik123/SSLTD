1) WEB — mapa nawigacji i ekrany
A) Public (bez logowania)

Home (/)

About (/about)

Services (/services)

szczegół usługi (/services/:slug)

Gallery (/gallery)

detal realizacji (/gallery/:id)

Contact (/contact)

Request a Quote (/quote-request)

Login (/login)

Register (/register)

Reset password (/reset-password)

Request a Quote – pola (MVP):

dane klienta (imię, tel, email)

adres (autocomplete)

zakres prac (multi-select + opis)

preferowane terminy (2–3 propozycje)

zdjęcia/pliki: Local + Drive + Dropbox

checkboxy RODO/zgody

B) Portal (po zalogowaniu) – wspólny layout

Topbar: global search, notifications, quick-add
Sidebar: Dashboard, Leads, Projects, Schedule, Inventory, People, Messages, Reports, Admin

1) Dashboard (/app)

widgety: New leads, Projects in progress, Pending approvals, Delivery alerts, Unread messages

quick actions: “Create project”, “Propose schedule”, “New message”, “Upload files”

2) Leads & Quotes (/app/leads)

Leads list (/app/leads)

Lead details (/app/leads/:id)

tabs:

Overview

Attachments

Messages (thread lead)

Actions: assign PM / convert to project / reject

Quote builder (/app/leads/:id/quote)

checklist draft + pricing

Quote preview & send (/app/leads/:id/quote/send)

3) Projects (/app/projects)

Projects list (/app/projects)

Project details (/app/projects/:id)

tabs:

Overview

Scope / Checklist

Schedule

Tasks

Materials & Tools

Files

Messages

Reports

Change Requests

Issues

Billing (phase 2 jeśli chcesz)

Create project (/app/projects/new) (z leadu lub ręcznie)

4) Schedule (/app/schedule)

Calendar view (day/week/month)

Resource lanes: employee/subcontractor/tool

Event details (/app/schedule/:eventId)

Propose schedule to client (/app/projects/:id/schedule/propose)

Client approval panel (dla klienta) (/app/client/approvals)

5) Inventory (/app/inventory)

Materials catalog (/app/inventory/materials)

Material detail (/app/inventory/materials/:id)

Tools catalog (/app/inventory/tools)

Tool detail (/app/inventory/tools/:id)

Project materials/tools (w projekcie, ale też widok zbiorczy) (/app/inventory/project-allocations)

6) People (/app/people)

Clients (/app/people/clients)

Employees (/app/people/employees)

Subcontractors (/app/people/subcontractors)

Subcontractor detail (/app/people/subcontractors/:id)

workers + pending approvals

7) Messages (/app/messages)

Inbox / Threads (/app/messages)

Thread view (/app/messages/:threadId)

Compose (/app/messages/new)

Attachments picker (local + Drive/Dropbox)

Tag resolver (#material #tool #project)

8) Reports (/app/reports)

Progress by project

Timesheets summary

Delivery delays

KPI (phase 2)

9) Admin (/app/admin) — tylko admin

Services & PriceBook (/app/admin/services, /app/admin/pricing)

Roles & Permissions (/app/admin/roles)

Integrations (/app/admin/integrations)

Audit logs (/app/admin/audit)

Storage/Retention settings (/app/admin/storage)

Templates (checklists/messages) (/app/admin/templates)

2) ANDROID — screen list + nawigacja

Bottom nav (5):

Today

Projects

Capture

Messages

Profile

1) Today

Today overview

dzisiejsze eventy z kalendarza

“Start shift / Stop” (timesheet)

szybkie akcje: photo, issue, progress note

Task detail (quick) (z dzisiejszego planu)

2) Projects

Projects list (tylko przypisane)

Project detail (mobile) — zakładki:

Overview

Checklist (read + update status pozycji)

Tasks (mark done)

Files (gallery)

Reports (add progress)

Issues (create / update)

Change requests (create)

Materials/tools (read + request)

3) Capture

Camera screen

Attachment form

select project

stage: before/during/after

opis + tagi

offline queue toggle

Upload queue

retry / pause / delete

4) Messages

Threads list

Thread view

Compose

do osoby / roli / projektu

attach photo/file

5) Profile

My timesheets

Training materials

My certificates

Settings

offline cache

notifications

sign out

Offline (MVP):

kolejka uploadów (zdjęcia/raporty)

cache: assigned projects + today schedule + last 50 messages/thread

3) Flow statusów — gdzie i kto klika

Lead/Quote:

quote_requested (client)

quote_review (admin/PM)

meeting_scheduled (PM)

checklist_draft (PM)

checklist_submitted (PM → client)

checklist_approved / rejected (client)

project_created (system)

Project execution:

materials_planning → materials_ordered (PM/admin)

schedule_proposed → client_approved (PM → client)

ready_for_execution (system: warunki spełnione)

in_progress (PM/employee starts)

completed (PM)

closed (admin)

4) API — endpointy (REST, MVP + łatwe do rozbudowy)
Auth

POST /auth/login

POST /auth/register

POST /auth/refresh

POST /auth/forgot-password

POST /auth/reset-password

GET /auth/me

Users / People

GET /users?role=...

GET /users/:id

PATCH /users/:id

POST /subcontractors

GET /subcontractors

POST /subcontractors/:id/workers

POST /subcontractor-workers/:id/approve (admin)

Leads / Quote Requests

POST /quote-requests (public)

GET /quote-requests (staff)

GET /quote-requests/:id

PATCH /quote-requests/:id (assign PM, update status)

POST /quote-requests/:id/convert-to-project

Projects

POST /projects

GET /projects?status=...&assignedTo=me

GET /projects/:id

PATCH /projects/:id (status, people)

GET /projects/:id/timeline (audit + status log)

Checklist / Scope

POST /projects/:id/checklists

GET /projects/:id/checklists/current

PATCH /checklists/:id

POST /checklists/:id/submit

POST /checklists/:id/approve (client)

POST /checklists/:id/reject (client)

POST /checklists/:id/items

PATCH /checklist-items/:itemId

DELETE /checklist-items/:itemId

Schedule

GET /schedule?from=...&to=...&resource=...

POST /schedule/events

PATCH /schedule/events/:id

DELETE /schedule/events/:id

POST /projects/:id/schedule/propose (PM → client)

POST /projects/:id/schedule/approve (client)

Materials / Tools / Deliveries

GET /materials

POST /materials

GET /tools

POST /tools

POST /projects/:id/material-requests

POST /projects/:id/tool-requests

POST /deliveries

PATCH /deliveries/:id (status/date)

Files / Uploads (local + external links)

POST /files/presign (S3/Blob presigned url)

POST /files (metadata create: projectId, type, stage, url)

GET /projects/:id/files

DELETE /files/:id

POST /files/external-link (Drive/Dropbox URL + meta)

Messages

GET /threads?projectId=...&unread=true

POST /threads (participants / role-based)

GET /threads/:id/messages

POST /threads/:id/messages

POST /messages/:id/read

POST /messages/:id/attachments

Timesheets (teren)

POST /timesheets/start

POST /timesheets/stop

GET /timesheets?userId=me&from=...&to=...

PATCH /timesheets/:id

Reports / Issues / Change Requests

POST /projects/:id/reports (progress note + photos)

GET /projects/:id/reports

POST /projects/:id/issues

PATCH /issues/:id

POST /projects/:id/change-requests

PATCH /change-requests/:id

Notifications

POST /devices (FCM token)

GET /notifications

POST /notifications/:id/read

5) Najważniejsze „spójności”, które to naprawiają

Wiadomości, pliki, raporty, issue i change request zawsze są przypięte do projektu (lub leadu) → nic nie „wisi w próżni”.

Klient ma osobny panel akceptacji (quote/schedule/change), zamiast błądzić po technicznych zakładkach.

Podwykonawca dodaje pracownika → admin zatwierdza → dopiero wtedy przypisania do projektów.

App robi szybkie rzeczy, web robi ciężkie rzeczy — ale oba widzą te same rekordy.