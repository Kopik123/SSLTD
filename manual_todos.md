# Manual TODOs - Tasks Requiring Human Intervention

**Created**: 2026-02-08  
**Purpose**: Tasks from todos_list2.md that cannot be automated and require human intervention

---

## Infrastructure & External Services

### Email System (Task 8.2)
- [ ] Configure SMTP credentials in production .env
- [ ] Test email delivery in production
- [ ] Set up email templates with brand identity
- [ ] Configure SPF/DKIM records for domain

**Why Manual**: Requires access to email provider, domain DNS, and production environment.

### Push Notifications (Task 9.1)
- [ ] Create Firebase project and obtain API keys
- [ ] Configure FCM in Android app
- [ ] Test notifications on physical devices
- [ ] Set up notification templates

**Why Manual**: Requires Firebase account, Android app build, and device testing.

### Monitoring & Alerting (Task 8.3)
- [ ] Set up Sentry account and obtain DSN
- [ ] Configure production monitoring dashboard
- [ ] Set up alert recipients and escalation
- [ ] Test alert delivery channels

**Why Manual**: Requires external service setup, production environment access.

---

## Production Deployment

### Backup Automation (Task 8.1)
- [ ] Set up cron jobs on production server
- [ ] Configure backup storage location (S3, NAS)
- [ ] Test restore procedure in staging
- [ ] Document disaster recovery plan

**Why Manual**: Requires production server access and storage infrastructure.

### Automatic Deployment (Task 1.2)
- [ ] Configure deployment secrets in GitHub
- [ ] Set up production server SSH keys
- [ ] Test automated deployment to staging
- [ ] Obtain approval for production deployment automation

**Why Manual**: Requires production credentials, stakeholder approval.

---

## Manual QA & Testing

### Coverage Reporting (Task 1.1)
- [ ] Install Xdebug in CI environment
- [ ] Configure PHPUnit code coverage
- [ ] Set up coverage reporting service (Codecov/Coveralls)
- [ ] Review coverage reports and identify gaps

**Why Manual**: Requires CI environment configuration and service setup.

### Full Integration Testing
- [ ] Test complete user workflows end-to-end
- [ ] Test Android app <-> backend integration
- [ ] Perform load testing and identify bottlenecks
- [ ] Security penetration testing

**Why Manual**: Requires manual interaction, security expertise.

---

## Business & Stakeholder Tasks

### Rate Limiting Configuration (Task 2.3)
- [ ] Determine appropriate rate limits for production
- [ ] Test CAPTCHA integration (if required)
- [ ] Obtain stakeholder approval for rate limits
- [ ] Monitor real-world rate limit effectiveness

**Why Manual**: Requires business decision on acceptable limits.

### Analytics Dashboard (Task 10.1)
- [ ] Define key metrics with stakeholders
- [ ] Design dashboard UI/UX
- [ ] Choose charting library
- [ ] Gather feedback from end users

**Why Manual**: Requires stakeholder input and design decisions.

---

## Optional Enhancements

### Internationalization (Task 10.2)
- [ ] Decide on supported languages
- [ ] Hire translators or use translation service
- [ ] Review translations for accuracy
- [ ] Test UI in different locales

**Why Manual**: Requires human translation and cultural adaptation.

### Search Functionality (Task 9.2)
- [ ] Determine search requirements and scope
- [ ] Choose search technology (MySQL FTS vs Elasticsearch)
- [ ] Design search UI/UX
- [ ] Tune search relevance

**Why Manual**: Requires product decisions and performance tuning.

---

## Performance & Optimization

### Query Optimization (Task 6.2)
- [ ] Capture production query performance data
- [ ] Analyze slow query logs
- [ ] Test index additions in staging
- [ ] Monitor production after optimization

**Why Manual**: Requires production database access and analysis.

### Caching Strategy (Task 6.1)
- [ ] Identify cacheable data through profiling
- [ ] Choose caching technology (File, Redis, Memcached)
- [ ] Test cache invalidation strategies
- [ ] Measure performance improvements

**Why Manual**: Requires performance profiling and infrastructure decisions.

---

## Documentation Review

### Architecture Documentation (Task 7.2)
- [ ] Review architecture diagrams with team
- [ ] Validate design patterns documentation
- [ ] Obtain technical review from senior developers
- [ ] Update based on feedback

**Why Manual**: Requires peer review and team collaboration.

---

## Deployment Checklist (for v0.1.0 release)

- [ ] Run `php bin/validate_production.php --strict` on production
- [ ] Review all open security alerts
- [ ] Perform manual QA testing (use `bin/qa_prerelease.php` guide)
- [ ] Obtain stakeholder sign-off
- [ ] Schedule deployment window
- [ ] Execute deployment
- [ ] Monitor error rates post-deployment
- [ ] Verify backup procedures

---

## Notes

**Total Manual Tasks**: ~35 tasks requiring human intervention  
**Categories**: Infrastructure (8), Deployment (3), QA/Testing (4), Business (5), Optional (5), Performance (4), Documentation (2), Deployment Checklist (8)

**Next Steps**:
1. Complete automated tasks first
2. Prioritize manual tasks by business value
3. Schedule infrastructure setup sessions
4. Coordinate with stakeholders for decisions
5. Plan deployment windows

**Estimated Manual Effort**: 80-120 hours of human work
