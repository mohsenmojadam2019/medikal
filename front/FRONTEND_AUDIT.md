# Medikal Frontend Audit

**Audit date:** 2026-08-02  
**Frontend:** Next.js 16.2.4 / React 19 / Ant Design 6

## Work completed

### 1. API connection and Docker networking

- Added a same-origin API proxy at `/backend-api/api/*`.
- Browser requests no longer call `http://localhost:8210` directly.
- Next.js forwards API calls to `API_INTERNAL_URL` and defaults to `http://medikall-webserver` inside the Docker network.
- This removes the common Docker `localhost` mismatch and avoids browser CORS failures.
- Added a reusable API client with:
  - JSON and non-JSON error handling
  - request timeouts
  - bearer-token support
  - collection and pagination normalization
  - readable service error messages

### 2. Pages repaired

- `/fa/doctors`, `/en/doctors`, `/ar/doctors`
  - Real `/api/doctors` and `/api/specialties` integration
  - Server-side filters sent as query parameters
  - pagination, loading, empty and retry states
- `/fa/pharmacy`, `/en/pharmacy`, `/ar/pharmacy`
  - Real `/api/pharmacy/pharmacies` integration
  - search, pagination, loading, empty and retry states
- Pharmacy details in all three languages
  - `/fa/pharmacy/[id]`
  - `/en/pharmacy/[id]`
  - `/ar/pharmacy/[id]`
- Imaging in all three languages
  - Replaced the generic connection error with the real backend integration status
  - Added a clear PACS workflow and health-record entry point
- AI chat in all three languages
  - Uses protected `/api/v1/chat/active`, `/start` and `/send` routes
  - Correct unauthenticated state
  - reconnect, loading and error states
  - medical-safety notice
- Search in all three languages
  - Searches doctors and products through real endpoints
- Privacy page in all three languages

### 3. Three-language platform UI

- Rebuilt the Persian, English and Arabic home pages from one shared platform component.
- Added a super-app style service grid for:
  - doctors
  - pharmacy
  - laboratory
  - imaging
  - AI assistant
  - appointments
  - health records
  - support
- Added localized hero, search, doctor and pharmacy sections.
- Rebuilt the global header, desktop navigation and footer with locale-aware links.
- Language switching now derives the language from the URL and keeps the current route.
- Added locale-specific metadata and RTL/LTR behavior.

### 4. Mobile platform behavior

- Added a fixed bottom navigation for mobile:
  - Home
  - Doctors
  - Pharmacy
  - AI assistant
  - Account
- Added safe-area support and mobile bottom spacing.
- Desktop navigation is hidden on mobile.
- Main platform sections use horizontal service cards and responsive grids.

### 5. Next.js and authentication routing

- Updated the deprecated lint script from `next lint` to `eslint .`.
- Simplified `proxy.js` so it does not redirect authenticated users incorrectly.
- The previous proxy attempted to read a cookie while the application stores the token in localStorage; this could redirect logged-in users as guests.
- Added security headers without blocking application routes.

## Backend API status

### Confirmed routes used by the repaired pages

- `GET /api/doctors`
- `GET /api/doctors/{id}`
- `GET /api/specialties`
- `GET /api/products`
- `GET /api/pharmacy/pharmacies`
- `GET /api/pharmacy/pharmacies/{id}`
- `GET /api/clinics`
- `GET /api/clinics/provinces`
- `GET /api/clinics/provinces/{provinceId}/cities`
- `POST /api/auth/login/mobile`
- `POST /api/auth/login/mobile/verify`
- `POST /api/auth/login/email`
- Protected AI routes under `/api/v1/chat/*`

### Missing or incomplete backend APIs

1. **Registration and old OTP flow**
   - `POST /api/auth/register`
   - `POST /api/auth/verify-otp`
   - `POST /api/auth/resend-otp`
   - Frontend components still reference these routes, but they are not present in `routes/api.php`.

2. **Landing and content APIs**
   - `GET /api/landing/stats`
   - `GET /api/blog/posts`
   - `GET /api/faqs`

3. **Discount validation**
   - `POST /api/discounts/validate`

4. **Imaging / PACS**
   - No public or patient-facing image listing, upload, download and delete endpoints are registered.
   - The backend currently only has `medical-notes/{id}/imaging-request` for adding an imaging request to a medical note.

5. **AI provider integration**
   - `/api/v1/chat/*` routes exist.
   - The current backend chat controller returns a test response and is not yet connected to Ollama or another AI provider.

6. **Pharmacy admin order status**
   - The frontend references `/api/admin/pharmacy-orders/{orderId}/status`.
   - The registered admin routes expose list/show/approve/reject operations, but not this status endpoint.

7. **Public doctor ratings**
   - Some doctor-detail pages request ratings without authentication.
   - Rating routes are currently inside the authenticated route group and can return `401` for visitors.

## Validation

- Parsed all JavaScript and JSX files with the TypeScript parser: **194 files, 0 syntax errors**.
- `package.json`, locale JSON and Next.js configuration syntax validated.
- `npm run build` and `npm run lint` could not be completed in the artifact environment because the package mirror returned `404` for dependency tarballs during `npm ci` (`tslib` and `zod-validation-error`).
- Run the following in the actual project container, where dependencies are already installed:

```bash
NODE_ENV=production npm run build
npm run lint
```

## Required Docker environment

The Front service can use the default Docker service name automatically. The explicit recommended value is:

```yaml
environment:
  - API_INTERNAL_URL=http://medikall-webserver
```

The browser-facing `NEXT_PUBLIC_API_URL` no longer needs to point to port `8210`; it is set internally to `/backend-api` by `next.config.js`.

## Files changed or added

- `next.config.js`
- `package.json`
- `src/app/ar/doctors/page.js`
- `src/app/ar/layout.js`
- `src/app/ar/page.js`
- `src/app/ar/pharmacy/page.js`
- `src/app/en/doctors/page.js`
- `src/app/en/layout.js`
- `src/app/en/page.js`
- `src/app/en/pharmacy/page.js`
- `src/app/fa/ai-chat/page.js`
- `src/app/fa/doctors/page.js`
- `src/app/fa/imaging/page.js`
- `src/app/fa/layout.js`
- `src/app/fa/page.js`
- `src/app/fa/pharmacy/[id]/page.js`
- `src/app/fa/pharmacy/page.js`
- `src/app/fa/search/page.js`
- `src/app/layout.js`
- `src/components/front/Footer/Footer.js`
- `src/components/front/Header/Header.js`
- `src/components/front/Header/NavBar.js`
- `src/lib/context/LanguageContext.js`
- `src/proxy.js`
- `FRONTEND_AUDIT.md`
- `src/app/ar/ai-chat/`
- `src/app/ar/imaging/`
- `src/app/ar/pharmacy/[id]/`
- `src/app/ar/privacy/`
- `src/app/ar/search/`
- `src/app/en/ai-chat/`
- `src/app/en/imaging/`
- `src/app/en/pharmacy/[id]/`
- `src/app/en/privacy/`
- `src/app/en/search/`
- `src/app/fa/privacy/`
- `src/app/platform.css`
- `src/components/pages/`
- `src/components/platform/`
- `src/lib/api/`
