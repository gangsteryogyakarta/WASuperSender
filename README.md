# WhatsApp CRM untuk Astra International

Sistem CRM WhatsApp untuk profesional sales otomotif menggunakan Laravel 12 dan WAHA (WhatsApp HTTP API).

## Tech Stack

- **Backend**: Laravel 12 + PHP 8.2
- **Database**: MySQL 8
- **Queue/Cache**: Redis
- **WhatsApp API**: WAHA (devlikeapro/waha)
- **Auth**: Laravel Sanctum
- **Permissions**: spatie/laravel-permission

## Quick Start

### 1. Start Docker Services

```bash
docker-compose up -d
```

Ini akan menjalankan:

- WAHA Server (port 3000)
- Redis (port 6379)
- MySQL (port 3306)

### 2. Setup Laravel

```bash
cd backend

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# (Optional) Seed sample data
php artisan db:seed
```

### 3. Start Queue Worker

```bash
php artisan queue:work redis
```

### 4. Start Development Server

```bash
php artisan serve
```

API tersedia di: `http://localhost:8000/api`

## Configuration

Edit `.env` untuk konfigurasi WAHA:

```env
WAHA_BASE_URL=http://localhost:3000
WAHA_API_KEY=your-secure-api-key
WAHA_RATE_LIMIT_PER_MINUTE=30
WAHA_MESSAGE_DELAY=2
```

## API Endpoints

### Authentication

- `POST /api/login` - Login user
- `POST /api/register` - Register user

### Contacts

- `GET /api/contacts` - List contacts
- `POST /api/contacts` - Create contact
- `POST /api/contacts/import` - Import contacts
- `PATCH /api/contacts/{id}/status` - Update lead status

### Segments

- `GET /api/segments` - List segments
- `POST /api/segments` - Create segment
- `POST /api/segments/preview` - Preview count
- `POST /api/segments/{id}/sync` - Sync contacts

### Campaigns

- `GET /api/campaigns` - List campaigns
- `POST /api/campaigns` - Create campaign
- `POST /api/campaigns/{id}/start` - Start campaign
- `POST /api/campaigns/{id}/pause` - Pause campaign
- `POST /api/campaigns/{id}/resume` - Resume campaign

### Sessions (WAHA)

- `GET /api/sessions` - List sessions
- `POST /api/sessions` - Create session
- `GET /api/sessions/{name}` - Get QR code
- `POST /api/sessions/check-number` - Validate number

### Analytics

- `GET /api/analytics/dashboard` - Dashboard stats
- `GET /api/analytics/lead-funnel` - Pipeline funnel
- `GET /api/analytics/messages` - Message stats
- `GET /api/analytics/vehicle-interest` - Vehicle distribution

### Webhook

- `POST /api/webhook/waha` - WAHA webhook receiver

## Features

- ✅ Contact Management dengan Lead Pipeline
- ✅ Dynamic Segmentation
- ✅ Broadcast Campaigns dengan Scheduling
- ✅ Automated Follow-up Sequences
- ✅ Message Template Management
- ✅ Real-time Delivery Analytics
- ✅ Rate Limiting & Queue Management
- ✅ Webhook Handler untuk Status Updates

## Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=WahaServiceTest
```

## License

MIT
