# EasySave - Docker Setup Guide

This guide covers setting up EasySave with Docker for local development and Railway deployment.

## Table of Contents
1. [Local Development with Docker Compose](#local-development)
2. [Railway Deployment](#railway-deployment)
3. [Troubleshooting](#troubleshooting)
4. [File Structure](#file-structure)

---

## Local Development

### Prerequisites
- Docker Desktop installed and running
- Docker Compose (usually included with Docker Desktop)
- Git

### Quick Start

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd EasySave
   ```

2. **Copy environment configuration:**
   ```bash
   cp .env.docker .env
   ```

3. **Build and start containers:**
   ```bash
   docker compose up --build
   ```

4. **Access the application:**
   - **Web Application**: http://localhost:8080
   - **phpMyAdmin**: http://localhost:8081 (user: `easysave`, password: `easysave_pass`)
   - **MailHog** (Email Testing): http://localhost:8025

### Container Services

| Service | Purpose | Port | Host Access |
|---------|---------|------|-------------|
| `web` | Nginx web server | 80 | 8080 |
| `php` | PHP-FPM application | 9000 | Internal |
| `db` | MySQL database | 3306 | 3306 |
| `phpmyadmin` | Database management UI | 80 | 8081 |
| `mailhog` | Email testing | 1025, 8025 | 1025, 8025 |

### Common Commands

```bash
# Start containers in background
docker compose up -d

# Stop containers
docker compose down

# Stop and remove volumes (full cleanup)
docker compose down -v

# View logs
docker compose logs -f php
docker compose logs -f web

# Execute commands in PHP container
docker compose exec php php bin/console cache:clear --env=dev
docker compose exec php php bin/console doctrine:migrations:migrate

# Connect to database
docker compose exec db mysql -u easysave -peasysave_pass easysave

# Rebuild specific service
docker compose up --build php
```

### Database Management

**Access via phpMyAdmin:**
- URL: http://localhost:8081
- User: `easysave`
- Password: `easysave_pass`
- Server: `db`

**Command line:**
```bash
docker compose exec db mysql -u easysave -peasysave_pass easysave

# Useful SQL commands
SHOW TABLES;
SELECT * FROM user;
DESCRIBE user;
```

### Email Testing with MailHog

MailHog catches all outgoing emails in development mode.

1. Open http://localhost:8025
2. Check sent emails without actually sending them
3. Useful for testing verification emails, password resets, etc.

### PHP Code Debugging

Xdebug is configured in the development container:
- Host: Your machine
- Port: 9003

**VS Code Setup:**
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for XDebug",
            "type": "php",
            "port": 9003,
            "pathMapping": {
                "/app": "${workspaceRoot}"
            }
        }
    ]
}
```

---

## Railway Deployment

### Prerequisites
- Railway account (https://railway.app)
- GitHub repository connected to Railway
- Environment variables configured in Railway

### Step 1: Repository Setup

Ensure your GitHub repository contains:
```
Dockerfile              # Main production image
docker-compose.yaml    # Local dev setup
.dockerignore         # Build optimization
entrypoint.sh         # Container startup script
docker/               # Configuration files
```

### Step 2: Create Railway Services

1. **Go to Railway Dashboard**: https://railway.app/dashboard
2. **Create New Project**
3. **Add Services**:

#### Service 1: Web (Docker)
- **Service Type**: Docker
- **Dockerfile**: `Dockerfile` (from repository root)
- **Port**: 80 (expose this)
- **Build Command**: `docker build -f Dockerfile -t app .`

#### Service 2: MySQL Database
- **Service Type**: MySQL
- **Version**: 8.0+
- Leave as default (Railway manages backups, security)

### Step 3: Configure Environment Variables

In Railway Dashboard, set these variables for the Web service:

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<your-secret-key>
DATABASE_URL=<railway-provided-mysql-url>
MAILER_DSN=brevo+api://YOUR_API_KEY@default
MAILER_FROM=noreply@yourdomain.com
RUN_MIGRATIONS=1
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
```

**Important Notes:**
- `APP_SECRET`: Generate a secure random string (minimum 32 characters)
- `DATABASE_URL`: Railway provides this automatically if you connect the MySQL service
- `MAILER_DSN`: Set up email service (Brevo, SendGrid, etc.)

### Step 4: Deploy

1. **Push to GitHub**:
   ```bash
   git add .
   git commit -m "Docker setup for Railway"
   git push origin main
   ```

2. **Railway Auto-Deploy**:
   - Railway automatically deploys when you push to your connected branch
   - Check deployment status in Railway Dashboard

3. **Verify**:
   - Check Railway logs for any errors
   - Visit your Railway domain (auto-generated URL)
   - Run migrations if needed

### Step 5: Database Migrations (One-time)

First deployment should run migrations automatically (RUN_MIGRATIONS=1).

If needed manually:
```bash
# Via Railway CLI
railway run php bin/console doctrine:migrations:migrate

# Or in Railway Dashboard, create a Worker for one-time job
```

---

## Troubleshooting

### Docker Issues

**Container fails to start:**
```bash
# Check logs
docker compose logs -f php
docker compose logs -f web

# Rebuild everything
docker compose down -v
docker compose up --build
```

**Database connection error:**
```bash
# Verify database is running
docker compose ps

# Check database logs
docker compose logs db

# Ensure DATABASE_URL is correct for Docker:
DATABASE_URL=mysql://easysave:easysave_pass@db:3306/easysave
```

**Permission issues:**
```bash
# Fix file permissions
docker compose exec php chmod -R 755 var/cache var/log public
docker compose exec php chown -R www-data:www-data var public
```

### Railway Issues

**Deployment fails:**
1. Check Railway logs in Dashboard
2. Verify all environment variables are set
3. Ensure Dockerfile exists in repository root
4. Check if Docker build is working locally

**Application crashes on Railway:**
1. Check DATABASE_URL is correct
2. Verify all required environment variables are set
3. Check app logs in Railway Dashboard
4. Try running migrations manually

**Database connection issues:**
1. Use the DATABASE_URL provided by Railway
2. Don't use localhost - use the Railway-provided hostname
3. Verify database credentials in environment

### Common Errors

**"Unable to connect to database":**
- Local: Check MySQL container is running (`docker compose ps`)
- Railway: Verify DATABASE_URL in environment variables

**"Migrations failed":**
```bash
# Check current migration state
docker compose exec php php bin/console doctrine:migrations:status

# Reset migrations (development only!)
docker compose exec php php bin/console doctrine:schema:drop --force
docker compose exec php php bin/console doctrine:migrations:migrate
```

**"File permissions denied":**
```bash
# Fix permissions
docker compose exec php chmod -R 755 var/
docker compose exec php chown -R www-data:www-data var/ public/
```

---

## File Structure

```
EasySave/
├── Dockerfile              # Production image for Railway
├── Dockerfile.dev          # Development image with debugging
├── docker-compose.yaml     # Local development stack
├── entrypoint.sh          # Container startup script
├── .dockerignore           # Files to exclude from Docker builds
├── .env                    # Environment variables (gitignored)
├── .env.docker            # Docker environment template
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf      # Main Nginx configuration
│   │   ├── default.conf    # Site configuration
│   │   └── nginx-main.conf # Additional configuration
│   ├── php/
│   │   ├── php.ini         # PHP settings
│   │   └── php-fpm.conf    # PHP-FPM configuration
│   ├── mysql/
│   │   └── init.sql        # Database initialization
│   └── supervisor/
│       └── supervisord.conf # Process manager config
├── src/                    # Symfony source code
├── public/                 # Web root
├── config/                 # Symfony configuration
├── templates/              # Twig templates
└── var/                    # Cache, logs, uploaded files
```

---

## Development Workflow

### Day-to-day Development

```bash
# Start development environment
docker compose up -d

# Watch logs in real-time
docker compose logs -f

# Run Symfony console commands
docker compose exec php php bin/console <command>

# Clear cache
docker compose exec php php bin/console cache:clear --env=dev

# Run tests
docker compose exec php php bin/phpunit

# Stop when done
docker compose down
```

### Creating a Migration

```bash
docker compose exec php php bin/console make:migration
docker compose exec php php bin/console doctrine:migrations:migrate
```

### Loading Fixtures

```bash
docker compose exec php php bin/console doctrine:fixtures:load
```

---

## Performance Optimization

For production on Railway, consider:

1. **Enable OPCache**: Already configured in `docker/php/php.ini`
2. **Use Redis for cache/sessions**: Add Redis service to docker-compose.yaml
3. **Set up CDN**: For static assets
4. **Database indexing**: Optimize queries in your code
5. **Monitor performance**: Use Railway's built-in monitoring

---

## Support & Resources

- **Docker Documentation**: https://docs.docker.com
- **Railway Docs**: https://docs.railway.app
- **Symfony Docker Guide**: https://symfony.com/doc/current/setup/docker.html
- **Nginx Documentation**: https://nginx.org/en/docs/

---

For questions or issues, please refer to the project's issue tracker or contact the development team.
