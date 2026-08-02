import { Suspense } from 'react';
import SearchResultsPage from '@/components/pages/SearchResultsPage';

export default function Page() {
  return <Suspense fallback={null}><SearchResultsPage locale="en" /></Suspense>;
}
