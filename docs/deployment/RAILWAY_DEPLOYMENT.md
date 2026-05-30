# Railway Deployment Guide for EasySave

Complete guide for deploying EasySave to Railway.

## Pre-Deployment Checklist

- [ ] GitHub repository created and updated
- [ ] All files committed (Dockerfile, docker-compose.yaml, .dockerignore, etc.)
- [ ] Railway account created
- [ ] GitHub connected to Railway
- [ ] Domain/custom URL prepared (optional)

## Step 1: Initial Railway Setup

### 1.1 Create Railway Account
- Visit: https://railway.app
- Sign up with GitHub
- Authorize Railway to access your repositories

### 1.2 Create New Project
1. Click "New Project" in Railway Dashboard
2. Select "GitHub Repo"
3. Choose the EasySave repository
4. Select the branch to deploy (e.g., `main`)

## Step 2: Service Configuration

### 2.1 Add Web Service (Docker)

1. **Click "Add Service" → "Docker"**
2. **Configure:**
   - **Name**: `web` or `easysave-web`
   - **Dockerfile**: `Dockerfile` (from repo root)
   - **Build Context**: `/`
   - **Port**: `80`

3. **Expose Port:**
   - Click service → "Deploy"
   - Set public port to port 80
   - Generate/copy public URL

### 2.2 Add MySQL Database Service

1. **Click "Add Service" → "MySQL"**
2. **Configure:**
   - **Version**: 8.0 (or latest)
   - **Database Name**: `easysave`
   - Leave other settings as default
3. **Note the connection URL** (Railway auto-generates it)

### 2.3 Connect Services

1. Right-click Web service → "Connect"
2. Select MySQL service
3. Confirm connection

## Step 3: Environment Variables

### 3.1 Set Variables for Web Service

Navigate to Web service → Variables → Add the following:

| Variable | Value | Notes |
|----------|-------|-------|
| `APP_ENV` | `prod` | Production environment |
| `APP_DEBUG` | `0` | Disable debug mode |
| `APP_SECRET` | (generate) | Must be 32+ characters |
| `DATABASE_URL` | (auto-provided) | Set by Railway when MySQL is connected |
| `RUN_MIGRATIONS` | `1` | Auto-run migrations on startup |
| `MAILER_DSN` | `brevo+api://KEY@default` | See email setup below |
| `MAILER_FROM` | `noreply@yourdomain.com` | Your domain |
| `GOOGLE_CLIENT_ID` | (from Google Cloud) | OAuth configuration |
| `GOOGLE_CLIENT_SECRET` | (from Google Cloud) | OAuth configuration |
| `JWT_PASSPHRASE` | (same as local) | JWT authentication |
| `CORS_ALLOW_ORIGIN` | (your domain) | CORS settings |

### 3.2 Generate APP_SECRET

```bash
# Using Symfony
composer require symfony/dotenv
php bin/console generate:secret

# Or generate manually
openssl rand -hex 32
```

### 3.3 Configure Email Service

**Option 1: Brevo (Recommended)**
1. Create account: https://www.brevo.com
2. Get API key
3. Set: `MAILER_DSN=brevo+api://YOUR_API_KEY@default`

**Option 2: SendGrid**
1. Create account: https://sendgrid.com
2. Get API key
3. Set: `MAILER_DSN=sendgrid+smtp://apikey:SG.YOUR_KEY@default`

**Option 3: Gmail (Not recommended for production)**
```
MAILER_DSN=gmail+smtp://YOUR_EMAIL:YOUR_APP_PASSWORD@default
```

### 3.4 Google OAuth Setup

1. Go to: https://console.cloud.google.com
2. Create new project
3. Enable Google+ API
4. Create OAuth 2.0 credentials (Web Application)
5. Add authorized redirect URIs:
   - `https://YOUR_RAILWAY_DOMAIN/auth/google/callback`
   - `http://localhost:8080/auth/google/callback` (for local testing)
6. Copy Client ID and Secret to Railway variables

## Step 4: Deploy

### 4.1 Automatic Deployment

Push to your repository:
```bash
git add .
git commit -m "Docker setup and Railway deployment"
git push origin main
```

Railway automatically:
1. Detects changes
2. Builds Docker image
3. Runs migrations (if RUN_MIGRATIONS=1)
4. Deploys new version
5. Restarts services

### 4.2 Monitor Deployment

1. Open Railway Dashboard
2. Go to Web service → "Deployments"
3. Watch deployment progress
4. Check logs if errors occur

```bash
# Or use Railway CLI
railway logs
```

### 4.3 Verify Deployment

```bash
# Check if app is running
curl https://YOUR_RAILWAY_URL/health

# Check database connection
curl https://YOUR_RAILWAY_URL/api/verification-status
# (requires authentication)
```

## Step 5: Post-Deployment

### 5.1 Database Setup (First Time Only)

Migrations run automatically with `RUN_MIGRATIONS=1`.

Verify:
```bash
railway run php bin/console doctrine:migrations:status
```

### 5.2 Create Initial Admin User

```bash
# Via Railway CLI
railway shell
php bin/console app:create-admin

# Or manually via phpMyAdmin if Railway has a remote DB tool
```

### 5.3 Set Up Custom Domain

1. Buy domain from registrar (Namecheap, GoDaddy, etc.)
2. In Railway Dashboard:
   - Go to Web service → "Settings"
   - Add custom domain
   - Follow DNS configuration instructions
3. DNS typically takes 24 hours to propagate

### 5.4 Enable HTTPS

1. Railway provides free HTTPS automatically
2. Update `CORS_ALLOW_ORIGIN` to use `https://`
3. Update `DEFAULT_URI` to use `https://`
4. Update Google OAuth redirect URIs to use `https://`

## Step 6: Monitoring & Maintenance

### 6.1 Logs

View in Railway Dashboard:
- Web service → "Logs"
- MySQL service → "Logs"

Real-time:
```bash
railway logs -f
```

### 6.2 Database Backups

Railway automatically backs up MySQL data.
Configure in MySQL service settings if needed.

### 6.3 Performance Monitoring

1. Railway Dashboard shows:
   - CPU usage
   - Memory usage
   - Request latency
   - Error rates

2. Optimize if needed:
   - Increase instance size
   - Scale horizontally
   - Add caching layer

## Troubleshooting

### Build Fails

**Error: "Dockerfile not found"**
- Ensure `Dockerfile` exists in repository root
- Commit and push changes
- Trigger redeploy

**Error: "Docker build failed"**
- Check build logs in Railway Dashboard
- Common issues:
  - Missing dependencies in Dockerfile
  - PHP extensions not installed
  - Permissions issues

### App Crashes

**Error: "Database connection failed"**
```
Check:
1. DATABASE_URL is set in environment
2. Format is correct: mysql://user:pass@host:port/db
3. MySQL service is connected and running
```

**Error: "Migrations failed"**
```bash
# Check migration status
railway run php bin/console doctrine:migrations:status

# Check MySQL is accessible
railway run php bin/console doctrine:database:create --if-not-exists
```

**Error: "Email service failed"**
- Verify `MAILER_DSN` is correct
- Check email service credentials
- Ensure email service is running

### High Memory/CPU Usage

1. Check slow queries:
   ```bash
   railway run php bin/console doctrine:query:sql "SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST;"
   ```

2. Optimize:
   - Add database indexes
   - Cache expensive queries
   - Scale up instance size
   - Add Redis for caching

## Advanced Configuration

### Custom Domain with SSL

1. Set `https://yourdomain.com` in Railway settings
2. Add DNS records as instructed
3. Wait for propagation
4. Update app configuration:
   ```env
   DEFAULT_URI=https://yourdomain.com
   CORS_ALLOW_ORIGIN=^https://yourdomain\.com$
   ```

### Redis Cache Layer

Add Redis service:
1. "Add Service" → "Redis"
2. Set `REDIS_URL` in environment
3. Configure Symfony to use Redis

### Load Balancing

For high traffic:
1. Scale Web service (Railway handles load balancing)
2. Increase instance count or size
3. Monitor performance metrics

## Rollback

If deployment fails:

1. **Automatic rollback** (optional):
   - Railway can auto-rollback failed deployments

2. **Manual rollback**:
   - Go to Deployments
   - Click previous working deployment
   - Click "Redeploy"

## Support

- Railway Docs: https://docs.railway.app
- Railway Community: https://railway.app/community
- GitHub Issues: Create issue in repository
- Email Support: Railway dashboard → Support

---

## Deployment Summary

| Task | Status | Notes |
|------|--------|-------|
| Repository setup | ⬜ | Ensure all Docker files committed |
| Railway project creation | ⬜ | Create new project in Dashboard |
| Web service setup | ⬜ | Configure Docker service |
| MySQL setup | ⬜ | Add MySQL database service |
| Environment variables | ⬜ | Set all required variables |
| Initial deployment | ⬜ | Push to trigger build |
| Verify app running | ⬜ | Check public URL |
| Domain configuration | ⬜ | Optional - set custom domain |
| Email service setup | ⬜ | Configure Brevo/SendGrid |
| Google OAuth setup | ⬜ | Set credentials |
| Monitor deployment | ⬜ | Check logs and metrics |

Complete each step before proceeding to the next.
