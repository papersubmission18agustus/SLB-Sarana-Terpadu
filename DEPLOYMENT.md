# 🚀 Deployment Guide - Smart Learning Down Syndrome

## Backend Deployment ke Railway

### Step 1: Setup Railway Account
1. Buka https://railway.app
2. Sign up dengan GitHub account
3. Authorize Railway untuk akses GitHub

### Step 2: Create Backend Project di Railway
1. Di Railway Dashboard → **New Project**
2. Pilih **Deploy from GitHub repo**
3. Select repository: `SLB-Sarana-Terpadu`
4. Railway akan auto-detect Procfile ✅

### Step 3: Setup Database di Railway
1. Di Railway Dashboard → **New Project**
2. Pilih **MySQL** atau **PostgreSQL**
3. Akan auto-generate database credentials
4. Copy credentials ini untuk step berikutnya

### Step 4: Connect Backend dengan Database
1. Buka Backend Project di Railway
2. Pilih tab **Variables**
3. Add environment variables:
   ```
   APP_KEY=base64:kJ6xCcwGSgfvSd+eXlUbGoFT063m3RHVi4nT6Rpkl6Q=
   APP_DEBUG=false
   APP_URL=https://<your-railway-url>
   DATABASE_URL=mysql.<generated-host>
   DATABASE_NAME=railway
   DATABASE_USER=root
   DATABASE_PASSWORD=<generated-password>
   DB_HOST=<mysql-host>
   DB_PORT=3306
   ```

### Step 5: Run Migrations
Railway akan otomatis run migrations saat deploy karena sudah di-set di `railway.json`:
```json
"startCommand": "cd backend && php artisan migrate --force && vendor/bin/heroku-php-apache2 public/"
```

### Step 6: Get Backend URL
Setelah deploy selesai, copy URL dari Railway:
```
Contoh: https://smart-learning-production.railway.app
```

---

## Frontend Deployment ke Netlify

### Step 1: Setup Netlify Account
1. Buka https://netlify.com
2. Sign up dengan GitHub

### Step 2: Deploy Frontend ke Netlify
1. Di Netlify Dashboard → **Add new site**
2. Pilih **Import an existing project**
3. Authorize GitHub
4. Select repository: `SLB-Sarana-Terpadu`
5. Atur Build settings:
   - **Base directory**: `frontend`
   - **Build command**: `npm run build`
   - **Publish directory**: `frontend/dist`

### Step 3: Set Environment Variables
1. Di Netlify Project → Settings → Build & deploy → Environment
2. Add variable:
   ```
   VITE_API_BASE_URL=https://smart-learning-production.railway.app
   ```

### Step 4: Deploy
Netlify akan automatically deploy setiap kali push ke `main` branch

Frontend URL akan seperti:
```
https://smart-learning.netlify.app
```

---

## Update .env Files

Setelah tahu URLs dari Railway & Netlify, update:

### Backend (.env.production)
```env
APP_URL=https://smart-learning-production.railway.app
FRONTEND_URL=https://smart-learning.netlify.app
DB_HOST=<mysql-host-from-railway>
DB_PASSWORD=<password-from-railway>
```

### Frontend (.env.production)
```env
VITE_API_BASE_URL=https://smart-learning-production.railway.app
```

---

## Database Migration & Seeding

Railway akan auto-run migrations via `railway.json` start command.

Untuk seed data initial (optional), tambah di `Procfile`:
```
web: cd backend && php artisan migrate --force && php artisan db:seed --force && vendor/bin/heroku-php-apache2 public/
```

---

## Testing di Production

Setelah deploy selesai, test:

```bash
# Test backend API
curl https://smart-learning-production.railway.app/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"Admin12345!"}'

# Test frontend
Buka https://smart-learning.netlify.app di browser
```

---

## Monitoring & Logs

### Railway
1. Project → Deployments → Logs
2. Check error logs jika ada issue

### Netlify
1. Site → Deploys → Logs
2. Check build logs jika ada issue

---

## Tips Production

✅ Keep `.env.production` secrets di Railway/Netlify, jangan commit ke GitHub
✅ Enable auto-deployment dari GitHub (sudah default)
✅ Setup custom domain di Railway & Netlify settings
✅ Enable HTTPS (sudah auto di Railway & Netlify)
✅ Backup database secara regular
✅ Monitor performance & errors

---

## Next Steps

1. Setup Railway account & create projects
2. Update backend .env.production dengan Railway credentials
3. Deploy frontend ke Netlify
4. Update CORS config di backend untuk allow Netlify domain
5. Test integration antara frontend & backend
6. Setup custom domains (optional)

**Need help? Check Railway & Netlify documentation atau ask me!** 🎯
