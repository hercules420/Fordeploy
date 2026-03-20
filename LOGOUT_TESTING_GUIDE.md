# Logout Testing Guide

## Quick Test Steps

Test each user type to confirm logout works:

### 1. Farm Owner Logout Test
- ✅ **Normal state**: Login → navigate dashboard → click Logout → should redirect to login page
- ✅ **Expired subscription**: Logout button should STILL work (this was the bug!)
- ✅ **Approval revoked**: Logout button should STILL work
- 📍 Route: `POST /farm-owner/logout` → Redirect to `farmowner.login`

### 2. HR/Finance/Department Staff Logout Test
- ✅ Login as HR staff
- ✅ Navigate to HR dashboard
- ✅ Click Logout in sidebar
- ✅ Should redirect to login page
- 📍 Route: `POST /logout` → Redirect to `/login`

### 3. Super Admin Logout Test
- ✅ Login as Super Admin
- ✅ Navigate to super admin dashboard
- ✅ Click Logout in top right menu
- ✅ Should redirect to home page
- 📍 Route: `POST /logout` → Redirect to `/`

### 4. Consumer/Client Logout Test
- ✅ Login as Consumer/Client
- ✅ Click Logout in navigation
- ✅ Should redirect to login page
- 📍 Route: `POST /logout` → Redirect to `/login`

### 5. Mobile App Logout Test  
- ✅ Login on mobile app
- ✅ Navigate to profile
- ✅ Click Logout
- ✅ Should return to login screen
- 📍 API Route: `POST /api/mobile/auth/logout`

---

## Expected Behavior After Fix

| User Type | Logout Route | Middleware | Expected Redirect | Status |
|-----------|-------------|-----------|-------------------|--------|
| Farm Owner | `/farm-owner/logout` | `role:farm_owner` | `/farm-owner/login` | ✅ Fixed |
| HR/Finance | `/logout` | `auth` | `/login` | ✅ Works |
| Department Staff | `/logout` | `auth` | `/login` | ✅ Works |
| Super Admin | `/logout` | `auth` | `/` | ✅ Works |
| Consumer/Client | `/logout` | `auth` | `/login` | ✅ Works |
| Mobile User | `/api/mobile/auth/logout` | API token | App screen | ✅ Works |

---

## Verify Routes in Terminal

Run this command to see all logout routes:
```bash
php artisan route:list | grep logout
```

Should show 3 routes:
```
POST   api/mobile/auth/logout
POST   farm-owner/logout
POST   logout
```

---

## What Was Fixed

**Before**: Farm owner logout was blocked if subscription expired or approval was revoked (404 error)

**After**: Farm owner can logout anytime, regardless of subscription/approval status

The fix moves the logout endpoint OUTSIDE the restrictive middleware checks so users can always escape (logout) even in a "locked" state.
