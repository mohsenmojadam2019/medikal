'use client';

import { useEffect, useRef, useState } from 'react';
import Link from 'next/link';
import { Alert, Avatar, Button, Card, Empty, Input, Space, Spin, Typography } from 'antd';
import { LoginOutlined, RobotOutlined, SendOutlined, UserOutlined } from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import { apiFetch, getApiErrorMessage, getStoredToken } from '@/lib/api/client';

const { Title, Text, Paragraph } = Typography;
const { TextArea } = Input;

const copyMap = {
  fa: {
    title: 'دستیار هوشمند سلامت', subtitle: 'برای راهنمایی اولیه درباره خدمات سلامت و مسیر مراجعه',
    disclaimer: 'پاسخ‌های این بخش آموزشی و راهنمای اولیه هستند و جایگزین تشخیص پزشک نیستند. برای وضعیت فوری از خدمات اورژانس محلی کمک بگیرید.',
    loginTitle: 'برای شروع وارد حساب شوید', loginText: 'گفت‌وگوهای سلامت به حساب شما متصل می‌شوند و این سرویس نیاز به ورود دارد.', login: 'ورود به حساب',
    empty: 'سؤال خود را بنویسید', placeholder: 'پیام شما…', send: 'ارسال', retry: 'اتصال دوباره',
    connection: 'ارتباط با دستیار برقرار نشد', waiting: 'در حال دریافت پاسخ…', welcome: 'سلام! درباره خدمات مدیکال یا مسیر مناسب مراجعه چه کمکی می‌توانم بکنم؟',
  },
  en: {
    title: 'AI health assistant', subtitle: 'Initial guidance about healthcare services and where to seek care',
    disclaimer: 'Responses are educational and provide initial guidance only. They do not replace a clinician. Contact local emergency services for urgent situations.',
    loginTitle: 'Sign in to start', loginText: 'Health conversations are linked to your account, so this service requires authentication.', login: 'Sign in',
    empty: 'Write your question', placeholder: 'Your message…', send: 'Send', retry: 'Reconnect',
    connection: 'Could not connect to the assistant', waiting: 'Getting a response…', welcome: 'Hello! How can I help you navigate Medikal services or choose the right type of care?',
  },
  ar: {
    title: 'المساعد الصحي الذكي', subtitle: 'إرشاد أولي حول الخدمات الصحية ومسار المراجعة المناسب',
    disclaimer: 'الإجابات تعليمية وإرشادية فقط ولا تغني عن تشخيص الطبيب. تواصل مع خدمات الطوارئ المحلية في الحالات العاجلة.',
    loginTitle: 'سجل الدخول للبدء', loginText: 'ترتبط المحادثات الصحية بحسابك، لذلك تتطلب الخدمة تسجيل الدخول.', login: 'تسجيل الدخول',
    empty: 'اكتب سؤالك', placeholder: 'رسالتك…', send: 'إرسال', retry: 'إعادة الاتصال',
    connection: 'تعذر الاتصال بالمساعد', waiting: 'جاري الحصول على الإجابة…', welcome: 'مرحباً! كيف يمكنني مساعدتك في خدمات ميديكال أو اختيار نوع الرعاية المناسب؟',
  },
};

export default function AIChatPage({ locale = 'fa' }) {
  const copy = copyMap[locale] || copyMap.fa;
  const [token, setToken] = useState(null);
  const [session, setSession] = useState(null);
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [connecting, setConnecting] = useState(true);
  const [error, setError] = useState('');
  const endRef = useRef(null);

  useEffect(() => {
    const storedToken = getStoredToken();
    setToken(storedToken);
    if (!storedToken) {
      setConnecting(false);
      return;
    }

    apiFetch('/api/v1/chat/active', { token: storedToken })
      .then((payload) => {
        setSession(payload.data || null);
        setMessages([{ id: 'welcome', role: 'assistant', content: copy.welcome }]);
      })
      .catch((requestError) => setError(getApiErrorMessage(requestError, copy.connection)))
      .finally(() => setConnecting(false));
  }, [copy.connection, copy.welcome]);

  useEffect(() => { endRef.current?.scrollIntoView({ behavior: 'smooth' }); }, [messages, loading]);

  const reconnect = async () => {
    if (!token) return;
    setConnecting(true); setError('');
    try {
      const payload = await apiFetch('/api/v1/chat/start', { method: 'POST', token, body: JSON.stringify({ model: 'qwen3.5:4b' }) });
      setSession(payload.data || null);
      setMessages([{ id: 'welcome', role: 'assistant', content: copy.welcome }]);
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, copy.connection));
    } finally { setConnecting(false); }
  };

  const sendMessage = async () => {
    const text = input.trim();
    if (!text || loading || !token) return;
    const userMessage = { id: `user-${Date.now()}`, role: 'user', content: text };
    setMessages((current) => [...current, userMessage]);
    setInput(''); setLoading(true); setError('');
    try {
      const payload = await apiFetch('/api/v1/chat/send', {
        method: 'POST', token,
        body: JSON.stringify({ message: text, session_id: session?.id }),
      });
      const answer = payload.data?.response || payload.data?.message || payload.message || '';
      setMessages((current) => [...current, { id: payload.data?.id || `assistant-${Date.now()}`, role: 'assistant', content: answer || copy.connection }]);
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, copy.connection));
    } finally { setLoading(false); }
  };

  return (
    <div className="medikal-platform">
      <Header />
      <main className="medikal-page"><div className="medikal-shell medikal-chat-shell">
        <div className="medikal-page-heading"><span className="medikal-eyebrow"><RobotOutlined /> {copy.subtitle}</span><Title level={1}>{copy.title}</Title></div>
        <Alert type="warning" showIcon message={copy.disclaimer} className="medikal-status-alert" />

        {!token ? (
          <Card className="medikal-login-card">
            <RobotOutlined className="medikal-login-card__icon" />
            <Title level={3}>{copy.loginTitle}</Title><Paragraph>{copy.loginText}</Paragraph>
            <Link href={`/login`}><Button type="primary" size="large" icon={<LoginOutlined />}>{copy.login}</Button></Link>
          </Card>
        ) : (
          <Card className="medikal-chat-card">
            {connecting ? <div className="medikal-chat-loading"><Spin size="large" /></div> : null}
            {!connecting && error && messages.length === 0 ? <Empty description={<><strong>{copy.connection}</strong><Text type="secondary">{error}</Text></>}><Button onClick={reconnect}>{copy.retry}</Button></Empty> : null}
            {!connecting && (messages.length > 0 || !error) ? (
              <>
                <div className="medikal-chat-messages">
                  {messages.map((message) => (
                    <div className={`medikal-chat-message ${message.role}`} key={message.id}>
                      <Avatar icon={message.role === 'user' ? <UserOutlined /> : <RobotOutlined />} />
                      <div>{message.content}</div>
                    </div>
                  ))}
                  {loading ? <div className="medikal-chat-message assistant"><Avatar icon={<RobotOutlined />} /><div><Spin size="small" /> {copy.waiting}</div></div> : null}
                  <div ref={endRef} />
                </div>
                {error ? <Alert type="error" showIcon message={error} closable onClose={() => setError('')} /> : null}
                <Space.Compact block className="medikal-chat-composer">
                  <TextArea value={input} onChange={(event) => setInput(event.target.value)} onPressEnter={(event) => { if (!event.shiftKey) { event.preventDefault(); sendMessage(); } }} autoSize={{ minRows: 1, maxRows: 4 }} placeholder={copy.placeholder} />
                  <Button type="primary" icon={<SendOutlined />} onClick={sendMessage} loading={loading}>{copy.send}</Button>
                </Space.Compact>
              </>
            ) : null}
          </Card>
        )}
      </div></main>
      <Footer />
    </div>
  );
}
