<?php $title = $title ?? 'S&S LTD'; ?>

<section class="hero">
  <div class="hero-grid">
    <div>
      <h1>Craftsmanship, scheduled.</h1>
      <p>
        S&amp;S LTD delivers renovation projects with a structured workflow.
        Clear scope, documented progress, and predictable schedules.
      </p>
      <div class="flex gap-10 flex-wrap">
        <a class="btn btn--gold" href="<?= e(app_url($ctx, '/quote-request')) ?>">Request a quote</a>
        <a class="btn" href="<?= e(app_url($ctx, '/services')) ?>">Browse services</a>
      </div>
    </div>
    <div class="hero-card">
      <div class="title">
        Service area
      </div>
      <div class="muted mt-6">
        Metro Boston and nearby towns (approx. 60 miles).
      </div>
      <div class="kpi">
        <div class="kpi-box">
          <div class="num">Before</div>
          <div class="label">Photos + scope</div>
        </div>
        <div class="kpi-box">
          <div class="num">During</div>
          <div class="label">Progress reports</div>
        </div>
        <div class="kpi-box">
          <div class="num">After</div>
          <div class="label">Final checklist</div>
        </div>
        <div class="kpi-box">
          <div class="num">Approved</div>
          <div class="label">Client sign-off</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <h2>What you get</h2>
  <div class="grid-3">
    <div class="card">
      <div class="title">Scope clarity</div>
      <div class="muted">Checklist-based estimation and approvals, so nothing "floats".</div>
    </div>
    <div class="card">
      <div class="title">Real updates</div>
      <div class="muted">Progress notes and photos tied to the project timeline.</div>
    </div>
    <div class="card">
      <div class="title">Schedule control</div>
      <div class="muted">Resource-aware calendar to avoid conflicts and delays.</div>
    </div>
  </div>
</section>
