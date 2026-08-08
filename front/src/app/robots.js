import {siteUrl} from '@/lib/publicRoutes';
export default function robots(){return{rules:{userAgent:'*',allow:'/',disallow:['/api/','/profile/','/appointments/','/pharmacy/checkout','/pharmacy/orders/']},sitemap:siteUrl('/sitemap.xml'),host:siteUrl('/')}}
