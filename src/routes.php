<?php
declare(strict_types=1);

use App\Controllers\App\DashboardController;
use App\Controllers\App\Admin\AuditController as AdminAuditController;
use App\Controllers\App\Admin\UsersController as AdminUsersController;
use App\Controllers\App\ChecklistsController;
use App\Controllers\App\InventoryController;
use App\Controllers\App\ProjectChangeRequestsController;
use App\Controllers\App\ProjectChecklistController;
use App\Controllers\App\ProjectInventoryController;
use App\Controllers\App\ProjectIssuesController;
use App\Controllers\App\ProjectReportsController;
use App\Controllers\App\ScheduleController;
use App\Controllers\App\Client\ApprovalsController as ClientApprovalsController;
use App\Controllers\App\Client\LeadsController as ClientLeadsController;
use App\Controllers\App\Client\ProjectsController as ClientProjectsController;
use App\Controllers\App\People\SubcontractorsController as PeopleSubcontractorsController;
use App\Controllers\App\Dev\LogsController as DevLogsController;
use App\Controllers\App\Dev\ToolsController as DevToolsController;
use App\Controllers\App\LeadsController;
use App\Controllers\App\MessagesController;
use App\Controllers\App\ProjectsController;
use App\Controllers\App\TimesheetsController;
use App\Controllers\App\UploadsController;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\ChecklistsApiController;
use App\Controllers\Api\IssuesApiController;
use App\Controllers\Api\ProjectsApiController;
use App\Controllers\Api\QuoteRequestsApiController;
use App\Controllers\Api\ReportsApiController;
use App\Controllers\Api\ScheduleApiController;
use App\Controllers\Api\ThreadsApiController;
use App\Controllers\Api\TimesheetsApiController;
use App\Controllers\Api\UploadsApiController;
use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\PublicController;
use App\Http\Request;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RoleMiddleware;

/** @var \App\Http\Router $router */

// Public website
$router->get('/health', [HealthController::class, 'index']);
$router->get('/health/db', [HealthController::class, 'db']);

$router->get('/', [PublicController::class, 'home']);
$router->get('/about', [PublicController::class, 'about']);
$router->get('/services', [PublicController::class, 'services']);
$router->get('/gallery', [PublicController::class, 'gallery']);
$router->get('/contact', [PublicController::class, 'contact']);
$router->get('/privacy', [PublicController::class, 'privacy']);
$router->get('/terms', [PublicController::class, 'terms']);
$router->get('/quote-request', [PublicController::class, 'quoteRequestForm']);
$router->post(
  '/quote-request',
  [PublicController::class, 'quoteRequestSubmit'],
  [new CsrfMiddleware(), new RateLimitMiddleware('quote_request', 10, 60)]
);

// Auth (web)
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'loginSubmit'], [
  new CsrfMiddleware(),
  // Backoff on brute force: cap by IP and also by identity (email) per IP.
  new RateLimitMiddleware('login_ip', 30, 300),
  new RateLimitMiddleware('login_identity', 6, 300, static fn (Request $r): string => (string)$r->input('email', '')),
]);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'registerSubmit'], [new CsrfMiddleware(), new RateLimitMiddleware('register', 5, 600)]);
$router->post('/logout', [AuthController::class, 'logout'], [new CsrfMiddleware(), AuthMiddleware::class]);

$router->get('/reset-password', [AuthController::class, 'forgotPasswordForm']);
$router->post('/reset-password', [AuthController::class, 'forgotPasswordSubmit'], [new CsrfMiddleware(), new RateLimitMiddleware('forgot_password', 5, 600)]);
$router->get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm']);
$router->post('/reset-password/{token}', [AuthController::class, 'resetPasswordSubmit'], [new CsrfMiddleware(), new RateLimitMiddleware('reset_password', 10, 600)]);

// Portal (web)
$router->get('/app', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/app/leads', [LeadsController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/leads/{id}', [LeadsController::class, 'show'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/leads/{id}/checklist', [ChecklistsController::class, 'leadChecklist'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/leads/{id}/checklist/items', [ChecklistsController::class, 'addItem'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/checklist-items/{itemId}/update', [ChecklistsController::class, 'updateItem'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/checklist-items/{itemId}/delete', [ChecklistsController::class, 'deleteItem'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/leads/{id}/checklist/submit', [ChecklistsController::class, 'submitLeadChecklist'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/leads/{id}/status', [LeadsController::class, 'updateStatus'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/leads/{id}/assign', [LeadsController::class, 'assign'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin'])]);
$router->post('/app/leads/{id}/convert', [LeadsController::class, 'convertToProject'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/projects', [ProjectsController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/projects/{id}', [ProjectsController::class, 'show'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/projects/{id}/checklist', [ProjectChecklistController::class, 'show'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/checklist/items', [ProjectChecklistController::class, 'addItem'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/project-checklist-items/{itemId}/update', [ProjectChecklistController::class, 'updateItem'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/project-checklist-items/{itemId}/delete', [ProjectChecklistController::class, 'deleteItem'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/status', [ProjectsController::class, 'updateStatus'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/schedule/propose', [ScheduleController::class, 'propose'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/change-requests', [ProjectChangeRequestsController::class, 'create'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/change-requests/{id}/update', [ProjectChangeRequestsController::class, 'update'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/change-requests/{id}/delete', [ProjectChangeRequestsController::class, 'delete'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/change-requests/{id}/submit', [ProjectChangeRequestsController::class, 'submit'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/materials', [ProjectInventoryController::class, 'addMaterial'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/project-materials/{id}/update', [ProjectInventoryController::class, 'updateMaterial'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/project-materials/{id}/delete', [ProjectInventoryController::class, 'deleteMaterial'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/tools', [ProjectInventoryController::class, 'addTool'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/project-tools/{id}/update', [ProjectInventoryController::class, 'updateTool'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/project-tools/{id}/delete', [ProjectInventoryController::class, 'deleteTool'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/deliveries', [ProjectInventoryController::class, 'addDelivery'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/deliveries/{id}/update', [ProjectInventoryController::class, 'updateDelivery'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/deliveries/{id}/delete', [ProjectInventoryController::class, 'deleteDelivery'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/reports', [ProjectReportsController::class, 'add'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/issues', [ProjectIssuesController::class, 'add'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/issues/{id}/update', [ProjectIssuesController::class, 'update'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/timesheets', [TimesheetsController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/schedule', [ScheduleController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/schedule/events', [ScheduleController::class, 'createEvent'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/schedule/events/{id}/update', [ScheduleController::class, 'updateEvent'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/schedule/events/{id}/cancel', [ScheduleController::class, 'cancelEvent'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/inventory', [InventoryController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/inventory/materials', [InventoryController::class, 'materials'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/inventory/materials', [InventoryController::class, 'createMaterial'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/inventory/materials/{id}', [InventoryController::class, 'showMaterial'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/inventory/materials/{id}/update', [InventoryController::class, 'updateMaterial'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/inventory/tools', [InventoryController::class, 'tools'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/inventory/tools', [InventoryController::class, 'createTool'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/inventory/tools/{id}', [InventoryController::class, 'showTool'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/inventory/tools/{id}/update', [InventoryController::class, 'updateTool'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/people', [PeopleSubcontractorsController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/people/subcontractors', [PeopleSubcontractorsController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/app/people/subcontractors/{id}', [PeopleSubcontractorsController::class, 'show'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/people/subcontractor-workers/{id}/approve', [PeopleSubcontractorsController::class, 'approveWorker'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin'])]);
$router->post('/app/people/subcontractor-workers/{id}/reject', [PeopleSubcontractorsController::class, 'rejectWorker'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin'])]);
$router->get('/app/messages', [MessagesController::class, 'index'], [AuthMiddleware::class]);
$router->get('/app/messages/{id}', [MessagesController::class, 'show'], [AuthMiddleware::class]);
$router->post('/app/messages/{id}', [MessagesController::class, 'send'], [new CsrfMiddleware(), AuthMiddleware::class]);
$router->get('/app/uploads/{id}', [UploadsController::class, 'download'], [AuthMiddleware::class]);
$router->post('/app/uploads/{id}/visibility', [UploadsController::class, 'setVisibility'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/leads/{id}/uploads', [UploadsController::class, 'uploadToLead'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->post('/app/projects/{id}/uploads', [UploadsController::class, 'uploadToProject'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);

// Dev tooling (temporary). Never expose in production.
// In debug mode this is intentionally usable pre-login to help debug auth flows,
// but the controller hard-gates to private/LAN IPs only.
$router->get('/app/dev/logs', [DevLogsController::class, 'tail'], [new RateLimitMiddleware('dev_logs', 120, 60)]);
$router->get('/app/dev/tools/whoami', [DevToolsController::class, 'whoami'], [new RateLimitMiddleware('dev_tools_whoami', 120, 60)]);
$router->get('/app/dev/tools/users', [DevToolsController::class, 'users'], [new RateLimitMiddleware('dev_tools_users', 120, 60)]);
$router->post('/app/dev/tools/login-as', [DevToolsController::class, 'loginAs'], [new CsrfMiddleware(), new RateLimitMiddleware('dev_tools_login_as', 60, 60)]);
$router->post('/app/dev/tools/logout', [DevToolsController::class, 'logout'], [new CsrfMiddleware(), new RateLimitMiddleware('dev_tools_logout', 60, 60)]);
$router->post('/app/dev/tools/ratelimit/clear', [DevToolsController::class, 'clearRateLimits'], [new CsrfMiddleware(), new RateLimitMiddleware('dev_tools_rl_clear', 10, 60)]);

// Admin
$router->get('/app/admin/users', [AdminUsersController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['admin'])]);
$router->post('/app/admin/users', [AdminUsersController::class, 'create'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin'])]);
$router->post('/app/admin/users/{id}/update', [AdminUsersController::class, 'update'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin'])]);
$router->post('/app/admin/users/{id}/password', [AdminUsersController::class, 'setPassword'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['admin'])]);
$router->get('/app/admin/audit', [AdminAuditController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['admin'])]);

// Client portal
$router->get('/app/client/leads', [ClientLeadsController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->get('/app/client/leads/{id}', [ClientLeadsController::class, 'show'], [AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->get('/app/client/projects', [ClientProjectsController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->get('/app/client/projects/{id}', [ClientProjectsController::class, 'show'], [AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->get('/app/client/approvals', [ClientApprovalsController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->get('/app/client/approvals/{id}', [ClientApprovalsController::class, 'show'], [AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->post('/app/client/approvals/{id}/approve', [ClientApprovalsController::class, 'approve'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->post('/app/client/approvals/{id}/reject', [ClientApprovalsController::class, 'reject'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->get('/app/client/approvals/schedule/{id}', [ClientApprovalsController::class, 'showSchedule'], [AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->post('/app/client/approvals/schedule/{id}/approve', [ClientApprovalsController::class, 'approveSchedule'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->post('/app/client/approvals/schedule/{id}/reject', [ClientApprovalsController::class, 'rejectSchedule'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->get('/app/client/approvals/change/{id}', [ClientApprovalsController::class, 'showChange'], [AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->post('/app/client/approvals/change/{id}/approve', [ClientApprovalsController::class, 'approveChange'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['client'])]);
$router->post('/app/client/approvals/change/{id}/reject', [ClientApprovalsController::class, 'rejectChange'], [new CsrfMiddleware(), AuthMiddleware::class, new RoleMiddleware(['client'])]);

// API (Android + integrations)
$router->post('/api/auth/login', [AuthApiController::class, 'login'], [
  new RateLimitMiddleware('api_login_ip', 60, 300),
  new RateLimitMiddleware('api_login_identity', 10, 300, static function (Request $r): string {
    $body = $r->json();
    if (is_array($body) && isset($body['email']) && is_string($body['email'])) {
      return $body['email'];
    }
    return (string)$r->input('email', '');
  }),
]);
$router->post('/api/auth/register', [AuthApiController::class, 'register'], [new RateLimitMiddleware('api_register', 10, 600)]);
$router->get('/api/auth/me', [AuthApiController::class, 'me'], [AuthMiddleware::class]);
$router->post('/api/auth/refresh', [AuthApiController::class, 'refresh'], [AuthMiddleware::class, new RateLimitMiddleware('api_refresh', 30, 300)]);

$router->post('/api/quote-requests', [QuoteRequestsApiController::class, 'create'], [new RateLimitMiddleware('api_quote_request', 20, 60)]);
$router->get('/api/quote-requests', [QuoteRequestsApiController::class, 'list'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);
$router->get('/api/quote-requests/{id}', [QuoteRequestsApiController::class, 'get'], [AuthMiddleware::class, new RoleMiddleware(['admin', 'pm'])]);

$router->get('/api/projects', [ProjectsApiController::class, 'list'], [AuthMiddleware::class, new RateLimitMiddleware('api_projects_list', 120, 60)]);
$router->get('/api/projects/{id}', [ProjectsApiController::class, 'get'], [AuthMiddleware::class, new RateLimitMiddleware('api_projects_get', 120, 60)]);

$router->get('/api/threads', [ThreadsApiController::class, 'list'], [AuthMiddleware::class, new RateLimitMiddleware('api_threads_list', 60, 60)]);
$router->get('/api/threads/{id}/messages', [ThreadsApiController::class, 'messages'], [AuthMiddleware::class, new RateLimitMiddleware('api_threads_messages', 120, 60)]);
$router->post('/api/threads/{id}/messages', [ThreadsApiController::class, 'send'], [AuthMiddleware::class, new RateLimitMiddleware('api_threads_send', 60, 60)]);

$router->post('/api/timesheets/start', [TimesheetsApiController::class, 'start'], [AuthMiddleware::class, new RateLimitMiddleware('api_timesheets_start', 30, 60)]);
$router->post('/api/timesheets/stop', [TimesheetsApiController::class, 'stop'], [AuthMiddleware::class, new RateLimitMiddleware('api_timesheets_stop', 30, 60)]);
$router->get('/api/timesheets', [TimesheetsApiController::class, 'list'], [AuthMiddleware::class, new RateLimitMiddleware('api_timesheets_list', 120, 60)]);

$router->get('/api/schedule', [ScheduleApiController::class, 'list'], [AuthMiddleware::class, new RateLimitMiddleware('api_schedule_list', 120, 60)]);

$router->post('/api/uploads', [UploadsApiController::class, 'create'], [AuthMiddleware::class, new RateLimitMiddleware('api_uploads', 30, 60)]);

// Checklist (project scope) for Android
$router->get('/api/projects/{id}/checklist/current', [ChecklistsApiController::class, 'currentForProject'], [AuthMiddleware::class, new RateLimitMiddleware('api_project_checklist', 120, 60)]);
$router->post('/api/checklist-items/{id}', [ChecklistsApiController::class, 'updateItemStatus'], [AuthMiddleware::class, new RateLimitMiddleware('api_checklist_item_update', 120, 60)]);

// Reports + Issues (Android + portal integrations)
$router->get('/api/projects/{id}/reports', [ReportsApiController::class, 'listForProject'], [AuthMiddleware::class, new RateLimitMiddleware('api_reports_list', 120, 60)]);
$router->post('/api/projects/{id}/reports', [ReportsApiController::class, 'createForProject'], [AuthMiddleware::class, new RateLimitMiddleware('api_reports_create', 60, 60)]);
$router->get('/api/projects/{id}/issues', [IssuesApiController::class, 'listForProject'], [AuthMiddleware::class, new RateLimitMiddleware('api_issues_list', 120, 60)]);
$router->post('/api/projects/{id}/issues', [IssuesApiController::class, 'createForProject'], [AuthMiddleware::class, new RateLimitMiddleware('api_issues_create', 60, 60)]);
$router->post('/api/issues/{id}', [IssuesApiController::class, 'update'], [AuthMiddleware::class, new RateLimitMiddleware('api_issues_update', 120, 60)]);
