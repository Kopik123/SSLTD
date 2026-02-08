<?php
$title = $title ?? 'Request a Quote';
$mode = $mode ?? '';
if (!is_string($mode)) {
  $mode = '';
}
?>

<section class="section">
  <h2>Request a quote</h2>

  <?php if ($mode !== 'simple' && $mode !== 'advanced'): ?>
    <div class="card mb-14">
      <div class="title">Choose your intake</div>
      <div class="muted mt-6">
        Simple is fast: one message plus contact details.
        Advanced collects scope, address, dates, and attachments to speed up estimation.
      </div>
    </div>

    <div class="grid-2">
      <div class="card">
        <div class="title">Simple</div>
        <div class="muted mt-6">
          Contact details + one text box.
        </div>
        <div class="mt-12">
          <a class="btn btn--gold" href="<?= e(app_url($ctx, '/quote-request?mode=simple')) ?>">Start simple</a>
        </div>
      </div>

      <div class="card card--stone">
        <div class="title">Advanced</div>
        <div class="muted mt-6">
          Address, scope, preferred dates, and optional photos/files.
        </div>
        <div class="mt-12">
          <a class="btn btn--gold" href="<?= e(app_url($ctx, '/quote-request?mode=advanced')) ?>">Start advanced</a>
        </div>
      </div>
    </div>
  <?php elseif ($mode === 'simple'): ?>
    <div class="card">
      <div class="flex justify-between items-center gap-10 mb-12">
        <div class="muted">Mode: <span class="badge badge--gold">Simple</span></div>
        <a class="btn" href="<?= e(app_url($ctx, '/quote-request')) ?>">Change</a>
      </div>

      <form method="post" action="<?= e(app_url($ctx, '/quote-request')) ?>">
        <?= csrf_field($ctx) ?>
        <input type="hidden" name="mode" value="simple">

        <div class="row">
          <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" required>
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" placeholder="Optional">
          </div>
        </div>

        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required>
        </div>

        <div class="field">
          <label for="description">What do you need?</label>
          <textarea id="description" name="description" required placeholder="Describe the work. Example: kitchen remodel, timeline expectations, any constraints."></textarea>
          <div class="muted mt-6">Minimum 10 characters.</div>
        </div>

        <div class="field">
          <label>
            <input type="checkbox" name="consent_privacy" value="1" required>
            I agree to the privacy policy (MVP placeholder).
          </label>
        </div>

        <button class="btn btn--gold" type="submit">Send request</button>
      </form>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="flex justify-between items-center gap-10 mb-12">
        <div class="muted">Mode: <span class="badge badge--stone">Advanced</span></div>
        <a class="btn" href="<?= e(app_url($ctx, '/quote-request')) ?>">Change</a>
      </div>

      <form method="post" action="<?= e(app_url($ctx, '/quote-request')) ?>" enctype="multipart/form-data">
        <?= csrf_field($ctx) ?>
        <input type="hidden" name="mode" value="advanced">

        <div class="row">
          <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" required>
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" placeholder="Optional">
          </div>
        </div>

        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required>
        </div>

        <div class="field">
          <label for="address">Project address</label>
          <input id="address" name="address" required placeholder="City, State (MVP)">
        </div>

        <div class="field">
          <label>Scope</label>
          <div class="row">
            <label><input type="checkbox" name="scope[]" value="kitchen_remodel"> Kitchen remodel</label>
            <label><input type="checkbox" name="scope[]" value="bathroom_remodel"> Bathroom remodel</label>
            <label><input type="checkbox" name="scope[]" value="extension"> Extension</label>
            <label><input type="checkbox" name="scope[]" value="other"> Other</label>
          </div>
        </div>

        <div class="field">
          <label for="description">Details</label>
          <textarea id="description" name="description" placeholder="What needs to be done? Any constraints?"></textarea>
        </div>

        <div class="field">
          <label>Preferred dates (optional)</label>
          <div class="row">
            <input type="date" name="preferred_dates[]">
            <input type="date" name="preferred_dates[]">
          </div>
        </div>

        <div class="field">
          <label for="attachments">Photos / files (optional)</label>
          <input id="attachments" type="file" name="attachments[]" multiple accept=".png,.jpg,.jpeg,.pdf" data-filelist="filelist">
          <div id="filelist" class="muted mt-6">No files selected</div>
          <div class="muted mt-6">Allowed: JPG/PNG/PDF. Max 10 files, 10MB each.</div>
        </div>

        <div class="field">
          <label>
            <input type="checkbox" name="consent_privacy" value="1" required>
            I agree to the privacy policy (MVP placeholder).
          </label>
        </div>

        <button class="btn btn--gold" type="submit">Send request</button>
      </form>
    </div>
  <?php endif; ?>
</section>
