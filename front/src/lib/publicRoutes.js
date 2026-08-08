const map={آ:'a',ا:'a',ب:'b',پ:'p',ت:'t',ث:'s',ج:'j',چ:'ch',ح:'h',خ:'kh',د:'d',ذ:'z',ر:'r',ز:'z',ژ:'zh',س:'s',ش:'sh',ص:'s',ض:'z',ط:'t',ظ:'z',ع:'a',غ:'gh',ف:'f',ق:'gh',ک:'k',گ:'g',ل:'l',م:'m',ن:'n',و:'v',ه:'h',ی:'y'};
export function slugify(v=''){return String(v).trim().split('').map(c=>map[c]??c).join('').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')||'profile'}
export const doctorName=d=>d?.full_name||d?.user?.full_name||d?.user?.name||d?.name||'پزشک';
export const doctorSlug=d=>d?.slug||slugify(doctorName(d).replace(/^دکتر\s+/,''));
export const productSlug=p=>p?.slug||slugify(p?.name);
export const siteUrl=(p='/')=>new URL(p,process.env.NEXT_PUBLIC_SITE_URL||'https://doctorweb.ir').toString();
