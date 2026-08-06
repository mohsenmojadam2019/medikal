'use client';

import { useEffect, useMemo } from 'react';
import L from 'leaflet';
import {
  CircleMarker,
  MapContainer,
  Marker,
  Polyline,
  Popup,
  TileLayer,
  useMap,
  useMapEvents,
} from 'react-leaflet';
import styles from './MedicalMap.module.css';

const DEFAULT_CENTER = [35.6892, 51.389];
const TYPE_CONFIG = {
  pharmacy: { label: 'داروخانه', symbol: '✚' },
  clinic: { label: 'کلینیک', symbol: '⚕' },
  hospital: { label: 'بیمارستان', symbol: 'H' },
  laboratory: { label: 'آزمایشگاه', symbol: '◉' },
  imaging: { label: 'تصویربرداری', symbol: '⌁' },
};

function markerIcon(type, selected) {
  const config = TYPE_CONFIG[type] || TYPE_CONFIG.clinic;
  return L.divIcon({
    className: '',
    html: `<span class="${styles.marker} ${styles[`marker_${type}`] || ''} ${
      selected ? styles.markerSelected : ''
    }"><b>${config.symbol}</b></span>`,
    iconSize: selected ? [44, 52] : [38, 46],
    iconAnchor: selected ? [22, 50] : [19, 44],
    popupAnchor: [0, -42],
  });
}

function MapEvents({ onBoundsChange, onMapClick, manualOriginMode }) {
  const map = useMapEvents({
    moveend() {
      const bounds = map.getBounds();
      onBoundsChange?.({
        north: bounds.getNorth(),
        south: bounds.getSouth(),
        east: bounds.getEast(),
        west: bounds.getWest(),
      });
    },
    click(event) {
      if (manualOriginMode) {
        onMapClick?.({
          latitude: event.latlng.lat,
          longitude: event.latlng.lng,
        });
      }
    },
  });

  useEffect(() => {
    const bounds = map.getBounds();
    onBoundsChange?.({
      north: bounds.getNorth(),
      south: bounds.getSouth(),
      east: bounds.getEast(),
      west: bounds.getWest(),
    });
  }, [map, onBoundsChange]);

  return null;
}

function MapController({ selectedFacility, userLocation, route }) {
  const map = useMap();

  useEffect(() => {
    if (route?.geometry?.coordinates?.length) {
      const latLngs = route.geometry.coordinates.map(([lng, lat]) => [lat, lng]);
      map.fitBounds(latLngs, { padding: [60, 60], maxZoom: 16 });
      return;
    }
    if (selectedFacility) {
      map.flyTo(
        [selectedFacility.latitude, selectedFacility.longitude],
        Math.max(map.getZoom(), 15),
        { duration: 0.7 },
      );
      return;
    }
    if (userLocation) {
      map.flyTo([userLocation.latitude, userLocation.longitude], 14, { duration: 0.7 });
    }
  }, [map, selectedFacility, userLocation, route]);

  return null;
}

export default function LeafletMap({
  facilities,
  selectedFacility,
  onSelectFacility,
  onRouteRequest,
  onBoundsChange,
  userLocation,
  onMapClick,
  manualOriginMode,
  route,
}) {
  const tileUrl =
    process.env.NEXT_PUBLIC_MAP_TILE_URL ||
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  const attribution =
    process.env.NEXT_PUBLIC_MAP_ATTRIBUTION ||
    '&copy; OpenStreetMap contributors';

  const routePositions = useMemo(() => {
    const coordinates = route?.geometry?.coordinates;
    if (!Array.isArray(coordinates)) return [];
    return coordinates.map(([longitude, latitude]) => [latitude, longitude]);
  }, [route]);

  return (
    <MapContainer
      center={DEFAULT_CENTER}
      zoom={12}
      scrollWheelZoom
      className={styles.leafletMap}
    >
      <TileLayer url={tileUrl} attribution={attribution} maxZoom={19} />

      <MapEvents
        onBoundsChange={onBoundsChange}
        onMapClick={onMapClick}
        manualOriginMode={manualOriginMode}
      />
      <MapController
        selectedFacility={selectedFacility}
        userLocation={userLocation}
        route={route}
      />

      {facilities.map((facility) => (
        <Marker
          key={`${facility.type}-${facility.id}`}
          position={[facility.latitude, facility.longitude]}
          icon={markerIcon(facility.type, selectedFacility?.id === facility.id)}
          eventHandlers={{ click: () => onSelectFacility?.(facility) }}
        >
          <Popup>
            <div className={styles.popup} dir="rtl">
              <strong>{facility.name}</strong>
              <span>{TYPE_CONFIG[facility.type]?.label || 'مرکز درمانی'}</span>
              <p>{facility.address}</p>
              <div className={styles.popupMeta}>
                <span>{facility.is_open ? 'اکنون باز است' : 'اکنون بسته است'}</span>
                {facility.is_24_hours && <span>شبانه‌روزی</span>}
              </div>
              <button type="button" onClick={() => onRouteRequest?.(facility)}>
                مسیریابی تا این مرکز
              </button>
            </div>
          </Popup>
        </Marker>
      ))}

      {userLocation && (
        <CircleMarker
          center={[userLocation.latitude, userLocation.longitude]}
          radius={9}
          pathOptions={{
            color: '#ffffff',
            weight: 4,
            fillColor: '#2563eb',
            fillOpacity: 1,
          }}
        >
          <Popup><div dir="rtl">مبدا شما</div></Popup>
        </CircleMarker>
      )}

      {routePositions.length > 1 && (
        <Polyline
          positions={routePositions}
          pathOptions={{
            color: '#2563eb',
            weight: 6,
            opacity: 0.88,
            lineCap: 'round',
            lineJoin: 'round',
          }}
        />
      )}
    </MapContainer>
  );
}
