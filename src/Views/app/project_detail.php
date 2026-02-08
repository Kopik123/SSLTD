<?php
$title = $title ?? 'Project';
$project = $project ?? [];
$threadId = $threadId ?? 0;
$allowedStatuses = $allowedStatuses ?? [];
$attachments = $attachments ?? [];
$materialsCatalog = $materialsCatalog ?? [];
$toolsCatalog = $toolsCatalog ?? [];
$projectMaterials = $projectMaterials ?? [];
$projectTools = $projectTools ?? [];
$deliveries = $deliveries ?? [];
$reports = $reports ?? [];
$issues = $issues ?? [];
$changeRequests = $changeRequests ?? [];
?>

<section class="section">
  <h2>Project #<?= e((string)($project['id'] ?? '')) ?></h2>

  <div class="grid-3 grid-3--lead">
    <div class="card">
      <div class="title">Overview</div>
      <div class="fw-700"><?= e((string)($project['name'] ?? '')) ?></div>
      <div class="muted mt-6"><?= e((string)($project['address'] ?? '')) ?></div>

      <div class="title mt-12">Client</div>
      <?php if (!empty($project['client_name']) || !empty($project['client_email'])): ?>
        <div class="fw-700"><?= e((string)($project['client_name'] ?? '')) ?></div>
        <div class="muted"><?= e((string)($project['client_email'] ?? '')) ?></div>
      <?php else: ?>
        <div class="muted">-</div>
      <?php endif; ?>

      <?php if (!empty($project['lead_id'])): ?>
        <div class="title mt-12">Source lead</div>
        <div class="muted">
          <a href="<?= e(app_url($ctx, '/app/leads/' . (string)$project['lead_id'])) ?>">Lead #<?= e((string)$project['lead_id']) ?></a>
          <span class="badge badge--stone ml-6"><?= e((string)($project['lead_status'] ?? '')) ?></span>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="title">Status</div>
      <div class="mt-6">
        <span class="badge badge--gold"><?= e((string)($project['status'] ?? '')) ?></span>
      </div>
      <?php if (is_array($allowedStatuses) && $allowedStatuses !== []): ?>
        <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/status')) ?>" class="mt-10">
          <?= csrf_field($ctx) ?>
          <div class="field mb-0">
            <label for="status">Update status</label>
            <select id="status" name="status">
              <?php foreach ($allowedStatuses as $st): ?>
                <option value="<?= e((string)$st) ?>" <?= ((string)($project['status'] ?? '') === (string)$st) ? 'selected' : '' ?>>
                  <?= e((string)$st) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn mt-10" type="submit">Save</button>
        </form>
      <?php endif; ?>

      <div class="title mt-12">Assigned PM</div>
      <div class="muted"><?= e((string)($project['pm_name'] ?? 'Unassigned')) ?></div>

      <div class="title mt-12">Budget</div>
      <div class="muted">$<?= e((string)number_format(((int)($project['budget_cents'] ?? 0)) / 100, 2)) ?></div>
    </div>

    <div class="card">
      <div class="title">Actions</div>

      <?php if (is_int($threadId) && $threadId > 0): ?>
        <div class="mt-10">
          <a class="btn btn--gold" href="<?= e(app_url($ctx, '/app/messages/' . (string)$threadId)) ?>">Open messages</a>
        </div>
      <?php endif; ?>

      <div class="mt-10">
        <a class="btn" href="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/checklist')) ?>">Open checklist</a>
      </div>

      <div class="notice mt-12">
        Checklist pricing can be locked if it originates from an approved estimate. In locked mode you can update item status only.
      </div>
    </div>
  </div>
</section>

<?php
  $proposal = $scheduleProposal ?? null;
  $events = $scheduleEvents ?? [];
?>

<section class="section">
  <h2>Schedule</h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Latest proposal</div>
      <?php if (!is_array($proposal) || empty($proposal['id'])): ?>
        <div class="muted mt-6">No proposal yet.</div>
      <?php else: ?>
        <div class="muted mt-6">Status: <span class="badge badge--stone"><?= e((string)($proposal['status'] ?? '')) ?></span></div>
        <div class="muted mt-6">Start: <?= e((string)($proposal['starts_at'] ?? '')) ?></div>
        <div class="muted">End: <?= e((string)($proposal['ends_at'] ?? '')) ?></div>
        <?php if (!empty($proposal['note'])): ?>
          <div class="title mt-12">Note</div>
          <div class="muted ws-prewrap mt-6"><?= e((string)$proposal['note']) ?></div>
        <?php endif; ?>
        <?php if (!empty($proposal['decided_at'])): ?>
          <div class="muted mt-12">Decided: <?= e((string)$proposal['decided_at']) ?><?= !empty($proposal['decided_by_name']) ? (' • ' . e((string)$proposal['decided_by_name'])) : '' ?></div>
        <?php endif; ?>
        <?php if (!empty($proposal['decision_note'])): ?>
          <div class="title mt-12">Client note</div>
          <div class="muted ws-prewrap mt-6"><?= e((string)$proposal['decision_note']) ?></div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="card card--stone">
      <div class="title">Propose to client</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/schedule/propose')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="row">
          <div class="field mb-0">
            <label for="starts_at">Start</label>
            <input id="starts_at" name="starts_at" type="datetime-local" required>
          </div>
          <div class="field mb-0">
            <label for="ends_at">End</label>
            <input id="ends_at" name="ends_at" type="datetime-local" required>
          </div>
        </div>
        <div class="field mt-10">
          <label for="note">Note (optional)</label>
          <textarea id="note" name="note" placeholder="Milestones, access instructions, constraints..."></textarea>
        </div>
        <button class="btn btn--gold" type="submit">Submit proposal</button>
        <div class="muted mt-6">Client will see it in Portal → Approvals.</div>
      </form>
    </div>
  </div>

  <div class="card mt-14">
    <div class="title">Approved events</div>
    <?php if (($events ?? []) === []): ?>
      <div class="muted mt-6">No approved events yet.</div>
    <?php else: ?>
      <table class="table mt-12">
        <thead>
          <tr>
            <th>Title</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $ev): ?>
            <tr>
              <td class="fw-700"><?= e((string)($ev['title'] ?? '')) ?></td>
              <td class="muted"><?= e((string)($ev['starts_at'] ?? '')) ?></td>
              <td class="muted"><?= e((string)($ev['ends_at'] ?? '')) ?></td>
              <td class="muted"><?= e((string)($ev['status'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>

<?php
  $pmStatuses = ['required', 'ordered', 'delivered', 'cancelled'];
  $ptStatuses = ['required', 'assigned', 'on_site', 'in_storage', 'in_service', 'cancelled'];
  $delStatuses = ['pending', 'delivered', 'cancelled'];
?>

<section class="section">
  <h2>Materials &amp; Tools</h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Add material</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/materials')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="material_id">Material</label>
          <select id="material_id" name="material_id" required>
            <option value="" selected disabled>Select material</option>
            <?php foreach (($materialsCatalog ?? []) as $m): ?>
              <option value="<?= e((string)$m['id']) ?>"><?= e((string)($m['name'] ?? '')) ?><?= !empty($m['unit']) ? (' (' . e((string)$m['unit']) . ')') : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
          <div class="field mb-0">
            <label for="pm_qty">Qty</label>
            <input id="pm_qty" name="qty" inputmode="decimal" placeholder="0">
          </div>
          <div class="field mb-0">
            <label for="pm_status">Status</label>
            <select id="pm_status" name="status">
              <?php foreach ($pmStatuses as $st): ?>
                <option value="<?= e($st) ?>" <?= $st === 'required' ? 'selected' : '' ?>><?= e($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row mt-10">
          <div class="field mb-0">
            <label for="pm_needed_by">Needed by (optional)</label>
            <input id="pm_needed_by" name="needed_by" placeholder="2026-02-20 or 2026-02-20T09:00">
          </div>
          <div class="field mb-0">
            <label for="pm_notes">Notes (optional)</label>
            <input id="pm_notes" name="notes">
          </div>
        </div>
        <button class="btn btn--gold mt-10" type="submit">Add</button>
      </form>
    </div>

    <div class="card card--stone">
      <div class="title">Add tool</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/tools')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="tool_id">Tool</label>
          <select id="tool_id" name="tool_id" required>
            <option value="" selected disabled>Select tool</option>
            <?php foreach (($toolsCatalog ?? []) as $t): ?>
              <option value="<?= e((string)$t['id']) ?>"><?= e((string)($t['name'] ?? '')) ?><?= !empty($t['serial']) ? (' • ' . e((string)$t['serial'])) : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="pt_status">Status</label>
          <select id="pt_status" name="status">
            <?php foreach ($ptStatuses as $st): ?>
              <option value="<?= e($st) ?>" <?= $st === 'required' ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="pt_notes">Notes (optional)</label>
          <input id="pt_notes" name="notes">
        </div>
        <button class="btn btn--gold" type="submit">Add</button>
      </form>
    </div>
  </div>

  <div class="card mt-14">
    <div class="title">Project materials</div>
    <?php if (($projectMaterials ?? []) === []): ?>
      <div class="muted mt-6">No materials assigned to this project yet.</div>
    <?php else: ?>
      <?php foreach ($projectMaterials as $pm): ?>
        <div class="thread-msg">
          <div class="fw-700"><?= e((string)($pm['material_name'] ?? 'Material')) ?><?= !empty($pm['material_unit']) ? (' (' . e((string)$pm['material_unit']) . ')') : '' ?></div>
          <div class="muted mt-4">Status: <?= e((string)($pm['status'] ?? '')) ?> • Qty: <?= e((string)($pm['qty'] ?? '0')) ?><?= !empty($pm['needed_by']) ? (' • Needed by: ' . e((string)$pm['needed_by'])) : '' ?></div>

          <form method="post" action="<?= e(app_url($ctx, '/app/project-materials/' . (string)$pm['id'] . '/update')) ?>" class="mt-10">
            <?= csrf_field($ctx) ?>
            <div class="row">
              <div class="field mb-0">
                <label for="pm_qty_<?= e((string)$pm['id']) ?>">Qty</label>
                <input id="pm_qty_<?= e((string)$pm['id']) ?>" name="qty" inputmode="decimal" value="<?= e((string)($pm['qty'] ?? '0')) ?>">
              </div>
              <div class="field mb-0">
                <label for="pm_st_<?= e((string)$pm['id']) ?>">Status</label>
                <select id="pm_st_<?= e((string)$pm['id']) ?>" name="status">
                  <?php foreach ($pmStatuses as $st): ?>
                    <option value="<?= e($st) ?>" <?= ((string)($pm['status'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row mt-10">
              <div class="field mb-0">
                <label for="pm_nb_<?= e((string)$pm['id']) ?>">Needed by</label>
                <input id="pm_nb_<?= e((string)$pm['id']) ?>" name="needed_by" value="<?= e((string)($pm['needed_by'] ?? '')) ?>">
              </div>
              <div class="field mb-0">
                <label for="pm_n_<?= e((string)$pm['id']) ?>">Notes</label>
                <input id="pm_n_<?= e((string)$pm['id']) ?>" name="notes" value="<?= e((string)($pm['notes'] ?? '')) ?>">
              </div>
            </div>
            <div class="flex gap-10 items-center mt-10">
              <button class="btn btn--gold" type="submit">Save</button>
              <button class="btn" type="submit" formaction="<?= e(app_url($ctx, '/app/project-materials/' . (string)$pm['id'] . '/delete')) ?>">Remove</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card mt-14">
    <div class="title">Project tools</div>
    <?php if (($projectTools ?? []) === []): ?>
      <div class="muted mt-6">No tools assigned to this project yet.</div>
    <?php else: ?>
      <?php foreach ($projectTools as $pt): ?>
        <div class="thread-msg">
          <div class="fw-700"><?= e((string)($pt['tool_name'] ?? 'Tool')) ?><?= !empty($pt['tool_serial']) ? (' • ' . e((string)$pt['tool_serial'])) : '' ?></div>
          <div class="muted mt-4">Status: <?= e((string)($pt['status'] ?? '')) ?><?= !empty($pt['tool_location']) ? (' • Location: ' . e((string)$pt['tool_location'])) : '' ?></div>

          <form method="post" action="<?= e(app_url($ctx, '/app/project-tools/' . (string)$pt['id'] . '/update')) ?>" class="mt-10">
            <?= csrf_field($ctx) ?>
            <div class="row">
              <div class="field mb-0">
                <label for="pt_st_<?= e((string)$pt['id']) ?>">Status</label>
                <select id="pt_st_<?= e((string)$pt['id']) ?>" name="status">
                  <?php foreach ($ptStatuses as $st): ?>
                    <option value="<?= e($st) ?>" <?= ((string)($pt['status'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="field mb-0">
                <label for="pt_n_<?= e((string)$pt['id']) ?>">Notes</label>
                <input id="pt_n_<?= e((string)$pt['id']) ?>" name="notes" value="<?= e((string)($pt['notes'] ?? '')) ?>">
              </div>
            </div>
            <div class="flex gap-10 items-center mt-10">
              <button class="btn btn--gold" type="submit">Save</button>
              <button class="btn" type="submit" formaction="<?= e(app_url($ctx, '/app/project-tools/' . (string)$pt['id'] . '/delete')) ?>">Remove</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <h2>Deliveries</h2>

  <div class="card">
    <div class="title">Create delivery</div>
    <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/deliveries')) ?>" class="mt-10">
      <?= csrf_field($ctx) ?>
      <div class="row">
        <div class="field mb-0">
          <label for="del_material_id">Material</label>
          <select id="del_material_id" name="material_id" required>
            <option value="" selected disabled>Select material</option>
            <?php foreach (($materialsCatalog ?? []) as $m): ?>
              <option value="<?= e((string)$m['id']) ?>"><?= e((string)($m['name'] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field mb-0">
          <label for="del_qty">Qty</label>
          <input id="del_qty" name="qty" inputmode="decimal" placeholder="0">
        </div>
      </div>
      <div class="row mt-10">
        <div class="field mb-0">
          <label for="del_status">Status</label>
          <select id="del_status" name="status">
            <?php foreach ($delStatuses as $st): ?>
              <option value="<?= e($st) ?>" <?= $st === 'pending' ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field mb-0">
          <label for="del_expected_at">Expected at (optional)</label>
          <input id="del_expected_at" name="expected_at" placeholder="2026-02-20 or 2026-02-20T09:00">
        </div>
      </div>
      <div class="field mt-10">
        <label for="del_notes">Notes (optional)</label>
        <input id="del_notes" name="notes">
      </div>
      <button class="btn btn--gold" type="submit">Create</button>
    </form>
  </div>

  <div class="card mt-14">
    <div class="title">Delivery list</div>
    <?php if (($deliveries ?? []) === []): ?>
      <div class="muted mt-6">No deliveries yet.</div>
    <?php else: ?>
      <?php foreach ($deliveries as $d): ?>
        <div class="thread-msg">
          <div class="fw-700"><?= e((string)($d['material_name'] ?? 'Material')) ?><?= !empty($d['material_unit']) ? (' (' . e((string)$d['material_unit']) . ')') : '' ?></div>
          <div class="muted mt-4">
            Status: <?= e((string)($d['status'] ?? '')) ?> • Qty: <?= e((string)($d['qty'] ?? '0')) ?>
            <?= !empty($d['expected_at']) ? (' • Expected: ' . e((string)$d['expected_at'])) : '' ?>
            <?= !empty($d['delivered_at']) ? (' • Delivered: ' . e((string)$d['delivered_at'])) : '' ?>
          </div>

          <form method="post" action="<?= e(app_url($ctx, '/app/deliveries/' . (string)$d['id'] . '/update')) ?>" class="mt-10">
            <?= csrf_field($ctx) ?>
            <div class="row">
              <div class="field mb-0">
                <label for="d_qty_<?= e((string)$d['id']) ?>">Qty</label>
                <input id="d_qty_<?= e((string)$d['id']) ?>" name="qty" inputmode="decimal" value="<?= e((string)($d['qty'] ?? '0')) ?>">
              </div>
              <div class="field mb-0">
                <label for="d_st_<?= e((string)$d['id']) ?>">Status</label>
                <select id="d_st_<?= e((string)$d['id']) ?>" name="status">
                  <?php foreach ($delStatuses as $st): ?>
                    <option value="<?= e($st) ?>" <?= ((string)($d['status'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row mt-10">
              <div class="field mb-0">
                <label for="d_ea_<?= e((string)$d['id']) ?>">Expected at</label>
                <input id="d_ea_<?= e((string)$d['id']) ?>" name="expected_at" value="<?= e((string)($d['expected_at'] ?? '')) ?>">
              </div>
              <div class="field mb-0">
                <label for="d_da_<?= e((string)$d['id']) ?>">Delivered at</label>
                <input id="d_da_<?= e((string)$d['id']) ?>" name="delivered_at" value="<?= e((string)($d['delivered_at'] ?? '')) ?>">
              </div>
            </div>
            <div class="field mt-10">
              <label for="d_n_<?= e((string)$d['id']) ?>">Notes</label>
              <input id="d_n_<?= e((string)$d['id']) ?>" name="notes" value="<?= e((string)($d['notes'] ?? '')) ?>">
            </div>
            <div class="flex gap-10 items-center mt-10">
              <button class="btn btn--gold" type="submit">Save</button>
              <button class="btn" type="submit" formaction="<?= e(app_url($ctx, '/app/deliveries/' . (string)$d['id'] . '/delete')) ?>">Delete</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <h2>Change requests</h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Create change request</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/change-requests')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="cr_title">Title</label>
          <input id="cr_title" name="title" required maxlength="255" placeholder="e.g. Add recessed lighting">
        </div>
        <div class="field">
          <label for="cr_body">Details (optional)</label>
          <textarea id="cr_body" name="body" placeholder="Reason, scope change, assumptions..."></textarea>
        </div>
        <div class="row">
          <div class="field mb-0">
            <label for="cr_cost_delta">Cost delta ($)</label>
            <input id="cr_cost_delta" name="cost_delta" inputmode="decimal" placeholder="0.00">
          </div>
          <div class="field mb-0">
            <label for="cr_schedule_delta_days">Schedule delta (days)</label>
            <input id="cr_schedule_delta_days" name="schedule_delta_days" inputmode="numeric" placeholder="0">
          </div>
        </div>
        <div class="field mt-10">
          <label for="cr_submit_now">
            <input id="cr_submit_now" type="checkbox" name="submit_now" value="1">
            Submit to client immediately
          </label>
        </div>
        <button class="btn btn--gold" type="submit">Create</button>
        <div class="muted mt-10">Submitted change requests appear in Client Portal → Approvals.</div>
      </form>
    </div>

    <div class="card card--stone">
      <div class="title">History</div>
      <?php if (($changeRequests ?? []) === []): ?>
        <div class="muted mt-10">No change requests yet.</div>
      <?php else: ?>
        <?php foreach ($changeRequests as $cr): ?>
          <?php
            $st = (string)($cr['status'] ?? 'draft');
            $badge = $st === 'submitted' ? 'badge--gold' : ($st === 'approved' ? 'badge--good' : ($st === 'rejected' ? 'badge--bad' : 'badge--stone'));
            $cost = (int)($cr['cost_delta_cents'] ?? 0);
          ?>
          <div class="thread-msg">
            <div class="flex items-center justify-between gap-10">
              <div class="fw-700">#<?= e((string)($cr['id'] ?? '')) ?> • <?= e((string)($cr['title'] ?? '')) ?></div>
              <span class="badge <?= e($badge) ?>"><?= e($st) ?></span>
            </div>
            <div class="muted mt-4">
              Cost delta: $<?= e((string)number_format($cost / 100, 2)) ?> • Schedule delta: <?= e((string)($cr['schedule_delta_days'] ?? 0)) ?> days
            </div>
            <?php if (!empty($cr['body'])): ?>
              <div class="ws-prewrap mt-6"><?= e((string)$cr['body']) ?></div>
            <?php endif; ?>
            <div class="muted mt-6">
              Created: <?= e((string)($cr['created_at'] ?? '')) ?><?= !empty($cr['created_by_name']) ? (' • ' . e((string)$cr['created_by_name'])) : '' ?>
              <?php if (!empty($cr['submitted_at'])): ?> • Submitted: <?= e((string)$cr['submitted_at']) ?><?php endif; ?>
              <?php if (!empty($cr['decided_at'])): ?> • Decided: <?= e((string)$cr['decided_at']) ?><?= !empty($cr['decided_by_name']) ? (' • ' . e((string)$cr['decided_by_name'])) : '' ?><?php endif; ?>
            </div>
            <?php if (!empty($cr['decision_note'])): ?>
              <div class="title mt-10">Client note</div>
              <div class="muted ws-prewrap mt-6"><?= e((string)$cr['decision_note']) ?></div>
            <?php endif; ?>

            <?php if ($st === 'draft'): ?>
              <form method="post" action="<?= e(app_url($ctx, '/app/change-requests/' . (string)$cr['id'] . '/update')) ?>" class="mt-10">
                <?= csrf_field($ctx) ?>
                <div class="field">
                  <label for="cr_t_<?= e((string)$cr['id']) ?>">Title</label>
                  <input id="cr_t_<?= e((string)$cr['id']) ?>" name="title" maxlength="255" value="<?= e((string)($cr['title'] ?? '')) ?>" required>
                </div>
                <div class="field">
                  <label for="cr_b_<?= e((string)$cr['id']) ?>">Details</label>
                  <textarea id="cr_b_<?= e((string)$cr['id']) ?>" name="body"><?= e((string)($cr['body'] ?? '')) ?></textarea>
                </div>
                <div class="row">
                  <div class="field mb-0">
                    <label for="cr_c_<?= e((string)$cr['id']) ?>">Cost delta ($)</label>
                    <input id="cr_c_<?= e((string)$cr['id']) ?>" name="cost_delta" inputmode="decimal" value="<?= e((string)number_format($cost / 100, 2)) ?>">
                  </div>
                  <div class="field mb-0">
                    <label for="cr_s_<?= e((string)$cr['id']) ?>">Schedule delta (days)</label>
                    <input id="cr_s_<?= e((string)$cr['id']) ?>" name="schedule_delta_days" inputmode="numeric" value="<?= e((string)($cr['schedule_delta_days'] ?? 0)) ?>">
                  </div>
                </div>
                <div class="flex gap-10 items-center mt-10">
                  <button class="btn btn--gold" type="submit">Save</button>
                  <button class="btn" type="submit" formaction="<?= e(app_url($ctx, '/app/change-requests/' . (string)$cr['id'] . '/submit')) ?>">Submit</button>
                  <button class="btn" type="submit" formaction="<?= e(app_url($ctx, '/app/change-requests/' . (string)$cr['id'] . '/delete')) ?>">Delete</button>
                </div>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php
  $issueStatuses = ['open', 'in_progress', 'blocked', 'resolved', 'closed'];
  $issueSeverities = ['low', 'medium', 'high'];
?>

<section class="section">
  <h2>Reports</h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Add progress report</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/reports')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field mb-0">
          <label for="report_body">Update</label>
          <textarea id="report_body" name="body" required placeholder="What happened today? What's next? Any blockers?"></textarea>
        </div>
        <button class="btn btn--gold mt-10" type="submit">Add report</button>
      </form>
      <div class="muted mt-10">Tip: attach photos in Files (stage: before/during/after) and reference them in the report.</div>
    </div>

    <div class="card card--stone">
      <div class="title">Recent reports</div>
      <?php if (($reports ?? []) === []): ?>
        <div class="muted mt-10">No reports yet.</div>
      <?php else: ?>
        <?php foreach ($reports as $r): ?>
          <div class="thread-msg">
            <div class="muted"><?= e((string)($r['created_at'] ?? '')) ?><?= !empty($r['created_by_name']) ? (' • ' . e((string)$r['created_by_name'])) : '' ?></div>
            <div class="ws-prewrap mt-6"><?= e((string)($r['body'] ?? '')) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <h2>Issues</h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Create issue</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/issues')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="issue_title">Title</label>
          <input id="issue_title" name="title" required maxlength="255" placeholder="e.g. Water shutoff not accessible">
        </div>
        <div class="row">
          <div class="field mb-0">
            <label for="issue_severity">Severity</label>
            <select id="issue_severity" name="severity">
              <?php foreach ($issueSeverities as $sev): ?>
                <option value="<?= e($sev) ?>" <?= $sev === 'medium' ? 'selected' : '' ?>><?= e($sev) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field mb-0">
            <label for="issue_body">Details (optional)</label>
            <input id="issue_body" name="body" placeholder="Short details / steps / context...">
          </div>
        </div>
        <button class="btn btn--gold mt-10" type="submit">Create issue</button>
      </form>
    </div>

    <div class="card card--stone">
      <div class="title">Issue list</div>
      <?php if (($issues ?? []) === []): ?>
        <div class="muted mt-10">No issues yet.</div>
      <?php else: ?>
        <?php foreach ($issues as $i): ?>
          <?php
            $st = (string)($i['status'] ?? 'open');
            $sev = (string)($i['severity'] ?? 'medium');
            $badge = $st === 'open' ? 'badge--gold' : ($st === 'resolved' || $st === 'closed' ? 'badge--good' : 'badge--stone');
          ?>
          <div class="thread-msg">
            <div class="flex items-center justify-between gap-10">
              <div class="fw-700"><?= e((string)($i['title'] ?? 'Issue')) ?></div>
              <div class="flex items-center gap-10">
                <span class="badge badge--stone"><?= e($sev) ?></span>
                <span class="badge <?= e($badge) ?>"><?= e($st) ?></span>
              </div>
            </div>
            <div class="muted mt-4"><?= e((string)($i['created_at'] ?? '')) ?><?= !empty($i['created_by_name']) ? (' • ' . e((string)$i['created_by_name'])) : '' ?><?= !empty($i['resolved_at']) ? (' • Resolved: ' . e((string)$i['resolved_at'])) : '' ?></div>
            <?php if (!empty($i['body'])): ?>
              <div class="ws-prewrap mt-6"><?= e((string)$i['body']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(app_url($ctx, '/app/issues/' . (string)$i['id'] . '/update')) ?>" class="mt-10">
              <?= csrf_field($ctx) ?>
              <div class="row">
                <div class="field mb-0">
                  <label for="i_st_<?= e((string)$i['id']) ?>">Status</label>
                  <select id="i_st_<?= e((string)$i['id']) ?>" name="status">
                    <?php foreach ($issueStatuses as $opt): ?>
                      <option value="<?= e($opt) ?>" <?= $opt === $st ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field mb-0">
                  <label for="i_sev_<?= e((string)$i['id']) ?>">Severity</label>
                  <select id="i_sev_<?= e((string)$i['id']) ?>" name="severity">
                    <?php foreach ($issueSeverities as $opt): ?>
                      <option value="<?= e($opt) ?>" <?= $opt === $sev ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="field mt-10">
                <label for="i_title_<?= e((string)$i['id']) ?>">Title</label>
                <input id="i_title_<?= e((string)$i['id']) ?>" name="title" maxlength="255" value="<?= e((string)($i['title'] ?? '')) ?>" required>
              </div>
              <div class="field">
                <label for="i_body_<?= e((string)$i['id']) ?>">Details</label>
                <textarea id="i_body_<?= e((string)$i['id']) ?>" name="body"><?= e((string)($i['body'] ?? '')) ?></textarea>
              </div>
              <button class="btn btn--gold" type="submit">Save issue</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <h2>Files</h2>
  <div class="card">
    <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/uploads')) ?>" enctype="multipart/form-data" class="mb-12">
      <?= csrf_field($ctx) ?>
      <div class="row">
        <div class="field mb-0">
          <label for="file">Upload file (jpg/png/pdf)</label>
          <input id="file" name="file" type="file" required>
        </div>
        <div class="field mb-0">
          <label for="stage">Stage</label>
          <select id="stage" name="stage">
            <option value="doc" selected>doc</option>
            <option value="before">before</option>
            <option value="during">during</option>
            <option value="after">after</option>
          </select>
        </div>
        <div class="field mb-0">
          <label for="client_visible">Client visibility</label>
          <select id="client_visible" name="client_visible">
            <option value="0" selected>internal</option>
            <option value="1">client-visible</option>
          </select>
        </div>
      </div>
      <div class="mt-10">
        <button class="btn btn--gold" type="submit">Upload</button>
      </div>
    </form>

    <?php if (($attachments ?? []) === []): ?>
      <div class="muted">No files yet.</div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Size</th>
            <th>Stage</th>
            <th>Visibility</th>
            <th>Uploaded</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($attachments as $a): ?>
            <tr>
              <td>
                <a href="<?= e(app_url($ctx, '/app/uploads/' . (string)$a['id'])) ?>">
                  <?= e((string)$a['original_name']) ?>
                </a>
              </td>
              <td class="muted"><?= e((string)$a['mime_type']) ?></td>
              <td class="muted"><?= e((string)$a['size_bytes']) ?> bytes</td>
              <td class="muted"><?= e((string)($a['stage'] ?? '')) ?></td>
              <td>
                <form method="post" action="<?= e(app_url($ctx, '/app/uploads/' . (string)$a['id'] . '/visibility')) ?>">
                  <?= csrf_field($ctx) ?>
                  <input type="hidden" name="back" value="<?= e('/app/projects/' . (string)($project['id'] ?? '')) ?>">
                  <select name="client_visible">
                    <option value="0" <?= ((int)($a['client_visible'] ?? 0) === 0) ? 'selected' : '' ?>>internal</option>
                    <option value="1" <?= ((int)($a['client_visible'] ?? 0) === 1) ? 'selected' : '' ?>>client-visible</option>
                  </select>
                  <button class="btn ml-8" type="submit">Save</button>
                </form>
              </td>
              <td class="muted"><?= e((string)$a['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>
