# NIXI Application - Routes Documentation

This document contains all available routes in the application, organized by user type and functionality.

## Table of Contents
- [Public Routes](#public-routes)
- [User Routes](#user-routes)
- [Admin Routes](#admin-routes)
- [SuperAdmin Routes](#superadmin-routes)
- [Quick Access Links](#quick-access-links)

---

## Public Routes

### Home
- **URL:** `/`
- **Route Name:** `home`
- **Description:** Welcome/Home page
- **Access:** Public

### Registration
- **URL:** `/register`
- **Route Name:** `register.index`
- **Description:** User registration page
- **Access:** Public

- **URL:** `/register` (POST)
- **Route Name:** `register.store`
- **Description:** Submit registration form
- **Access:** Public

- **URL:** `/register/send-email-otp` (POST)
- **Route Name:** `register.send.email.otp`
- **Description:** Send OTP to email
- **Access:** Public

- **URL:** `/register/send-mobile-otp` (POST)
- **Route Name:** `register.send.mobile.otp`
- **Description:** Send OTP to mobile
- **Access:** Public

- **URL:** `/register/verify-email-otp` (POST)
- **Route Name:** `register.verify.email.otp`
- **Description:** Verify email OTP
- **Access:** Public

- **URL:** `/register/verify-mobile-otp` (POST)
- **Route Name:** `register.verify.mobile.otp`
- **Description:** Verify mobile OTP
- **Access:** Public

- **URL:** `/register/verify-pan` (POST)
- **Route Name:** `register.verify.pan`
- **Description:** Verify PAN card
- **Access:** Public

### User Login
- **URL:** `/login`
- **Route Name:** `login.index`
- **Description:** User login page
- **Access:** Public

- **URL:** `/login` (POST)
- **Route Name:** `login.submit`
- **Description:** Submit login credentials
- **Access:** Public

- **URL:** `/login/verify`
- **Route Name:** `login.verify`
- **Description:** OTP verification page
- **Access:** Public

- **URL:** `/login/verify` (POST)
- **Route Name:** `login.verify.otp`
- **Description:** Verify login OTP
- **Access:** Public

- **URL:** `/login/resend-otp` (POST)
- **Route Name:** `login.resend.otp`
- **Description:** Resend login OTP
- **Access:** Public

- **URL:** `/login/forgot-password`
- **Route Name:** `login.forgot-password`
- **Description:** Forgot password page
- **Access:** Public

- **URL:** `/login/forgot-password` (POST)
- **Route Name:** `login.forgot-password.submit`
- **Description:** Submit forgot password request
- **Access:** Public

---

## User Routes

**Note:** All user routes require authentication (`user.auth` middleware)

### Dashboard
- **URL:** `/user/dashboard`
- **Route Name:** `user.dashboard`
- **Description:** User dashboard
- **Access:** Authenticated Users

### Profile
- **URL:** `/user/profile`
- **Route Name:** `user.profile`
- **Description:** User profile page
- **Access:** Authenticated Users

### Messages
- **URL:** `/user/messages`
- **Route Name:** `user.messages.index`
- **Description:** View all messages
- **Access:** Authenticated Users

- **URL:** `/user/messages/{id}`
- **Route Name:** `user.messages.show`
- **Description:** View specific message
- **Access:** Authenticated Users

- **URL:** `/user/messages/{id}/mark-read` (POST)
- **Route Name:** `user.messages.mark-read`
- **Description:** Mark message as read
- **Access:** Authenticated Users

- **URL:** `/user/messages/unread/count`
- **Route Name:** `user.messages.unread.count`
- **Description:** Get unread messages count (AJAX)
- **Access:** Authenticated Users

### Profile Update Requests
- **URL:** `/user/profile-update/request`
- **Route Name:** `user.profile-update.request`
- **Description:** Request profile update page
- **Access:** Authenticated Users

- **URL:** `/user/profile-update/request` (POST)
- **Route Name:** `user.profile-update.store`
- **Description:** Submit profile update request
- **Access:** Authenticated Users

- **URL:** `/user/profile-update/edit`
- **Route Name:** `user.profile-update.edit`
- **Description:** Edit profile (after approval)
- **Access:** Authenticated Users

- **URL:** `/user/profile-update/update` (POST)
- **Route Name:** `user.profile-update.update`
- **Description:** Update profile
- **Access:** Authenticated Users

### Applications
- **URL:** `/user/applications`
- **Route Name:** `user.applications.index`
- **Description:** View all applications
- **Access:** Authenticated Users (Approved status only)

- **URL:** `/user/applications/{id}`
- **Route Name:** `user.applications.show`
- **Description:** View application details
- **Access:** Authenticated Users (Approved status only)

### Logout
- **URL:** `/login/logout` (POST)
- **Route Name:** `login.logout`
- **Description:** User logout
- **Access:** Authenticated Users

---

## Admin Routes

### Admin Login (Public)
- **URL:** `/admin/login`
- **Route Name:** `admin.login`
- **Description:** Admin login page
- **Access:** Public

- **URL:** `/admin/login` (POST)
- **Route Name:** `admin.login.submit`
- **Description:** Submit admin login credentials
- **Access:** Public

- **URL:** `/admin/login/verify`
- **Route Name:** `admin.login.verify`
- **Description:** Admin OTP verification page
- **Access:** Public

- **URL:** `/admin/login/verify` (POST)
- **Route Name:** `admin.login.verify.otp`
- **Description:** Verify admin login OTP
- **Access:** Public

- **URL:** `/admin/login/resend-otp` (POST)
- **Route Name:** `admin.login.resend-otp`
- **Description:** Resend admin login OTP
- **Access:** Public

### Admin Dashboard (Protected)
**Note:** All admin routes below require authentication (`admin` middleware)

- **URL:** `/admin/dashboard`
- **Route Name:** `admin.dashboard`
- **Description:** Admin dashboard
- **Access:** Authenticated Admins

### User Management
- **URL:** `/admin/users`
- **Route Name:** `admin.users`
- **Description:** View all users
- **Access:** Authenticated Admins

- **URL:** `/admin/users/{id}`
- **Route Name:** `admin.users.show`
- **Description:** View user details
- **Access:** Authenticated Admins

- **URL:** `/admin/users/{id}/send-message` (POST)
- **Route Name:** `admin.users.send-message`
- **Description:** Send message to user
- **Access:** Authenticated Admins

- **URL:** `/admin/users/{id}/update-status` (POST)
- **Route Name:** `admin.users.update-status`
- **Description:** Update user status
- **Access:** Authenticated Admins

### Profile Update Requests
- **URL:** `/admin/profile-updates/{id}/approve` (POST)
- **Route Name:** `admin.profile-updates.approve`
- **Description:** Approve profile update request
- **Access:** Authenticated Admins

- **URL:** `/admin/profile-updates/{id}/reject` (POST)
- **Route Name:** `admin.profile-updates.reject`
- **Description:** Reject profile update request
- **Access:** Authenticated Admins

### Application Management
**Note:** Application routes are role-based (Processor, Finance, Technical)

- **URL:** `/admin/applications`
- **Route Name:** `admin.applications`
- **Description:** View applications (role-based)
- **Access:** Authenticated Admins (Processor/Finance/Technical roles)

- **URL:** `/admin/applications/{id}`
- **Route Name:** `admin.applications.show`
- **Description:** View application details
- **Access:** Authenticated Admins (Role-based)

#### Processor Routes
- **URL:** `/admin/applications/{id}/approve-to-finance` (POST)
- **Route Name:** `admin.applications.approve-to-finance`
- **Description:** Approve application and forward to Finance
- **Access:** Authenticated Admins (Processor role)

#### Finance Routes
- **URL:** `/admin/applications/{id}/approve-to-technical` (POST)
- **Route Name:** `admin.applications.approve-to-technical`
- **Description:** Approve application and forward to Technical
- **Access:** Authenticated Admins (Finance role)

- **URL:** `/admin/applications/{id}/send-back-to-processor` (POST)
- **Route Name:** `admin.applications.send-back-to-processor`
- **Description:** Send application back to Processor
- **Access:** Authenticated Admins (Finance role)

#### Technical Routes
- **URL:** `/admin/applications/{id}/approve` (POST)
- **Route Name:** `admin.applications.approve`
- **Description:** Final approval of application
- **Access:** Authenticated Admins (Technical role)

- **URL:** `/admin/applications/{id}/send-back-to-finance` (POST)
- **Route Name:** `admin.applications.send-back-to-finance`
- **Description:** Send application back to Finance
- **Access:** Authenticated Admins (Technical role)

### Admin Logout
- **URL:** `/admin/logout` (POST)
- **Route Name:** `admin.logout`
- **Description:** Admin logout
- **Access:** Authenticated Admins

---

## SuperAdmin Routes

### SuperAdmin Login (Public)
- **URL:** `/superadmin/login`
- **Route Name:** `superadmin.login`
- **Description:** SuperAdmin login page
- **Access:** Public

- **URL:** `/superadmin/login` (POST)
- **Route Name:** `superadmin.login.submit`
- **Description:** Submit SuperAdmin login credentials
- **Access:** Public

- **URL:** `/superadmin/login/verify`
- **Route Name:** `superadmin.login.verify`
- **Description:** SuperAdmin OTP verification page
- **Access:** Public

- **URL:** `/superadmin/login/verify` (POST)
- **Route Name:** `superadmin.login.verify.otp`
- **Description:** Verify SuperAdmin login OTP
- **Access:** Public

- **URL:** `/superadmin/login/resend-otp` (POST)
- **Route Name:** `superadmin.login.resend-otp`
- **Description:** Resend SuperAdmin login OTP
- **Access:** Public

### SuperAdmin Dashboard (Protected)
**Note:** All SuperAdmin routes below require authentication (`superadmin` middleware)

- **URL:** `/superadmin/dashboard`
- **Route Name:** `superadmin.dashboard`
- **Description:** SuperAdmin dashboard
- **Access:** Authenticated SuperAdmins

### User Management
- **URL:** `/superadmin/users`
- **Route Name:** `superadmin.users`
- **Description:** View all users
- **Access:** Authenticated SuperAdmins

- **URL:** `/superadmin/users/{id}`
- **Route Name:** `superadmin.users.show`
- **Description:** View user details with full history
- **Access:** Authenticated SuperAdmins

### Admin Management
- **URL:** `/superadmin/admins`
- **Route Name:** `superadmin.admins`
- **Description:** View all admins
- **Access:** Authenticated SuperAdmins

- **URL:** `/superadmin/admins/create`
- **Route Name:** `superadmin.admins.create`
- **Description:** Create new admin page
- **Access:** Authenticated SuperAdmins

- **URL:** `/superadmin/admins` (POST)
- **Route Name:** `superadmin.admins.store`
- **Description:** Store new admin
- **Access:** Authenticated SuperAdmins

- **URL:** `/superadmin/admins/{id}/edit`
- **Route Name:** `superadmin.admins.edit`
- **Description:** Edit admin page
- **Access:** Authenticated SuperAdmins

- **URL:** `/superadmin/admins/{id}` (POST)
- **Route Name:** `superadmin.admins.update`
- **Description:** Update admin
- **Access:** Authenticated SuperAdmins

### SuperAdmin Logout
- **URL:** `/superadmin/logout` (POST)
- **Route Name:** `superadmin.logout`
- **Description:** SuperAdmin logout
- **Access:** Authenticated SuperAdmins

---

## Quick Access Links

### For Users
- **Register:** `/register`
- **Login:** `/login`
- **Dashboard:** `/user/dashboard`
- **Profile:** `/user/profile`
- **Messages:** `/user/messages`
- **Applications:** `/user/applications`

### For Admins
- **Login:** `/admin/login`
- **Dashboard:** `/admin/dashboard`
- **Users:** `/admin/users`
- **Applications:** `/admin/applications` (if role assigned)

### For SuperAdmins
- **Login:** `/superadmin/login`
- **Dashboard:** `/superadmin/dashboard`
- **Users:** `/superadmin/users`
- **Admins:** `/superadmin/admins`

---

## Application Workflow

### User Registration Flow
1. `/register` - Fill registration form
2. Verify Email OTP
3. Verify Mobile OTP
4. Verify PAN
5. Submit registration
6. Account status: `pending`

### User Login Flow
1. `/login` - Enter email and password
2. `/login/verify` - Enter OTP
3. `/user/dashboard` - Access dashboard

### Application Workflow
1. **User submits application** → Status: `pending`
2. **Processor reviews** → Can approve to Finance or keep pending
3. **Finance reviews** → Can approve to Technical or send back to Processor
4. **Technical reviews** → Can approve (final) or send back to Finance
5. **User receives notification** when application is approved

### Admin Roles
- **Processor:** First layer - manages users, verifies them, approves applications to Finance
- **Finance:** Second layer - reviews Processor-approved applications, forwards to Technical
- **Technical:** Final layer - final approval authority

---

## Notes

1. **OTP Verification:** 
   - OTPs are currently logged in `storage/logs/laravel.log`
   - Master OTP: `123456` (can be used for all OTP verifications)

2. **Password Requirements:**
   - Minimum 8 characters
   - Automatically hashed if stored as plain text

3. **Session Management:**
   - All authenticated routes check session
   - Logout clears all session data
   - Cache control headers prevent back navigation after logout

4. **Error Pages:**
   - 401: Unauthorized Access
   - 403: Forbidden
   - 404: Not Found
   - 419: Page Expired
   - 500: Internal Server Error
   - 503: Service Unavailable

5. **Database Connection Errors:**
   - Automatically handled and show 503 error page
   - All errors are logged in `storage/logs/laravel.log`

---

## Development Notes

- **Framework:** Laravel 11
- **Database:** MySQL
- **Timezone:** Asia/Kolkata (IST) - All timestamps are stored and displayed in IST
- **Password Hashing:** Bcrypt
- **Session Driver:** Database

### Timezone Configuration

The application is configured to use **Indian Standard Time (IST - Asia/Kolkata)** throughout:

- **Application Timezone:** Set in `config/app.php` to `'Asia/Kolkata'`
- **Database Timezone:** MySQL connection configured to use `+05:30` timezone
- **All Models:** Timestamp casts configured to display in IST
- **All Controllers:** Using `now('Asia/Kolkata')` for all timestamp operations
- **Existing Records:** Migration has been run to convert all existing UTC timestamps to IST

**Note:** All new records will automatically use IST. The migration `2025_11_10_132412_update_existing_timestamps_to_ist` has converted all existing timestamps from UTC to IST by adding 5 hours and 30 minutes.

---

**Last Updated:** November 2025
