import { Card, Typography } from 'antd';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Paragraph } = Typography;
const copyMap = {
  fa: { title: 'حریم خصوصی', text: 'اطلاعات سلامت باید فقط برای ارائه خدمات مجاز و با دسترسی کنترل‌شده استفاده شود. اطلاعات ورود، فایل‌های پزشکی و سوابق کاربر نباید در کد عمومی یا گزارش‌های قابل انتشار قرار گیرند.' },
  en: { title: 'Privacy', text: 'Health information should only be used for authorized care with controlled access. Login data, medical files and user records must never be placed in public code or shareable logs.' },
  ar: { title: 'الخصوصية', text: 'يجب استخدام المعلومات الصحية فقط لتقديم الرعاية المصرح بها مع التحكم في الوصول. لا يجوز وضع بيانات الدخول أو الملفات الطبية أو سجلات المستخدم في كود عام أو سجلات قابلة للمشاركة.' },
};

export default function PrivacyPage({ }) {
  const copy = copyMap[locale] || copyMap.fa;
  return <div className="medikal-platform"><Header /><main className="medikal-page"><div className="medikal-shell"><Card><Title>{copy.title}</Title><Paragraph>{copy.text}</Paragraph></Card></div></main><Footer /></div>;
}
