'use client';

import dynamic from 'next/dynamic';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import styles from './MedicalMap.module.css';

const LeafletMap = dynamic(() => import('./LeafletMap'), {
  ssr: false,
  loading: () => <div className={styles.mapLoading}>در حال بارگذاری نقشه...</div>,
});

const TYPES = [
  { value: 'pharmacy', label: 'داروخانه', icon: '✚' },
  { value: 'clinic', label: 'کلینیک', icon: '⚕' },
  { value: 'hospital', label: 'بیمارستان', icon: 'H' },
  { value: 'laboratory', label: 'آزمایشگاه', icon: '◉' },
  { value: 'imaging', label: 'تصویربرداری', icon: '⌁' },
];

const TYPE_LABELS = Object.fromEntries(TYPES.map((item) => [item.value, item.label]));

function numberOrNull(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function normalizeFacility(item) {
  if (!item || typeof item !== 'object') return null;
  const coordinates = item.geometry?.coordinates;
  const source = item.geometry ? { ...(item.properties || {}), id: item.properties?.id ?? item.id } : item;

  const latitude = numberOrNull(
    source.latitude ?? source.lat ?? (Array.isArray(coordinates) ? coordinates[1] : null),
  );
  const longitude = numberOrNull(
    source.longitude ?? source.lng ?? source.lon ?? (Array.isArray(coordinates) ? coordinates[0] : null),
  );

  if (latitude === null || longitude === null) return null;

  return {
    id: String(source.id ?? `${latitude}-${longitude}`),
    type: source.type || source.facility_type || 'clinic',
    name: source.name || source.title || 'مرکز درمانی',
    latitude,
    longitude,
    address: source.address || source.full_address || 'آدرس ثبت نشده',
    phone: source.phone || source.mobile || '',
    rating: numberOrNull(source.rating),
    is_open: Boolean(source.is_open ?? source.open_now ?? false),
    is_24_hours: Boolean(source.is_24_hours ?? source.twenty_four_hours ?? false),
    services: Array.isArray(source.services) ? source.services : [],
    url: source.url || source.detail_url || '',
  };
}

function normalizePayload(payload) {
  const raw =
    payload?.type === 'FeatureCollection'
      ? payload.features
      : payload?.data?.type === 'FeatureCollection'
        ? payload.data.features
        : Array.isArray(payload?.data)
          ? payload.data
          : Array.isArray(payload)
            ? payload
            : [];

  return raw.map(normalizeFacility).filter(Boolean);
}

function distanceMeters(origin, destination) {
  if (!origin || !destination) return null;
  const radius = 6371000;
  const toRad = (value) => (value * Math.PI) / 180;
  const lat1 = toRad(origin.latitude);
  const lat2 = toRad(destination.latitude);
  const dLat = toRad(destination.latitude - origin.latitude);
  const dLng = toRad(destination.longitude - origin.longitude);
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
  return radius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function formatDistance(value) {
  if (!Number.isFinite(value)) return '';
  return value < 1000
    ? `${Math.round(value)} متر`
    : `${(value / 1000).toFixed(value < 10000 ? 1 : 0)} کیلومتر`;
}

function formatDuration(value) {
  if (!Number.isFinite(value)) return '';
  const minutes = Math.max(1, Math.round(value / 60));
  if (minutes < 60) return `${minutes} دقیقه`;
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;
  return rest ? `${hours} ساعت و ${rest} دقیقه` : `${hours} ساعت`;
}

export default function MedicalMapPage() {
  const [facilities, setFacilities] = useState([]);
  const [types, setTypes] = useState(TYPES.map((item) => item.value));
  const [search, setSearch] = useState('');
  const [onlyOpen, setOnlyOpen] = useState(false);
  const [only24, setOnly24] = useState(false);
  const [bounds, setBounds] = useState(null);
  const [selected, setSelected] = useState(null);
  const [userLocation, setUserLocation] = useState(null);
  const [manualOrigin, setManualOrigin] = useState(false);
  const [route, setRoute] = useState(null);
  const [loading, setLoading] = useState(true);
  const [routing, setRouting] = useState(false);
  const [locating, setLocating] = useState(false);
  const [message, setMessage] = useState('');
  const requestRef = useRef(null);

  const loadFacilities = useCallback(async () => {
    requestRef.current?.abort();
    const controller = new AbortController();
    requestRef.current = controller;

    const params = new URLSearchParams();
    if (bounds) {
      params.set('bbox', [bounds.west, bounds.south, bounds.east, bounds.north].join(','));
    }
    if (types.length) params.set('types', types.join(','));
    if (search.trim()) params.set('search', search.trim());
    if (onlyOpen) params.set('open_now', '1');
    if (only24) params.set('is_24_hours', '1');

    setLoading(true);
    try {
      const response = await fetch(`/api/map/facilities?${params}`, {
        cache: 'no-store',
        signal: controller.signal,
      });
      if (!response.ok) throw new Error('FACILITIES_REQUEST_FAILED');
      const payload = await response.json();
      const list = normalizePayload(payload);
      setFacilities(list);
      setMessage(list.length ? '' : 'مرکزی با این فیلترها پیدا نشد.');
    } catch (error) {
      if (error?.name !== 'AbortError') {
        setMessage('دریافت مراکز با خطا روبه‌رو شد.');
      }
    } finally {
      if (!controller.signal.aborted) setLoading(false);
    }
  }, [bounds, types, search, onlyOpen, only24]);

  useEffect(() => {
    const timer = setTimeout(loadFacilities, 350);
    return () => clearTimeout(timer);
  }, [loadFacilities]);

  useEffect(() => () => requestRef.current?.abort(), []);

  const visibleFacilities = useMemo(
    () =>
      facilities
        .map((facility) => ({
          ...facility,
          straightDistance: userLocation ? distanceMeters(userLocation, facility) : null,
        }))
        .sort((a, b) =>
          userLocation
            ? a.straightDistance - b.straightDistance
            : Number(b.is_open) - Number(a.is_open),
        ),
    [facilities, userLocation],
  );

  const toggleType = (type) => {
    setTypes((current) =>
      current.includes(type)
        ? current.filter((item) => item !== type)
        : [...current, type],
    );
    setRoute(null);
  };

  const locateUser = () => {
    if (!navigator.geolocation) {
      setMessage('مرورگر شما موقعیت مکانی را پشتیبانی نمی‌کند.');
      return;
    }

    setLocating(true);
    navigator.geolocation.getCurrentPosition(
      (position) => {
        setUserLocation({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
        });
        setManualOrigin(false);
        setLocating(false);
        setMessage('موقعیت شما ثبت شد.');
      },
      () => {
        setLocating(false);
        setMessage('اجازه موقعیت داده نشد؛ مبدا را دستی روی نقشه انتخاب کنید.');
      },
      { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 },
    );
  };

  const chooseManualOrigin = (position) => {
    if (!manualOrigin) return;
    setUserLocation(position);
    setManualOrigin(false);
    setRoute(null);
    setMessage('مبدا دستی ثبت شد.');
  };

  const requestRoute = async (facility) => {
    if (!userLocation) {
      setSelected(facility);
      setMessage('ابتدا موقعیت خود را ثبت یا مبدا را روی نقشه انتخاب کنید.');
      return;
    }

    setRouting(true);
    setSelected(facility);
    setMessage('');

    try {
      const response = await fetch('/api/map/route', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          origin: userLocation,
          destination: {
            latitude: facility.latitude,
            longitude: facility.longitude,
          },
          mode: 'driving',
        }),
      });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload?.message || 'ROUTE_REQUEST_FAILED');

      setRoute({ ...payload, destinationId: facility.id });
      setMessage(
        `مسیر تا ${facility.name}: ${formatDistance(payload.distance_meters)}، حدود ${formatDuration(payload.duration_seconds)}`,
      );
    } catch {
      setMessage('محاسبه مسیر انجام نشد؛ سرویس مسیریابی را بررسی کنید.');
    } finally {
      setRouting(false);
    }
  };

  const selectNearest = () => {
    if (!userLocation) {
      setMessage('برای نزدیک‌ترین مرکز ابتدا موقعیت خود را ثبت کنید.');
      return;
    }
    const nearest = visibleFacilities[0];
    if (!nearest) {
      setMessage('در محدوده فعلی مرکزی پیدا نشد.');
      return;
    }
    requestRoute(nearest);
  };

  return (
    <main className={styles.page} dir="rtl">
      <header className={styles.header}>
        <div>
          <span className={styles.eyebrow}>دکتر وب</span>
          <h1>نقشه مراکز درمانی</h1>
          <p>داروخانه، کلینیک و مراکز درمانی نزدیک خود را پیدا و مسیریابی کنید.</p>
        </div>

        <div className={styles.headerActions}>
          <button
            type="button"
            className={styles.secondaryButton}
            onClick={locateUser}
            disabled={locating}
          >
            {locating ? 'در حال دریافت...' : '◎ موقعیت من'}
          </button>
          <button
            type="button"
            className={styles.primaryButton}
            onClick={selectNearest}
            disabled={routing}
          >
            {routing ? 'در حال محاسبه...' : '⌖ نزدیک‌ترین مرکز'}
          </button>
        </div>
      </header>

      <section className={styles.workspace}>
        <aside className={styles.sidebar}>
          <div className={styles.searchBox}>
            <label htmlFor="facility-search">جستجوی مرکز یا آدرس</label>
            <input
              id="facility-search"
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="مثلاً داروخانه شبانه‌روزی"
            />
          </div>

          <div className={styles.filterSection}>
            <strong>نوع مرکز</strong>
            <div className={styles.typeFilters}>
              {TYPES.map((type) => {
                const active = types.includes(type.value);
                return (
                  <button
                    type="button"
                    key={type.value}
                    className={`${styles.typeButton} ${active ? styles.typeButtonActive : ''}`}
                    onClick={() => toggleType(type.value)}
                    aria-pressed={active}
                  >
                    <span>{type.icon}</span>
                    {type.label}
                  </button>
                );
              })}
            </div>
          </div>

          <div className={styles.switches}>
            <label>
              <input
                type="checkbox"
                checked={onlyOpen}
                onChange={(event) => setOnlyOpen(event.target.checked)}
              />
              فقط مراکز باز
            </label>
            <label>
              <input
                type="checkbox"
                checked={only24}
                onChange={(event) => setOnly24(event.target.checked)}
              />
              فقط شبانه‌روزی
            </label>
          </div>

          <div className={styles.originTools}>
            <button
              type="button"
              className={manualOrigin ? styles.warningButton : styles.outlineButton}
              onClick={() => {
                setManualOrigin((current) => !current);
                setMessage(
                  manualOrigin ? '' : 'حالا روی نقطه مبدا در نقشه کلیک کنید.',
                );
              }}
            >
              {manualOrigin ? 'لغو انتخاب مبدا' : 'انتخاب دستی مبدا'}
            </button>
            {route && (
              <button
                type="button"
                className={styles.textButton}
                onClick={() => {
                  setRoute(null);
                  setMessage('');
                }}
              >
                پاک‌کردن مسیر
              </button>
            )}
          </div>

          <div className={styles.resultHeader}>
            <strong>مراکز این محدوده</strong>
            <span>{loading ? '...' : visibleFacilities.length}</span>
          </div>

          <div className={styles.resultList}>
            {visibleFacilities.map((facility) => (
              <article
                key={`${facility.type}-${facility.id}`}
                className={`${styles.facilityCard} ${
                  selected?.id === facility.id ? styles.facilityCardActive : ''
                }`}
              >
                <button
                  type="button"
                  className={styles.cardMain}
                  onClick={() => setSelected(facility)}
                >
                  <div className={styles.cardTop}>
                    <span className={`${styles.typeDot} ${styles[facility.type] || ''}`} />
                    <div>
                      <h2>{facility.name}</h2>
                      <p>{TYPE_LABELS[facility.type] || 'مرکز درمانی'}</p>
                    </div>
                    <span className={facility.is_open ? styles.openBadge : styles.closedBadge}>
                      {facility.is_open ? 'باز' : 'بسته'}
                    </span>
                  </div>
                  <p className={styles.address}>{facility.address}</p>
                  <div className={styles.cardMeta}>
                    {facility.is_24_hours && <span>شبانه‌روزی</span>}
                    {facility.rating !== null && <span>★ {facility.rating}</span>}
                    {facility.straightDistance !== null && (
                      <span>{formatDistance(facility.straightDistance)}</span>
                    )}
                  </div>
                </button>

                <div className={styles.cardActions}>
                  {facility.phone ? (
                    <a href={`tel:${facility.phone}`} className={styles.callButton}>
                      تماس
                    </a>
                  ) : (
                    <span />
                  )}
                  <button
                    type="button"
                    className={styles.routeButton}
                    onClick={() => requestRoute(facility)}
                    disabled={routing}
                  >
                    مسیریابی
                  </button>
                </div>
              </article>
            ))}

            {!loading && !visibleFacilities.length && (
              <div className={styles.emptyState}>مرکزی در این محدوده پیدا نشد.</div>
            )}
          </div>
        </aside>

        <div className={styles.mapPanel}>
          <LeafletMap
            facilities={visibleFacilities}
            selectedFacility={selected}
            onSelectFacility={setSelected}
            onRouteRequest={requestRoute}
            onBoundsChange={setBounds}
            userLocation={userLocation}
            onMapClick={chooseManualOrigin}
            manualOriginMode={manualOrigin}
            route={route}
          />

          <div className={styles.mapLegend}>
            {TYPES.map((type) => (
              <span key={type.value}>
                <i className={`${styles.legendDot} ${styles[type.value] || ''}`} />
                {type.label}
              </span>
            ))}
          </div>

          {message && (
            <div className={styles.message} role="status" aria-live="polite">
              {message}
            </div>
          )}

          {route && selected && (
            <div className={styles.routeSummary}>
              <div>
                <small>مسیر پیشنهادی</small>
                <strong>{selected.name}</strong>
              </div>
              <div>
                <span>{formatDistance(route.distance_meters)}</span>
                <span>{formatDuration(route.duration_seconds)}</span>
              </div>
            </div>
          )}
        </div>
      </section>
    </main>
  );
}
