# Docker Commands Quick Reference

Common Docker and Docker Compose commands for EasySave development.

## Docker Compose Basics

```bash
# Start containers (background)
docker compose up -d

# Start containers (foreground, show logs)
docker compose up

# Rebuild and start
docker compose up --build

# Stop containers
docker compose stop

# Stop and remove containers
docker compose down

# Stop and remove everything (including volumes/data)
docker compose down -v

# View running containers
docker compose ps

# View logs
docker compose logs

# Follow logs in real-time
docker compose logs -f

# Logs from specific service
docker compose logs -f php
docker compose logs -f web
docker compose logs -f db
```

## Container Execution

```bash
# Execute command in PHP container
docker compose exec php <command>

# Execute with bash
docker compose exec php bash

# Interactive shell
docker compose exec -it php bash

# Example: Run Symfony command
docker compose exec php php bin/console cache:clear --env=dev
docker compose exec php php bin/console doctrine:migrations:migrate
```

## Common Development Tasks

### Clear Cache
```bash
docker compose exec php php bin/console cache:clear --env=dev
docker compose exec php php bin/console cache:warmup --env=dev
```

### Database Migrations
```bash
# Create new migration
docker compose exec php php bin/console make:migration

# Run migrations
docker compose exec php php bin/console doctrine:migrations:migrate

# Check migration status
docker compose exec php php bin/console doctrine:migrations:status

# Rollback last migration
docker compose exec php php bin/console doctrine:migrations:migrate prev
```

### Database Management
```bash
# Access MySQL CLI
docker compose exec db mysql -u easysave -peasysave_pass easysave

# Dump database
docker compose exec db mysqldump -u easysave -peasysave_pass easysave > backup.sql

# Restore database
docker compose exec -T db mysql -u easysave -peasysave_pass easysave < backup.sql

# Create database
docker compose exec php php bin/console doctrine:database:create

# Drop database
docker compose exec php php bin/console doctrine:database:drop --force

# Reset database (drop and create)
docker compose exec php php bin/console doctrine:schema:drop --force
docker compose exec php php bin/console doctrine:migrations:migrate
```

### Fixtures and Testing
```bash
# Load fixtures
docker compose exec php php bin/console doctrine:fixtures:load

# Run tests
docker compose exec php php bin/phpunit

# Run tests with coverage
docker compose exec php php bin/phpunit --coverage-html=coverage

# Run specific test
docker compose exec php php bin/phpunit tests/Controller/UserTest.php
```

### Code Quality
```bash
# PHP lint
docker compose exec php php -l <file>

# PHP CS Fixer
docker compose exec php php vendor/bin/php-cs-fixer fix src/

# PHPStan
docker compose exec php php vendor/bin/phpstan analyse src/

# Psalm
docker compose exec php php vendor/bin/psalm
```

## Service Management

### Restart Service
```bash
docker compose restart php
docker compose restart web
docker compose restart db
```

### Stop Service
```bash
docker compose stop php
docker compose stop web
docker compose stop db
```

### Remove Service
```bash
# Just container (not volume)
docker compose down --remove-orphans

# Including volumes
docker compose down -v
```

## Debugging

### View Logs
```bash
# All services
docker compose logs

# Specific service
docker compose logs php
docker compose logs -f web

# Last 100 lines
docker compose logs --tail=100

# With timestamps
docker compose logs -f --timestamps
```

### Inspect Container
```bash
# List processes
docker compose exec php ps aux

# Check disk usage
docker compose exec php df -h

# Check environment variables
docker compose exec php env

# Check network
docker compose exec php ip addr
```

### Performance Check
```bash
# Container stats
docker stats

# Memory/CPU usage
docker compose stats

# Process list
docker compose exec php top
```

## Maintenance

### Cleanup
```bash
# Remove stopped containers
docker container prune

# Remove unused images
docker image prune

# Remove unused volumes
docker volume prune

# Remove everything unused (careful!)
docker system prune -a
```

### Update Images
```bash
# Pull latest images
docker compose pull

# Rebuild images
docker compose build --no-cache

# Update everything
docker compose pull && docker compose up --build -d
```

## Docker Build

### Build Images Manually
```bash
# Build development image
docker build -f Dockerfile.dev -t easysave-dev .

# Build production image
docker build -f Dockerfile -t easysave-prod .

# Build with no cache
docker build --no-cache -f Dockerfile -t easysave .

# Build with build arguments
docker build --build-arg ENV=prod -f Dockerfile -t easysave .
```

### Run Container Directly
```bash
# Run PHP container
docker run -it --rm -v $(pwd):/app easysave-dev bash

# Run with environment variables
docker run -e APP_ENV=dev -it --rm easysave-dev php -v

# Run with ports exposed
docker run -p 8080:80 -d easysave-prod
```

## Database Backup & Restore

### MySQL Backup
```bash
# Full backup
docker compose exec db mysqldump -u easysave -peasysave_pass easysave > backup_$(date +%Y%m%d).sql

# Backup specific table
docker compose exec db mysqldump -u easysave -peasysave_pass easysave users > users_backup.sql

# Backup with compression
docker compose exec db mysqldump -u easysave -peasysave_pass easysave | gzip > backup.sql.gz
```

### MySQL Restore
```bash
# Restore full database
docker compose exec -T db mysql -u easysave -peasysave_pass easysave < backup.sql

# Restore from gzip
docker compose exec -T db mysql -u easysave -peasysave_pass easysave < <(gunzip -c backup.sql.gz)

# Restore specific table
docker compose exec -T db mysql -u easysave -peasysave_pass easysave < users_backup.sql
```

## Networking

### Network Commands
```bash
# List networks
docker network ls

# Inspect network
docker network inspect easysave_easysave-network

# Test connectivity
docker compose exec php ping db
docker compose exec php ping mailhog
```

### Port Mapping
```bash
# Check port bindings
docker compose ps

# View port details
netstat -tlnp | grep LISTEN

# Change port mapping (edit docker-compose.yaml)
```

## Environment & Configuration

### Environment Variables
```bash
# View all environment variables
docker compose exec php env

# Set environment variable for command
docker compose exec -e APP_ENV=prod php php -v

# View .env file
docker compose exec php cat .env
```

### Copy Files
```bash
# Copy file from container to host
docker compose cp php:/app/var/log/symfony.log ./symfony.log

# Copy file from host to container
docker compose cp ./config.php php:/app/config/config.php
```

## Development Tips

### Hot Reload
Changes to code are automatically reflected (volumes mounted).
```bash
# Just refresh browser to see changes
# Reload containers if config changes
docker compose restart php
```

### Database Access
```bash
# phpMyAdmin: http://localhost:8081
# Credentials: easysave / easysave_pass

# CLI access
docker compose exec db mysql -u easysave -peasysave_pass easysave
```

### Email Testing
```bash
# MailHog Web UI: http://localhost:8025
# Check all sent emails without delivery
# View email HTML/text in browser
```

### Xdebug Setup
```bash
# Already configured in Dockerfile.dev
# Use VS Code PHP Debug extension
# Listen on port 9003
```

## Troubleshooting Commands

```bash
# Check if ports are in use
lsof -i :8080
lsof -i :3306

# Force remove container
docker compose rm -f php

# Reset everything
docker compose down -v
docker system prune -a
docker compose up --build

# View detailed error logs
docker compose logs --timestamps php | tail -50

# Check disk space
docker system df

# Monitor real-time
docker stats
watch -n 1 'docker compose ps'
```

## Production Commands (Railway)

```bash
# SSH into Railway container
railway shell

# Run command in Railway container
railway run php bin/console cache:clear

# View logs
railway logs

# Restart service
railway restart

# Check environment
railway variables
```

## Cheat Sheet

| Task | Command |
|------|---------|
| Start dev | `docker compose up -d` |
| Stop all | `docker compose down` |
| View logs | `docker compose logs -f` |
| Run migration | `docker compose exec php php bin/console doctrine:migrations:migrate` |
| Clear cache | `docker compose exec php php bin/console cache:clear --env=dev` |
| Access DB | `docker compose exec db mysql -u easysave -peasysave_pass easysave` |
| Shell into PHP | `docker compose exec -it php bash` |
| Backup DB | `docker compose exec db mysqldump -u easysave -peasysave_pass easysave > backup.sql` |
| View stats | `docker stats` |
| Clean up | `docker system prune -a` |

---

For more help:
- `docker compose --help`
- `docker --help`
- https://docs.docker.com
