# Envobyte Assignment — Monica Tag System Extension

## Approach

Monica CRM internally calls "tags" as **Labels**. After exploring the codebase, I found:
- `labels` table with `vault_id`, `name`, `slug`, `description`, `bg_color`, `text_color`
- `contact_label` pivot table connecting labels to contacts
- Labels are scoped per vault, not per account

All API endpoints follow Monica's existing pattern of vault-scoped URLs: `/api/vaults/{vault}/tags`.

## What Was Built

### Database Changes
- Added `tag_category` column to `labels` table (nullable)
- Added indexes on `contact_label.label_id` and `contact_label.contact_id`

### API Endpoints
| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/vaults/{vault}/tags` | List tags with usage count (cached) |
| POST | `/api/vaults/{vault}/tags` | Create a tag |
| PUT | `/api/vaults/{vault}/tags/{tag}` | Update a tag |
| DELETE | `/api/vaults/{vault}/tags/{tag}` | Delete a tag (detaches from contacts) |
| POST | `/api/vaults/{vault}/contacts/{contact}/tags` | Attach tags to contact |
| DELETE | `/api/vaults/{vault}/contacts/{contact}/tags/{tag}` | Detach tag from contact |
| GET | `/api/vaults/{vault}/contacts?tags[]=1&tags[]=2` | Filter contacts by tags (AND logic) |

### Caching
- `GET /api/vaults/{vault}/tags` is cached in Redis with 10-minute TTL
- Cache key format: `vault_{vault_id}_tags`
- Cache is invalidated on: tag create, update, delete, attach, detach

### AND Filtering
Contacts filtered by multiple tags use a single SQL query:
```php
$query->whereHas('labels', function ($q) use ($tagIds) {
    $q->whereIn('labels.id', $tagIds);
}, '=', $tagCount);
```

## Assumptions & Trade-offs
- Used Monica's existing `Label` model instead of creating a new `Tag` model to avoid duplication
- Vault is required in the URL to scope all queries — consistent with Monica's existing API design
- SQLite used for testing (in-memory), MariaDB used for production
- `tag_category` is optional/nullable — existing tags are unaffected

## How to Run Tests
```bash
./vendor/bin/sail artisan test tests/Feature/TagSystemTest.php
```

## Setup
```bash
cp .env.example.sail .env
# Set CACHE_STORE=redis and APP_PORT=8080 in .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```
