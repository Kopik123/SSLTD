<?php
declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Database\Db;
use App\Support\Config;
use App\Support\Env;

// Optional: select env file (e.g. `.env.staging`) via `--env .env.staging` or `SS_ENV_FILE`.
$envFile = getenv('SS_ENV_FILE');
if (!is_string($envFile) || trim($envFile) === '') {
  $envFile = '.env';
}
for ($i = 1; $i < count($argv); $i++) {
  $a = (string)$argv[$i];
  if ($a === '--env' && isset($argv[$i + 1])) {
    $envFile = (string)$argv[$i + 1];
    break;
  }
  if (str_starts_with($a, '--env=')) {
    $envFile = substr($a, 6);
    break;
  }
}
$envFile = trim($envFile);
$envFile = str_replace(['\\', '/'], '', $envFile);
if (!preg_match('/^\\.env(\\.[A-Za-z0-9._-]+)?$/', $envFile)) {
  $envFile = '.env';
}
Env::load(__DIR__ . '/../' . $envFile);
$config = Config::fromEnv([
  'APP_ENV' => 'dev',
  'APP_DEBUG' => '1',
  'APP_URL' => 'http://127.0.0.1:8000',
  'APP_KEY' => 'change-me',
  'DB_CONNECTION' => 'mysql',
  'DB_DATABASE' => __DIR__ . '/../storage/app.db',
  'DB_HOST' => '127.0.0.1',
  'DB_PORT' => '3306',
  'DB_NAME' => 'ss_ltd',
  'DB_USER' => 'root',
  'DB_PASS' => '',
  'SERVICE_AREA_RADIUS_MILES' => '60',
]);

$db = Db::connect($config);

/**
 * @return int user id
 */
function ensure_user(Db $db, string $role, string $name, string $email, string $password, ?string $phone = null): int
{
  $existing = $db->fetchOne('SELECT id FROM users WHERE email = :e LIMIT 1', ['e' => $email]);
  if ($existing !== null) {
    return (int)$existing['id'];
  }

  $now = gmdate('c');
  $id = $db->insert(
    'INSERT INTO users (role, name, email, phone, password_hash, status, created_at, updated_at) VALUES (:r, :n, :e, :p, :h, :s, :c, :u)',
    [
      'r' => $role,
      'n' => $name,
      'e' => $email,
      'p' => $phone,
      'h' => password_hash($password, PASSWORD_DEFAULT),
      's' => 'active',
      'c' => $now,
      'u' => $now,
    ]
  );
  return (int)$id;
}

$adminId = ensure_user($db, 'admin', 'Admin', 'admin@ss.local', 'Admin123!');
$pmId = ensure_user($db, 'pm', 'Project Manager', 'pm@ss.local', 'Pm123456!');
$clientId = ensure_user($db, 'client', 'Client Demo', 'client@ss.local', 'Client123!');
$employeeId = ensure_user($db, 'employee', 'Employee Demo', 'employee@ss.local', 'Employee123!');
$subId = ensure_user($db, 'subcontractor', 'Subcontractor Demo', 'sub@ss.local', 'Sub123456!');
$subWorkerId = ensure_user($db, 'subcontractor_worker', 'Sub Worker Demo', 'subworker@ss.local', 'Worker123!');

$lead = $db->fetchOne('SELECT id FROM quote_requests LIMIT 1');
if ($lead === null) {
  $now = gmdate('c');
  $db->insert(
    'INSERT INTO quote_requests (status, client_user_id, name, email, phone, address, scope_json, description, preferred_dates_json, assigned_pm_user_id, service_area_ok, created_at, updated_at)
     VALUES (:st, :cuid, :n, :e, :p, :a, :scope, :d, :pd, :pm, :ok, :c, :u)',
    [
      'st' => 'quote_requested',
      'cuid' => $clientId,
      'n' => 'Client Demo',
      'e' => 'client@ss.local',
      'p' => '(555) 010-020',
      'a' => 'Boston, MA',
      'scope' => json_encode(['kitchen_remodel', 'bathroom_remodel'], JSON_UNESCAPED_SLASHES),
      'd' => 'Sample lead created by seed.',
      'pd' => json_encode(['2026-02-15', '2026-02-20'], JSON_UNESCAPED_SLASHES),
      'pm' => $pmId,
      'ok' => 1,
      'c' => $now,
      'u' => $now,
    ]
  );
}

// Seed a submitted checklist for the demo lead so the client Approvals page has content.
try {
  $leadRow = $db->fetchOne('SELECT id, client_user_id FROM quote_requests ORDER BY id ASC LIMIT 1');
  if ($leadRow !== null) {
    $leadId = (int)$leadRow['id'];
    $existingChecklist = $db->fetchOne('SELECT id FROM checklists WHERE quote_request_id = :id LIMIT 1', ['id' => $leadId]);
    if ($existingChecklist === null) {
      $now = gmdate('c');
      $checklistId = (int)$db->insert(
        'INSERT INTO checklists (quote_request_id, project_id, status, title, created_by_user_id, submitted_at, decided_at, decided_by_user_id, decision_note, created_at, updated_at)
         VALUES (:qid, NULL, :st, :t, :cb, :sub, NULL, NULL, NULL, :c, :u)',
        [
          'qid' => $leadId,
          'st' => 'submitted',
          't' => 'Estimate / Checklist',
          'cb' => $pmId,
          'sub' => $now,
          'c' => $now,
          'u' => $now,
        ]
      );

      $db->insert(
        'INSERT INTO checklist_items (checklist_id, position, title, pricing_mode, qty, unit_cost_cents, fixed_cost_cents, status, created_at, updated_at)
         VALUES (:cid, :pos, :t, :pm, :q, :uc, :fc, :st, :c, :u)',
        [
          'cid' => $checklistId,
          'pos' => 10,
          't' => 'Demolition + protection',
          'pm' => 'fixed',
          'q' => 0,
          'uc' => 0,
          'fc' => 125000,
          'st' => 'todo',
          'c' => $now,
          'u' => $now,
        ]
      );
      $db->insert(
        'INSERT INTO checklist_items (checklist_id, position, title, pricing_mode, qty, unit_cost_cents, fixed_cost_cents, status, created_at, updated_at)
         VALUES (:cid, :pos, :t, :pm, :q, :uc, :fc, :st, :c, :u)',
        [
          'cid' => $checklistId,
          'pos' => 20,
          't' => 'Tile install',
          'pm' => 'sqm',
          'q' => 18.5,
          'uc' => 2200,
          'fc' => 0,
          'st' => 'todo',
          'c' => $now,
          'u' => $now,
        ]
      );

      $db->execute('UPDATE quote_requests SET status = :st, updated_at = :u WHERE id = :id', [
        'st' => 'checklist_submitted',
        'u' => $now,
        'id' => $leadId,
      ]);
    }
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

$project = $db->fetchOne('SELECT id FROM projects LIMIT 1');
if ($project === null) {
  $leadRow = $db->fetchOne('SELECT * FROM quote_requests LIMIT 1');
  $now = gmdate('c');
  if ($leadRow !== null) {
    $projectId = (int)$db->insert(
      'INSERT INTO projects (status, quote_request_id, client_user_id, name, address, budget_cents, assigned_pm_user_id, created_at, updated_at)
       VALUES (:st, :qid, :cuid, :n, :a, :b, :pm, :c, :u)',
      [
        'st' => 'project_created',
        'qid' => (int)$leadRow['id'],
        'cuid' => $leadRow['client_user_id'] ?? null,
        'n' => 'Project from Lead #' . (string)$leadRow['id'],
        'a' => (string)$leadRow['address'],
        'b' => 0,
        'pm' => $leadRow['assigned_pm_user_id'] ?? null,
        'c' => $now,
        'u' => $now,
      ]
    );
    $db->execute('UPDATE quote_requests SET status = :st, updated_at = :u WHERE id = :id', [
      'st' => 'project_created',
      'u' => $now,
      'id' => (int)$leadRow['id'],
    ]);
  } else {
    $projectId = (int)$db->insert(
      'INSERT INTO projects (status, quote_request_id, client_user_id, name, address, budget_cents, assigned_pm_user_id, created_at, updated_at)
       VALUES (:st, NULL, NULL, :n, :a, :b, :pm, :c, :u)',
      [
        'st' => 'project_created',
        'n' => 'Sample Project',
        'a' => 'Boston, MA',
        'b' => 0,
        'pm' => $pmId,
        'c' => $now,
        'u' => $now,
      ]
    );
  }
} else {
  $projectId = (int)$project['id'];
}

// Seed a project checklist/scope (best-effort) so the web checklist page has content.
try {
  $existingProjectChecklist = $db->fetchOne('SELECT id FROM checklists WHERE project_id = :pid LIMIT 1', ['pid' => $projectId]);
  if ($existingProjectChecklist === null) {
    $now = gmdate('c');
    $projectChecklistId = (int)$db->insert(
      'INSERT INTO checklists (quote_request_id, project_id, status, title, created_by_user_id, submitted_at, decided_at, decided_by_user_id, decision_note, created_at, updated_at)
       VALUES (NULL, :pid, :st, :t, :cb, NULL, NULL, NULL, NULL, :c, :u)',
      [
        'pid' => $projectId,
        'st' => 'draft',
        't' => 'Project Checklist',
        'cb' => $pmId,
        'c' => $now,
        'u' => $now,
      ]
    );

    $db->insert(
      'INSERT INTO checklist_items (checklist_id, position, title, pricing_mode, qty, unit_cost_cents, fixed_cost_cents, status, created_at, updated_at)
       VALUES (:cid, :pos, :t, :pm, :q, :uc, :fc, :st, :c, :u)',
      [
        'cid' => $projectChecklistId,
        'pos' => 10,
        't' => 'Site protection + prep',
        'pm' => 'fixed',
        'q' => 0,
        'uc' => 0,
        'fc' => 75000,
        'st' => 'todo',
        'c' => $now,
        'u' => $now,
      ]
    );
    $db->insert(
      'INSERT INTO checklist_items (checklist_id, position, title, pricing_mode, qty, unit_cost_cents, fixed_cost_cents, status, created_at, updated_at)
       VALUES (:cid, :pos, :t, :pm, :q, :uc, :fc, :st, :c, :u)',
      [
        'cid' => $projectChecklistId,
        'pos' => 20,
        't' => 'Rough plumbing',
        'pm' => 'hours',
        'q' => 8,
        'uc' => 8500,
        'fc' => 0,
        'st' => 'todo',
        'c' => $now,
        'u' => $now,
      ]
    );
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

// Seed a pending schedule proposal so the client Approvals page shows schedule content.
try {
  $existingProposal = $db->fetchOne(
    'SELECT id FROM schedule_proposals WHERE project_id = :pid AND status = :st ORDER BY id DESC LIMIT 1',
    ['pid' => $projectId, 'st' => 'submitted']
  );
  if ($existingProposal === null) {
    $now = gmdate('c');
    $db->insert(
      'INSERT INTO schedule_proposals (project_id, status, starts_at, ends_at, note, decision_note, created_by_user_id, decided_by_user_id, created_at, decided_at, updated_at)
       VALUES (:pid, :st, :sa, :ea, :n, NULL, :cb, NULL, :c, NULL, :u)',
      [
        'pid' => $projectId,
        'st' => 'submitted',
        'sa' => '2026-02-20T09:00',
        'ea' => '2026-02-20T15:00',
        'n' => 'Seeded schedule proposal (adjust in Project detail → Schedule).',
        'cb' => $pmId,
        'c' => $now,
        'u' => $now,
      ]
    );
    $db->execute('UPDATE projects SET status = :st, updated_at = :u WHERE id = :id', [
      'st' => 'schedule_proposed',
      'u' => $now,
      'id' => $projectId,
    ]);
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

// Seed a small inventory catalog (best-effort).
try {
  $mc = $db->fetchOne('SELECT COUNT(*) AS c FROM materials');
  if ((int)($mc['c'] ?? 0) === 0) {
    $now = gmdate('c');
    $db->insert(
      'INSERT INTO materials (status, name, unit, unit_cost_cents, vendor, sku, notes, created_at, updated_at)
       VALUES (:st, :n, :u, :uc, :v, :sku, :notes, :c, :up)',
      [
        'st' => 'active',
        'n' => 'Tile adhesive (bag)',
        'u' => 'unit',
        'uc' => 2899,
        'v' => 'Local supplier',
        'sku' => 'ADH-001',
        'notes' => 'Seed material.',
        'c' => $now,
        'up' => $now,
      ]
    );
    $db->insert(
      'INSERT INTO materials (status, name, unit, unit_cost_cents, vendor, sku, notes, created_at, updated_at)
       VALUES (:st, :n, :u, :uc, :v, :sku, :notes, :c, :up)',
      [
        'st' => 'active',
        'n' => 'Cement board',
        'u' => 'unit',
        'uc' => 1549,
        'v' => 'Local supplier',
        'sku' => 'CB-010',
        'notes' => 'Seed material.',
        'c' => $now,
        'up' => $now,
      ]
    );
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

try {
  $tc = $db->fetchOne('SELECT COUNT(*) AS c FROM tools');
  if ((int)($tc['c'] ?? 0) === 0) {
    $now = gmdate('c');
    $db->insert(
      'INSERT INTO tools (status, name, serial, location, notes, created_at, updated_at)
       VALUES (:st, :n, :s, :l, :notes, :c, :up)',
      [
        'st' => 'active',
        'n' => 'Laser level',
        's' => 'LL-0001',
        'l' => 'Storage',
        'notes' => 'Seed tool.',
        'c' => $now,
        'up' => $now,
      ]
    );
    $db->insert(
      'INSERT INTO tools (status, name, serial, location, notes, created_at, updated_at)
       VALUES (:st, :n, :s, :l, :notes, :c, :up)',
      [
        'st' => 'active',
        'n' => 'Tile saw',
        's' => 'TS-0007',
        'l' => 'Truck',
        'notes' => 'Seed tool.',
        'c' => $now,
        'up' => $now,
      ]
    );
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

// Seed an example allocation + delivery for the demo project (best-effort).
try {
  $pmc = $db->fetchOne('SELECT COUNT(*) AS c FROM project_materials WHERE project_id = :pid', ['pid' => $projectId]);
  if ((int)($pmc['c'] ?? 0) === 0) {
    $m = $db->fetchOne('SELECT id FROM materials ORDER BY id ASC LIMIT 1');
    if ($m !== null) {
      $now = gmdate('c');
      $db->insert(
        'INSERT INTO project_materials (project_id, material_id, status, qty, needed_by, notes, created_by_user_id, created_at, updated_at)
         VALUES (:pid, :mid, :st, :q, :nb, :n, :cb, :c, :u)',
        [
          'pid' => $projectId,
          'mid' => (int)$m['id'],
          'st' => 'required',
          'q' => 10,
          'nb' => '2026-02-18',
          'n' => 'Seed allocation.',
          'cb' => $pmId,
          'c' => $now,
          'u' => $now,
        ]
      );
    }
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

try {
  $ptc = $db->fetchOne('SELECT COUNT(*) AS c FROM project_tools WHERE project_id = :pid', ['pid' => $projectId]);
  if ((int)($ptc['c'] ?? 0) === 0) {
    $t = $db->fetchOne('SELECT id FROM tools ORDER BY id ASC LIMIT 1');
    if ($t !== null) {
      $now = gmdate('c');
      $db->insert(
        'INSERT INTO project_tools (project_id, tool_id, assigned_to_user_id, status, notes, created_by_user_id, created_at, updated_at)
         VALUES (:pid, :tid, NULL, :st, :n, :cb, :c, :u)',
        [
          'pid' => $projectId,
          'tid' => (int)$t['id'],
          'st' => 'required',
          'n' => 'Seed tool allocation.',
          'cb' => $pmId,
          'c' => $now,
          'u' => $now,
        ]
      );
    }
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

try {
  $dc = $db->fetchOne('SELECT COUNT(*) AS c FROM deliveries WHERE project_id = :pid', ['pid' => $projectId]);
  if ((int)($dc['c'] ?? 0) === 0) {
    $m = $db->fetchOne('SELECT id FROM materials ORDER BY id ASC LIMIT 1');
    if ($m !== null) {
      $now = gmdate('c');
      $db->insert(
        'INSERT INTO deliveries (project_id, material_id, qty, status, expected_at, delivered_at, notes, created_by_user_id, created_at, updated_at)
         VALUES (:pid, :mid, :q, :st, :ea, NULL, :n, :cb, :c, :u)',
        [
          'pid' => $projectId,
          'mid' => (int)$m['id'],
          'q' => 10,
          'st' => 'pending',
          'ea' => '2026-02-19T09:00',
          'n' => 'Seed delivery.',
          'cb' => $pmId,
          'c' => $now,
          'u' => $now,
        ]
      );
    }
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

// Seed a progress report + issue for the demo project (best-effort).
try {
  $rc = $db->fetchOne('SELECT COUNT(*) AS c FROM project_reports WHERE project_id = :pid', ['pid' => $projectId]);
  if ((int)($rc['c'] ?? 0) === 0) {
    $now = gmdate('c');
    $db->insert(
      'INSERT INTO project_reports (project_id, body, created_by_user_id, created_at)
       VALUES (:pid, :b, :cb, :c)',
      [
        'pid' => $projectId,
        'b' => "Seed report: site protected, demolition started. Next: rough plumbing.",
        'cb' => $pmId,
        'c' => $now,
      ]
    );
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

try {
  $ic = $db->fetchOne('SELECT COUNT(*) AS c FROM issues WHERE project_id = :pid', ['pid' => $projectId]);
  if ((int)($ic['c'] ?? 0) === 0) {
    $now = gmdate('c');
    $db->insert(
      'INSERT INTO issues (project_id, status, severity, title, body, created_by_user_id, assigned_to_user_id, resolved_at, created_at, updated_at)
       VALUES (:pid, :st, :sev, :t, :b, :cb, NULL, NULL, :c, :u)',
      [
        'pid' => $projectId,
        'st' => 'open',
        'sev' => 'medium',
        't' => 'Access issue: water shutoff',
        'b' => 'Client needs to provide access to water shutoff location.',
        'cb' => $pmId,
        'c' => $now,
        'u' => $now,
      ]
    );
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

// Seed a pending change request for the demo project (best-effort).
try {
  $cc = $db->fetchOne('SELECT COUNT(*) AS c FROM change_requests WHERE project_id = :pid', ['pid' => $projectId]);
  if ((int)($cc['c'] ?? 0) === 0) {
    $now = gmdate('c');
    $db->insert(
      'INSERT INTO change_requests (project_id, status, title, body, cost_delta_cents, schedule_delta_days, created_by_user_id, submitted_at, decided_by_user_id, decided_at, decision_note, created_at, updated_at)
       VALUES (:pid, :st, :t, :b, :cdc, :sdd, :cb, :sub, NULL, NULL, NULL, :c, :u)',
      [
        'pid' => $projectId,
        'st' => 'submitted',
        't' => 'Add recessed lighting',
        'b' => 'Client requested additional recessed lights in kitchen.',
        'cdc' => 65000,
        'sdd' => 2,
        'cb' => $pmId,
        'sub' => $now,
        'c' => $now,
        'u' => $now,
      ]
    );
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

// Seed an approved schedule event for the demo project (best-effort) so the calendar has content.
try {
  $ec = $db->fetchOne('SELECT COUNT(*) AS c FROM schedule_events WHERE project_id = :pid', ['pid' => $projectId]);
  if ((int)($ec['c'] ?? 0) === 0) {
    $now = gmdate('c');
    $db->insert(
      'INSERT INTO schedule_events (project_id, title, starts_at, ends_at, status, created_by_user_id, created_at, updated_at)
       VALUES (:pid, :t, :sa, :ea, :st, :cb, :c, :u)',
      [
        'pid' => $projectId,
        't' => 'Kickoff',
        'sa' => '2026-02-18T09:00',
        'ea' => '2026-02-18T11:00',
        'st' => 'approved',
        'cb' => $pmId,
        'c' => $now,
        'u' => $now,
      ]
    );
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

$member = $db->fetchOne('SELECT 1 AS ok FROM project_members WHERE project_id = :pid AND user_id = :uid LIMIT 1', [
  'pid' => $projectId,
  'uid' => $employeeId,
]);
if ($member === null) {
  $db->insert(
    'INSERT INTO project_members (project_id, user_id, role, created_at) VALUES (:pid, :uid, :r, :c)',
    ['pid' => $projectId, 'uid' => $employeeId, 'r' => 'employee', 'c' => gmdate('c')]
  );
}

// Ensure subcontractor mapping tables exist/linked (best-effort).
try {
  $subRow = $db->fetchOne('SELECT id FROM subcontractors WHERE user_id = :uid LIMIT 1', ['uid' => $subId]);
  $subRowId = $subRow !== null ? (int)$subRow['id'] : (int)$db->insert(
    'INSERT INTO subcontractors (user_id, company_name, status, created_at) VALUES (:uid, :cn, :s, :c)',
    ['uid' => $subId, 'cn' => 'Subcontractor Co', 's' => 'active', 'c' => gmdate('c')]
  );

  $w = $db->fetchOne(
    'SELECT 1 AS ok FROM subcontractor_workers WHERE subcontractor_id = :sid AND user_id = :uid LIMIT 1',
    ['sid' => $subRowId, 'uid' => $subWorkerId]
  );
  if ($w === null) {
    $db->insert(
      'INSERT INTO subcontractor_workers (subcontractor_id, user_id, status, created_at) VALUES (:sid, :uid, :s, :c)',
      ['sid' => $subRowId, 'uid' => $subWorkerId, 's' => 'active', 'c' => gmdate('c')]
    );
  }
} catch (Throwable $_) {
  // ignore if tables do not exist yet
}

// Seed a pending worker approval request (best-effort).
try {
  $pendingUserId = ensure_user($db, 'subcontractor_worker', 'Pending Worker', 'pendingworker@ss.local', 'Worker123!');
  $p = $db->fetchOne(
    'SELECT id FROM subcontractor_workers WHERE subcontractor_id = :sid AND user_id = :uid LIMIT 1',
    ['sid' => $subRowId ?? 0, 'uid' => $pendingUserId]
  );
  if ($p === null && isset($subRowId) && (int)$subRowId > 0) {
    $db->insert(
      'INSERT INTO subcontractor_workers (subcontractor_id, user_id, status, created_at) VALUES (:sid, :uid, :s, :c)',
      ['sid' => (int)$subRowId, 'uid' => $pendingUserId, 's' => 'pending', 'c' => gmdate('c')]
    );
  }
} catch (Throwable $_) {
  // ignore
}

fwrite(STDOUT, "Seed complete.\n");
fwrite(STDOUT, "Accounts:\n");
fwrite(STDOUT, "  admin:  admin@ss.local / Admin123!\n");
fwrite(STDOUT, "  pm:     pm@ss.local / Pm123456!\n");
fwrite(STDOUT, "  client: client@ss.local / Client123!\n");
fwrite(STDOUT, "  employee: employee@ss.local / Employee123!\n");
fwrite(STDOUT, "  subcontractor: sub@ss.local / Sub123456!\n");
fwrite(STDOUT, "  subcontractor_worker: subworker@ss.local / Worker123!\n");
