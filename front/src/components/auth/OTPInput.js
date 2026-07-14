// /home/god/Videos/medikal/front/src/components/auth/OTPInput.jsx
'use client';

import { useState, useRef, useEffect } from 'react';
import { Input } from 'antd';

export default function OTPInput({ length = 4, onComplete, disabled = false }) {
  const [otp, setOtp] = useState(Array(length).fill(''));
  const inputs = useRef([]);

  const handleChange = (index, value) => {
    if (isNaN(value)) return;

    const newOtp = [...otp];
    newOtp[index] = value.slice(-1);
    setOtp(newOtp);

    if (value && index < length - 1) {
      inputs.current[index + 1].focus();
    }

    if (newOtp.every(v => v !== '')) {
      onComplete(newOtp.join(''));
    }
  };

  const handleKeyDown = (index, e) => {
    if (e.key === 'Backspace' && !otp[index] && index > 0) {
      inputs.current[index - 1].focus();
    }
  };

  const handlePaste = (e) => {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text').slice(0, length);
    if (!isNaN(pasted)) {
      const newOtp = [...otp];
      for (let i = 0; i < pasted.length; i++) {
        newOtp[i] = pasted[i];
      }
      setOtp(newOtp);
      if (newOtp.every(v => v !== '')) {
        onComplete(newOtp.join(''));
      }
      const lastIndex = Math.min(pasted.length - 1, length - 1);
      inputs.current[lastIndex]?.focus();
    }
  };

  // ✅ اتو فوکوس روی اولین input
  useEffect(() => {
    if (!disabled && inputs.current[0]) {
      inputs.current[0].focus();
    }
  }, [disabled]);

  return (
      <div style={{
        display: 'flex',
        gap: '8px',
        justifyContent: 'center',
        direction: 'ltr',
        padding: '8px 0'
      }}>
        {otp.map((digit, index) => (
            <Input
                key={index}
                ref={(el) => (inputs.current[index] = el)}
                value={digit}
                onChange={(e) => handleChange(index, e.target.value)}
                onKeyDown={(e) => handleKeyDown(index, e)}
                onPaste={handlePaste}
                disabled={disabled}
                style={{
                  width: '52px',
                  height: '60px',
                  textAlign: 'center',
                  fontSize: '24px',
                  fontWeight: 'bold',
                  borderRadius: '12px',
                  border: '2px solid #d9d9d9',
                  transition: 'border-color 0.3s',
                }}
                maxLength={1}
                autoFocus={index === 0}
            />
        ))}
      </div>
  );
}