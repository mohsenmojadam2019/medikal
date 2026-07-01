import './styles/globals.css';

export const metadata = {
  title: 'کلینیک‌یار - پنل مدیریت',
  description: 'سیستم مدیریت کلینیک',
  icons: {
    icon: '/favicon.ico',
  },
};

export default function RootLayout({ children }) {
  return (
    <html lang="fa" dir="rtl">
      <body>{children}</body>
    </html>
  );
}
