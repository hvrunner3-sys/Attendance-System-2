# Quick Start Guide

## 60-Second Setup

### Option 1: Manual Database Setup

1. **Upload Files to Hostinger**
   - All 12 files to public_html/

2. **Create Database**
   - Hostinger Control Panel → Databases → Create Database
   - Note: database name, user, password

3. **Edit config.php**
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_user');
   define('DB_PASS', 'your_pass');
   define('DB_NAME', 'your_db');
   ```

4. **Import Database Schema**
   - Hostinger Control Panel → phpMyAdmin
   - Select your database
   - Import → Choose setup.sql
   - Click Import

5. **Done!**
   - Open https://yourdomain.com
   - Login: admin@company.com / 1234

---

## First Login Credentials

| User | Email | PIN |
|------|-------|-----|
| Admin | admin@company.com | 1234 |
| Employee | john@company.com | 1111 |
| Intern | intern@company.com | 2222 |

**⚠️ Change these PINs immediately!**

---

## What to Configure

Edit `config.php`:

```php
// Your office location (coordinates & radius)
define('OFFICE_LAT', 28.6139);      // Your latitude
define('OFFICE_LNG', 77.2090);      // Your longitude
define('OFFICE_RADIUS', 100);       // Radius in meters

// Office hours
define('OFFICE_PUNCH_IN', '10:00'); // Punch in time
define('OFFICE_PUNCH_OUT', '18:00'); // Punch out time
```

---

## Basic Workflows

### Admin
1. Login
2. View pending approvals
3. Approve leaves/expenses
4. View team attendance
5. Check payroll

### Employee
1. Login
2. Click "Punch In"
3. Select location (Office/WFH/Site Visit)
4. Take live photo
5. Punch out later with work summary
6. View attendance history
7. Apply for leave
8. Submit expenses

### Intern
- Same as Employee
- But: Cannot apply leave
- Stipend: Hourly or Fixed (admin sets)

---

## Key Features

✅ **Attendance**
- Live photo + GPS verification
- Office geofence validation
- Auto punch-out at 11:59 PM
- Intelligent day counting (Full/Half/0 Day)

✅ **Leave**
- 2 Sick + 2 Casual per quarter
- Admin approval required
- Approved leave blocks punch-in

✅ **Expenses**
- Submit with optional receipt
- Admin approval
- Added to salary

✅ **Payroll**
- Automatic calculation
- Employee vs Intern handling
- Monthly report generation

✅ **Mobile App**
- Install like native app (PWA)
- Works offline
- Full-screen camera
- Mobile-first design

---

## File Breakdown

| File | Lines | Purpose |
|------|-------|---------|
| config.php | ~150 | All settings in one place |
| auth.php | ~200 | Login & sessions |
| db.php | ~600 | Database & business logic |
| index.php | ~1000 | Main UI & routing |
| api.php | ~400 | AJAX endpoints |
| app.js | ~500 | Client-side logic & camera |
| style.css | ~800 | Mobile-first responsive design |
| service-worker.js | ~100 | PWA offline support |

**Total: ~3,800 lines of production code**

---

## Common Issues & Fixes

### "Database connection failed"
→ Check config.php DB credentials
→ Verify database exists in MySQL

### "Camera not working"
→ Must use HTTPS
→ Allow camera permission in browser
→ Check if camera hardware available

### "Uploads not working"
→ Create uploads/ folder with 755 permissions
→ Check .htaccess is uploaded

### "Offline not working"
→ Clear browser cache
→ Reinstall PWA app
→ Check service worker in DevTools

---

## Verification Checklist

After setup:
- [ ] Can login with admin credentials
- [ ] Dashboard loads without errors
- [ ] Camera works (take photo)
- [ ] Punch-in records successfully
- [ ] Attendance shows in history
- [ ] Admin panel accessible
- [ ] App installable on mobile

---

## Next Steps

1. **Customize**
   - Change office location
   - Add users
   - Adjust office hours

2. **Train Users**
   - Admin: Approval workflow
   - Employees: Punch-in process
   - Interns: Attendance requirements

3. **Monitor**
   - Check error logs
   - Review audit trail
   - Verify calculations

4. **Maintain**
   - Weekly backup check
   - Monthly user audit
   - Quarterly leave allocation

---

## Support Resources

- **README.md** - Complete documentation
- **DEPLOYMENT_CHECKLIST.md** - Full deployment guide
- **Code Comments** - Inline documentation
- **Hostinger Help** - Server issues

---

Need more help? Check README.md or DEPLOYMENT_CHECKLIST.md for detailed information.
