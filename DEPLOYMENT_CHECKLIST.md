# Deployment Checklist - Attendance & Payroll System

## Pre-Deployment (Local Testing)

- [ ] All PHP files have valid syntax (checked: ✓)
- [ ] JavaScript files are validated (checked: ✓)
- [ ] CSS is well-formed (checked: ✓)
- [ ] JSON manifest is valid (checked: ✓)
- [ ] Database schema is complete (setup.sql ready)
- [ ] Security configuration reviewed (.htaccess)

## Hostinger Setup Steps

### 1. File Upload
- [ ] Connect to Hostinger via FTP or File Manager
- [ ] Upload all files to public_html/ directory:
  ```
  .htaccess
  README.md
  api.php
  app.js
  auth.php
  config.php
  db.php
  index.php
  manifest.json
  service-worker.js
  setup.sql
  style.css
  ```
- [ ] Create 'uploads' folder with 755 permissions
- [ ] Create 'uploads/photos' subfolder
- [ ] Create 'uploads/expenses' subfolder
- [ ] Verify .htaccess file is uploaded (may be hidden)

### 2. Database Setup
- [ ] Create new MySQL database via Hostinger Control Panel
- [ ] Create database user and note credentials
- [ ] Open phpMyAdmin
- [ ] Select the new database
- [ ] Go to Import tab
- [ ] Upload setup.sql
- [ ] Click Import
- [ ] Verify tables created (users, attendance, leaves, expenses, payroll, audit_logs)

### 3. Configuration
- [ ] Edit config.php
- [ ] Update DB_HOST (usually 'localhost' on Hostinger)
- [ ] Update DB_USER (your database user)
- [ ] Update DB_PASS (your database password)
- [ ] Update DB_NAME (your database name)
- [ ] Save config.php
- [ ] Update office geofence coordinates (config.php):
  - OFFICE_LAT
  - OFFICE_LNG
  - OFFICE_RADIUS

### 4. Security Hardening
- [ ] Verify .htaccess is in place
- [ ] Check upload directories are restricted (.htaccess in uploads)
- [ ] Verify config.php is not accessible via URL
- [ ] Test HTTPS redirect (should auto-redirect to HTTPS)
- [ ] Disable display_errors in config.php (production)

### 5. Testing
- [ ] Test login with admin@company.com / PIN: 1234
- [ ] Test employee login with john@company.com / PIN: 1111
- [ ] Test intern login with intern@company.com / PIN: 2222
- [ ] Test punch-in (allow location & camera access)
- [ ] Test camera capture (full-screen)
- [ ] Test geofence validation (office slot)
- [ ] Test WFH slot (no geofence)
- [ ] Test Site Visit conversion
- [ ] Test punch-out with work summary
- [ ] Test admin leave approval
- [ ] Test admin expense approval
- [ ] Verify attendance calculations
- [ ] Test leave application (employee only)
- [ ] Verify interns cannot apply leave
- [ ] Test expense submission
- [ ] Check salary calculations
- [ ] Verify audit logs record all actions

### 6. PWA Installation Testing
- [ ] Open app on Android Chrome
- [ ] Test "Add to Home Screen"
- [ ] Verify app launches in standalone mode
- [ ] Test camera in app mode
- [ ] Test offline functionality
- [ ] Repeat on iPhone Safari

### 7. Performance & Optimization
- [ ] Check page load time (target: <2s)
- [ ] Verify CSS/JS are properly cached
- [ ] Test database query performance
- [ ] Monitor server error logs
- [ ] Check PHP error.log for warnings

### 8. Security Audit
- [ ] Test SQL injection attempts (should fail)
- [ ] Verify gallery image upload is blocked
- [ ] Test unauthorized access to admin panel
- [ ] Verify sessions timeout properly
- [ ] Test CSRF protection
- [ ] Check that sensitive files are not accessible

### 9. Data Integrity Tests
- [ ] Create sample attendance records
- [ ] Verify day count calculations (all cases)
- [ ] Test late recovery rule
- [ ] Test auto punch-out
- [ ] Test site visit override
- [ ] Verify leave blocks punch-in
- [ ] Test expense → salary integration
- [ ] Verify month-lock handling

### 10. Browser Compatibility
- [ ] Chrome (Android)
- [ ] Chrome (iOS)
- [ ] Safari (iOS)
- [ ] Firefox (Android)
- [ ] Samsung Internet
- [ ] Opera
- [ ] Desktop Chrome
- [ ] Desktop Safari
- [ ] Desktop Firefox

### 11. Post-Deployment Tasks
- [ ] Change all default user PINs
- [ ] Add real users to database
- [ ] Update office geofence with real coordinates
- [ ] Update company details
- [ ] Set up automated backups (Hostinger Control Panel)
- [ ] Monitor error logs for first week
- [ ] Train admins on system usage
- [ ] Train employees on punch-in process

### 12. Documentation & Handover
- [ ] Admin has access to Admin Training Guide
- [ ] Employees have access to Employee Onboarding Guide
- [ ] Database credentials stored securely
- [ ] Backup schedule documented
- [ ] Support contact information shared
- [ ] Emergency procedures documented

## Production Checklist

### Security
- [ ] HTTPS enabled and enforced
- [ ] display_errors = OFF in PHP
- [ ] error_log configured
- [ ] .htaccess protecting sensitive files
- [ ] Regular backups scheduled
- [ ] Access logs monitored

### Performance
- [ ] Database indexes verified
- [ ] Queries optimized
- [ ] Asset caching enabled
- [ ] Gzip compression active
- [ ] Load times acceptable

### Maintenance
- [ ] Error monitoring in place
- [ ] Backup restoration tested
- [ ] Update procedures documented
- [ ] Escalation contacts defined
- [ ] SLA established

## Rollback Plan

If major issues occur:
1. Restore from backup via Hostinger Control Panel
2. OR restore database: `mysql -u user -p dbname < backup.sql`
3. OR restore files via FTP from backup copy
4. Notify users of outage

## Support Contacts

- **Hostinger Support**: [Control Panel Chat]
- **Emergency**: [Your emergency contact]
- **Database Issues**: [DBA if available]

## Sign-Off

- [ ] Deployment Lead: _________________ Date: _________
- [ ] QA Lead: _________________ Date: _________
- [ ] Admin Approval: _________________ Date: _________

---

**System Version**: 1.0.0
**Deployment Date**: ___________
**Deployment Status**: [ ] Complete [ ] In Progress [ ] Deferred
