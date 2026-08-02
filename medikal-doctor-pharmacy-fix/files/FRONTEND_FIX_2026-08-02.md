# Medikal frontend targeted fix — 2026-08-02

## Fixed

- Added a Next.js same-origin proxy: `/backend-api/*` → Docker service `medikall-webserver`.
- Browser API calls no longer depend on `NEXT_PUBLIC_API_URL=http://localhost:8210`.
- Server-side API calls use `API_INTERNAL_URL` or `http://medikall-webserver`.
- Persian, English and Arabic doctor pages now use the shared `DoctorsDirectoryPage` implementation.
- Persian, English and Arabic home pages now use the shared `PlatformHome` implementation, so the homepage doctor list uses the same repaired API client.
- Persian, English and Arabic pharmacy pages now use the shared pharmacy directory with name/address search.
- Mobile bottom navigation is mounted globally and has its own responsive CSS module.

## Root cause

The project contained the new shared API client and shared pages, but the actual route files still contained old direct requests to `http://localhost:8210`. In addition, `next.config.js` did not contain the `/backend-api` rewrite described in the earlier audit. Therefore browser requests could fail because of CORS, stale environment variables, or Docker localhost routing.

## Files changed

- `front/next.config.js`
- `front/src/lib/api/client.js`
- `front/src/app/layout.js`
- `front/src/app/fa/page.js`
- `front/src/app/en/page.js`
- `front/src/app/ar/page.js`
- `front/src/app/fa/doctors/page.js`
- `front/src/app/en/doctors/page.js`
- `front/src/app/ar/doctors/page.js`
- `front/src/app/fa/pharmacy/page.js`
- `front/src/app/en/pharmacy/page.js`
- `front/src/app/ar/pharmacy/page.js`
- `front/src/components/platform/MobileBottomNav.js`
- `front/src/components/platform/MobileBottomNav.module.css`

## Backend limitation

The current backend has public doctor and pharmacy routes, but it does not expose a dedicated public endpoint that groups one medicine across multiple pharmacies with structured city/region availability. That feature should be implemented as a pharmacist-controlled, prescription-aware workflow rather than inferred from incomplete public inventory data.

## Required Docker environment

Recommended in the `front` service:

```yaml
environment:
  - API_INTERNAL_URL=http://medikall-webserver
```

The frontend browser now uses `/backend-api` automatically.

## Validation commands

```bash
docker compose up -d --force-recreate front
docker compose exec front sh -lc 'NODE_ENV=production npm run build'
docker compose exec front sh -lc 'npm run lint'
docker compose logs --tail=200 front medikall-webserver medikall-laravel
```

## API smoke tests

```bash
curl -i http://localhost:8210/api/ping
curl -i 'http://localhost:8210/api/doctors?per_page=4'
curl -i 'http://localhost:3000/backend-api/api/doctors?per_page=4'
```
