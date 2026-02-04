# Attendance & Payroll System

Production-ready attendance, leave, payroll, and expense management system for Hostinger shared hosting.

## Features

✅ **Attendance Management**
- Office, WFH, Site Visit slots
- Live photo + GPS location capture
- Geofence validation for office
- Auto punch-out at 11:59 PM
- Intelligent day calculation (Full/Half/0 Day)

✅ **Leave Management**
- Sick & Casual leave with quarterly allocation
- Approved leave blocks punch-in
- Admin approval workflow

✅ **Expense Management**
- Submit expenses with optional receipt
- Admin approval & salary integration
- Month-lock handling

✅ **Payroll System**
- Automatic salary calculation
- Employee vs Intern handling
- Hourly & Fixed stipend support
- Expense & incentive integration

✅ **Admin Panel**
- Leave & Expense approvals
- Team attendance overview
- Payroll management
- Audit logs

✅ **Mobile App Features**
- PWA (Progressive Web App) - Install like native app
- Offline support
- Full-screen camera capture
- Mobile-first responsive design
- Touch-optimized interface

## System Requirements

- **PHP**: 7.4+ (with mysqli extension)
- **MySQL**: 5.7+ (or MariaDB 10.2+)
- **Server**: Apache with mod_rewrite enabled (standard on Hostinger)
- **Browser**: Chrome 51+, Safari 11+, Firefox 55+ (for PWA)

## Installation on Hostinger

### Step 1: Upload Files

1. Download all project files to your computer
2. Connect to Hostinger via **File Manager** or **FTP**
3. Upload all files to your domain's root directory (public_html or www)
4. Ensure folder structure:
   ```
   public_html/
   ├── index.php
   ├── config.php
   ├── auth.php
   ├── db.php
   ├── api.php
   ├── style.css
   ├── app.js
   ├── manifest.json
   ├── service-worker.js
   ├── .htaccess
   ├── setup.sql
   └── uploads/
   ```

### Step 2: Create Database

1. Go to Hostinger **Control Panel** → **Databases** → **MySQL Databases**
2. Create new database: `attendance_system`
3. Create database user with full privileges
4. Note the database credentials

### Step 3: Configure Database Connection

1. Edit `config.php` and update:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'attendance_system');
   ```

2. Save the file

### Step 4: Initialize Database

1. Go to Hostinger **Control Panel** → **Databases** → **phpMyAdmin**
2. Select your database `attendance_system`
3. Go to **Import** tab
4. Upload `setup.sql`
5. Click **Import**

**OR** Import via command line:
```bash
mysql -h localhost -u your_db_user -p attendance_system < setup.sql
```

### Step 5: Set Permissions

1. Set folder permissions via FTP:
   ```
   uploads/ → 755 or 777
   ```

2. Verify `.htaccess` is in place (may be hidden file)

### Step 6: Test Installation

1. Open your domain: `https://yourdomain.com`
2. Login with default credentials:
   - **Email**: admin@company.com
   - **PIN**: 1234

## Default Users (After Setup)

| Email | PIN | Role | Notes |
|-------|-----|------|-------|
| admin@company.com | 1234 | Admin | Full access |
| john@company.com | 1111 | Employee | 50,000 monthly salary |
| intern@company.com | 2222 | Intern | 200/hour stipend |

**⚠️ Important**: Change these passwords immediately after first login!

## Configuration

Edit `config.php` to customize:

```php
// Office Location (Geofence)
define('OFFICE_LAT', 28.6139);
define('OFFICE_LNG', 77.2090);
define('OFFICE_RADIUS', 100); // meters

// Office Hours
define('OFFICE_PUNCH_IN', '10:00');
define('OFFICE_PUNCH_OUT', '18:00');
define('RECOVERY_TIME', '19:00'); // 7 PM

// Late Window
define('LATE_START', '10:00');
define('LATE_END', '10:15');

// Leave Allocation (per quarter)
define('SICK_LEAVE_QUARTERLY', 2);
define('CASUAL_LEAVE_QUARTERLY', 2);
```

## File Structure

| File | Purpose |
|------|---------|
| `config.php` | All configuration & constants |
| `auth.php` | Authentication & session management |
| `db.php` | Database & business logic |
| `index.php` | Main app (UI + routing) |
| `api.php` | AJAX API endpoints |
| `style.css` | All styling (mobile-first) |
| `app.js` | Client-side logic & camera |
| `manifest.json` | PWA configuration |
| `service-worker.js` | Offline support |
| `.htaccess` | Security & server config |
| `setup.sql` | Database initialization |

## Business Logic Summary

### Attendance Day Calculation

**Priority Order:**
1. Site Visit → Always Full Day
2. Auto Punch-Out (Office/WFH) → 0 Day
3. Late Recovery (10:00-10:15 + 7:00 PM) → Full Day
4. Hour-based: <4hrs=0, 4-8hrs=Half, ≥8hrs=Full

### Salary Calculation

**Employee:**
- Base = Monthly ÷ 30
- Salary = (Attendance Days × Per Day) + (Leave Days × Per Day) + Expenses

**Intern:**
- Hourly: Paid by actual hours worked
- Fixed: Flat monthly amount

### Leave Rules

- 2 Sick + 2 Casual per quarter (employee only)
- Valid only after admin approval
- Approved leave blocks punch-in

## Database Schema

### users
- Basic user info, roles, salary/stipend config

### attendance
- Punch in/out records
- Geolocation data
- Photos
- Day count calculation

### leaves
- Leave applications
- Status tracking
- Approval workflow

### expenses
- Expense submissions
- Receipt images
- Approval & payment tracking

### payroll
- Monthly salary calculations
- Payment records
- Audit trail

### audit_logs
- All system actions
- IP tracking
- Compliance logging

## Security Features

✅ **Authentication**
- Session-based login
- PIN protection
- Secure password hashing

✅ **Data Protection**
- Prepared statements (SQL injection prevention)
- HTTPS enforcement
- File upload validation
- Directory access restrictions

✅ **Privacy**
- Row-level access control
- User data isolation
- Audit logging

✅ **Infrastructure**
- No external dependencies
- Self-hosted database
- No third-party APIs

## Maintenance

### Regular Tasks

1. **Weekly**: Review audit logs for suspicious activity
2. **Monthly**: Backup database & uploads folder
3. **Quarterly**: Check leave allocations
4. **Yearly**: Update PHP if needed, audit users

### Backup Instructions

**Via Hostinger Control Panel:**
1. Dashboard → Backups
2. Create manual backup
3. Download if needed

**Via Command Line:**
```bash
# Backup database
mysqldump -u user -p attendance_system > backup.sql

# Backup files
tar -czf backup.tar.gz public_html/
```

### Restore Instructions

```bash
# Restore database
mysql -u user -p attendance_system < backup.sql

# Upload files via FTP
```

## Troubleshooting

### "Database connection failed"
- Verify DB credentials in `config.php`
- Check if MySQL service is running
- Verify database name is correct

### Camera not working
- Ensure HTTPS is enabled
- Check browser permissions
- Verify camera hardware is connected

### Offline issues
- Clear browser cache
- Reinstall PWA app
- Check service worker in DevTools

### File permissions errors
- Set `uploads/` to 755
- Verify .htaccess is present
- Contact Hostinger support if issues persist

## Support & Updates

For issues or updates:
1. Check error logs: `error.log` in root
2. Verify PHP/MySQL versions in Hostinger Control Panel
3. Contact Hostinger support for server issues
4. Review code comments for logic details

## Performance Optimization

✅ Already Optimized:
- Indexed database queries
- Lazy loading for images
- CSS/JS minified in single files
- Asset caching via service worker
- Gzip compression via .htaccess

✅ Optional Enhancements:
- Enable CDN in Hostinger for static assets
- Implement database query caching
- Use Redis for session storage (if available)

## License

This system is provided as-is for business use.

---

**Last Updated**: February 2026
**Version**: 1.0.0
**Hostinger Ready**: ✅ Yes
