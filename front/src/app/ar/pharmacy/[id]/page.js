import PharmacyDetailPage from '@/components/pages/PharmacyDetailPage';

export default async function Page({ params }) {
  const { id } = await params;
  return <PharmacyDetailPage locale="ar" pharmacyId={id} />;
}
