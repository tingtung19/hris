# Production Checklist

The local `.env` is intentionally configured for the legacy MySQL database and
must not be copied unchanged to production.

## Required before deployment

- [ ] Create a separate production database and backup/restore procedure.
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and the
  real `APP_URL`.
- [ ] Use a dedicated least-privilege MySQL user; never use the local root user.
- [ ] Configure HTTPS and set `SESSION_SECURE_COOKIE=true`.
- [ ] Configure `MAIL_MAILER`, `MAIL_FROM_ADDRESS`, and SMTP credentials through
  the secret manager.
- [ ] Configure a durable private filesystem/object storage for CV and
  reimbursement uploads.
- [ ] Run `php artisan config:cache`, `route:cache`, and `view:cache`.
- [ ] Run one scheduler process: `php artisan schedule:work` or the host
  scheduler invoking `php artisan schedule:run` every minute.
- [ ] Configure a queue worker if `QUEUE_CONNECTION` is changed from `sync`.
- [ ] Restrict storage, `.env`, logs, and database backups from web access.
- [ ] Enable database, application, scheduler, and failed-job monitoring.
- [ ] Perform backup restore verification before the first migration/cutover.

## Manual UAT required

- [ ] Employee: login, attendance, leave request/cancellation, notification.
- [ ] Manager: leave/attendance approval and performance review.
- [ ] HR: employee, recruitment, onboarding/offboarding, training, assets.
- [ ] Finance: payroll, reimbursement verification/payment, reports/export.
- [ ] Auditor: audit logs and permission denial checks.
- [ ] Verify uploads, PDF/Excel/CSV downloads, email delivery, and scheduler
  side effects using non-production test data.
