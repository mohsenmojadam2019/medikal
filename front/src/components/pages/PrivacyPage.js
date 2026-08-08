'use client';
import {Card,Typography} from 'antd';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import {useLanguage} from '@/lib/context/LanguageContext';
const {Title,Paragraph}=Typography;
const copyMap={fa:{title:'حریم خصوصی',text:'اطلاعات سلامت فقط برای ارائه خدمات مجاز و با دسترسی کنترل‌شده استفاده می‌شود.'},en:{title:'Privacy',text:'Health information is only used for authorized care with controlled access.'},ar:{title:'الخصوصية',text:'تستخدم المعلومات الصحية فقط للرعاية المصرح بها مع التحكم في الوصول.'}};
export default function PrivacyPage(){const{locale='fa'}=useLanguage();const copy=copyMap[locale]||copyMap.fa;return <div className="medikal-platform"><Header/><main className="medikal-page"><div className="medikal-shell"><Card><Title>{copy.title}</Title><Paragraph>{copy.text}</Paragraph></Card></div></main><Footer/></div>}
