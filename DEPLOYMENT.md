# Deployment Guide - Portainer

## Deploy ke Portainer (port.nicoagusta.id)

### Langkah 1: Push ke GitHub

```bash
# Di lokal, push code ke GitHub
git init
git add .
git commit -m "Initial commit - WA CRM Astra"
git remote add origin https://github.com/YOUR_USERNAME/wa-crm-astra.git
git push -u origin main
```

### Langkah 2: Build & Push Docker Image (Opsional)

```bash
# Build image
docker build -t ghcr.io/YOUR_USERNAME/wa-crm-astra:latest .

# Login ke GitHub Container Registry
echo $GITHUB_TOKEN | docker login ghcr.io -u YOUR_USERNAME --password-stdin

# Push image
docker push ghcr.io/YOUR_USERNAME/wa-crm-astra:latest
```

### Langkah 3: Deploy via Portainer

1. **Login** ke https://port.nicoagusta.id
2. **Klik** environment "local"
3. **Navigate** ke **Stacks** > **Add stack**
4. **Paste** konten dari `docker-compose.prod.yml`
5. **Set Environment Variables:**

| Variable              | Value                                                            |
| --------------------- | ---------------------------------------------------------------- |
| `APP_KEY`             | `base64:...` (generate dengan `php artisan key:generate --show`) |
| `APP_URL`             | `https://wa-crm.nicoagusta.id`                                   |
| `DB_PASSWORD`         | Password database yang aman                                      |
| `MYSQL_ROOT_PASSWORD` | Password root MySQL                                              |
| `WAHA_API_KEY`        | API key untuk WAHA                                               |

6. **Klik** "Deploy the stack"

### Langkah 4: Run Migrations

Setelah stack running:

```bash
# Via Portainer Console atau SSH
docker exec -it wa-crm-app php artisan migrate --force
docker exec -it wa-crm-app php artisan db:seed --force
```

### Langkah 5: Setup Domain (via DNS)

Tambahkan DNS records:

| Type | Name   | Value     |
| ---- | ------ | --------- |
| A    | wa-crm | IP_SERVER |
| A    | waha   | IP_SERVER |

---

## Environment Variables Reference

```env
# App
APP_KEY=base64:xxxxx
APP_URL=https://wa-crm.nicoagusta.id
APP_PORT=8080

# Database
DB_DATABASE=wa_crm_astra
DB_USERNAME=wacrm
DB_PASSWORD=secure_password
MYSQL_ROOT_PASSWORD=root_secure_password

# WAHA
WAHA_API_KEY=your-secure-api-key
WAHA_PORT=3000
```

---

## Troubleshooting

### Check Logs

```bash
docker logs wa-crm-app
docker logs wa-crm-waha
docker logs wa-crm-mysql
```

### Restart Stack

Via Portainer: Stacks > wa-crm > Restart

### Clear Cache

```bash
docker exec -it wa-crm-app php artisan cache:clear
docker exec -it wa-crm-app php artisan config:clear
```
