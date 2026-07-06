'use client';

import { useState, useEffect } from 'react';
import { Button, Card, Typography } from 'antd';
import { LeftOutlined, RightOutlined } from '@ant-design/icons';

const { Text } = Typography;

const PersianCalendar = ({ value, onChange, disabledDate }) => {
  const today = new Date();
  
  const [currentDate, setCurrentDate] = useState(() => {
    if (value && value instanceof Date && !isNaN(value)) {
      return new Date(value.getFullYear(), value.getMonth(), 1);
    }
    return new Date(today.getFullYear(), today.getMonth(), 1);
  });
  
  const [selectedDate, setSelectedDate] = useState(() => {
    if (value && value instanceof Date && !isNaN(value)) {
      return new Date(value);
    }
    return null;
  });

  // دریافت نام ماه شمسی
  const getPersianMonthName = (date) => {
    if (!date || !(date instanceof Date) || isNaN(date)) return '';
    try {
      const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { month: 'long' });
      return formatter.format(date);
    } catch {
      return '';
    }
  };

  // دریافت سال شمسی
  const getPersianYear = (date) => {
    if (!date || !(date instanceof Date) || isNaN(date)) return '';
    try {
      const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { year: 'numeric' });
      return formatter.format(date);
    } catch {
      return '';
    }
  };

  // دریافت روز شمسی
  const getPersianDay = (date) => {
    if (!date || !(date instanceof Date) || isNaN(date)) return '';
    try {
      const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { day: 'numeric' });
      return formatter.format(date);
    } catch {
      return '';
    }
  };

  // دریافت نام روز هفته به فارسی
  const getPersianWeekday = (date) => {
    if (!date || !(date instanceof Date) || isNaN(date)) return '';
    try {
      const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { weekday: 'long' });
      return formatter.format(date);
    } catch {
      return '';
    }
  };

  // دریافت ایندکس روز هفته (0=شنبه, 6=جمعه)
  const getWeekDayIndex = (date) => {
    if (!date || !(date instanceof Date) || isNaN(date)) return 0;
    // در جاوااسکریپت: 0=یکشنبه, 1=دوشنبه, ..., 6=شنبه
    const day = date.getDay();
    // تبدیل به: 0=شنبه, 1=یکشنبه, ..., 6=جمعه
    return day === 6 ? 0 : day + 1;
  };

  // دریافت اولین روز ماه (0=شنبه, 6=جمعه)
  const getFirstDayOfMonth = (date) => {
    if (!date || !(date instanceof Date) || isNaN(date)) return 0;
    const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    return getWeekDayIndex(firstDay);
  };

  // دریافت تعداد روزهای ماه
  const getDaysInMonth = (date) => {
    if (!date || !(date instanceof Date) || isNaN(date)) return 31;
    return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
  };

  const goToPrevMonth = () => {
    const newDate = new Date(currentDate);
    newDate.setMonth(newDate.getMonth() - 1);
    setCurrentDate(newDate);
  };

  const goToNextMonth = () => {
    const newDate = new Date(currentDate);
    newDate.setMonth(newDate.getMonth() + 1);
    setCurrentDate(newDate);
  };

  const selectDay = (day) => {
    const selected = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
    if (disabledDate && disabledDate(selected)) {
      return;
    }
    setSelectedDate(selected);
    if (onChange) {
      onChange(selected);
    }
  };

  const isToday = (day) => {
    const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
    return date.getFullYear() === today.getFullYear() && 
           date.getMonth() === today.getMonth() && 
           date.getDate() === today.getDate();
  };

  const isSelected = (day) => {
    if (!selectedDate) return false;
    return day === selectedDate.getDate() && 
           currentDate.getMonth() === selectedDate.getMonth() && 
           currentDate.getFullYear() === selectedDate.getFullYear();
  };

  const isDisabled = (day) => {
    const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
    if (disabledDate) {
      return disabledDate(date);
    }
    return false;
  };

  // ساخت آرایه روزهای ماه
  const buildDays = () => {
    const daysInMonth = getDaysInMonth(currentDate);
    const startOffset = getFirstDayOfMonth(currentDate); // 0=شنبه
    
    const days = [];
    
    // روزهای خالی قبل از شروع ماه
    for (let i = 0; i < startOffset; i++) {
      days.push(null);
    }
    
    // روزهای ماه
    for (let i = 1; i <= daysInMonth; i++) {
      days.push(i);
    }
    
    return days;
  };

  // نام روزهای هفته به فارسی (از شنبه شروع می‌شود)
  const weekDays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
  
  const days = buildDays();

  useEffect(() => {
    if (value && value instanceof Date && !isNaN(value)) {
      setSelectedDate(new Date(value));
      setCurrentDate(new Date(value.getFullYear(), value.getMonth(), 1));
    }
  }, [value]);

  return (
    <Card 
      style={{ 
        borderRadius: '16px', 
        border: '1px solid #e8e8e8',
        direction: 'rtl',
        boxShadow: '0 2px 8px rgba(0,0,0,0.06)',
      }}
      styles={{ body: { padding: '20px' } }}
    >
      <div style={{ 
        display: 'flex', 
        justifyContent: 'space-between', 
        alignItems: 'center',
        marginBottom: '20px',
        padding: '0 4px'
      }}>
        <Button 
          type="text"
          icon={<RightOutlined />} 
          onClick={goToPrevMonth}
          style={{ fontSize: '16px' }}
        />
        <span style={{ 
          fontWeight: 'bold', 
          fontSize: '18px',
          color: '#1a1a2e'
        }}>
          {getPersianMonthName(currentDate)} {getPersianYear(currentDate)}
        </span>
        <Button 
          type="text"
          icon={<LeftOutlined />} 
          onClick={goToNextMonth}
          style={{ fontSize: '16px' }}
        />
      </div>

      <div style={{ 
        display: 'grid', 
        gridTemplateColumns: 'repeat(7, 1fr)',
        gap: '4px',
        marginBottom: '12px',
        textAlign: 'center'
      }}>
        {weekDays.map((day, index) => (
          <div key={index} style={{ 
            fontWeight: '600', 
            fontSize: '12px',
            color: index === 6 ? '#e74c3c' : '#666',
            padding: '4px 0'
          }}>
            {day}
          </div>
        ))}
      </div>

      <div style={{ 
        display: 'grid', 
        gridTemplateColumns: 'repeat(7, 1fr)',
        gap: '4px'
      }}>
        {days.map((day, index) => {
          if (day === null) {
            return <div key={`empty-${index}`} style={{ padding: '6px' }} />;
          }

          const dateObj = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
          const isTodayFlag = isToday(day);
          const isSelectedFlag = isSelected(day);
          const isDisabledFlag = isDisabled(day);
          const persianDay = getPersianDay(dateObj);
          const dayOfWeekIndex = getWeekDayIndex(dateObj);
          const isWeekend = dayOfWeekIndex === 6;

          return (
            <div
              key={index}
              onClick={() => !isDisabledFlag && selectDay(day)}
              style={{
                padding: '8px 4px',
                textAlign: 'center',
                cursor: isDisabledFlag ? 'not-allowed' : 'pointer',
                borderRadius: '12px',
                backgroundColor: isSelectedFlag ? '#2563eb' : 
                                isTodayFlag ? '#e8f0fe' : 'transparent',
                color: isDisabledFlag ? '#ccc' :
                       isSelectedFlag ? '#ffffff' : 
                       isTodayFlag ? '#2563eb' : 
                       isWeekend ? '#e74c3c' : '#1a1a2e',
                fontWeight: isSelectedFlag || isTodayFlag ? '700' : '400',
                fontSize: '15px',
                transition: 'all 0.2s ease',
                border: isTodayFlag && !isSelectedFlag ? '2px solid #2563eb' : 'none',
                position: 'relative',
              }}
              onMouseEnter={(e) => {
                if (!isDisabledFlag && !isSelectedFlag) {
                  e.target.style.backgroundColor = '#f0f5ff';
                }
              }}
              onMouseLeave={(e) => {
                if (!isDisabledFlag && !isSelectedFlag) {
                  e.target.style.backgroundColor = isTodayFlag ? '#e8f0fe' : 'transparent';
                }
              }}
            >
              {persianDay}
              {isTodayFlag && !isSelectedFlag && (
                <span style={{
                  position: 'absolute',
                  bottom: '2px',
                  left: '50%',
                  transform: 'translateX(-50%)',
                  width: '4px',
                  height: '4px',
                  borderRadius: '50%',
                  backgroundColor: '#2563eb',
                }} />
              )}
            </div>
          );
        })}
      </div>

      <div style={{ 
        marginTop: '16px', 
        display: 'flex', 
        justifyContent: 'center',
        gap: '8px'
      }}>
        <Button 
          type="primary"
          ghost
          size="small"
          onClick={() => {
            const todayDate = new Date();
            setCurrentDate(new Date(todayDate.getFullYear(), todayDate.getMonth(), 1));
            setSelectedDate(todayDate);
            if (onChange) {
              onChange(todayDate);
            }
          }}
          style={{ borderRadius: '8px' }}
        >
          امروز
        </Button>
      </div>

      {selectedDate && (
        <div style={{ 
          marginTop: '12px',
          padding: '8px 12px',
          background: '#f8fafc',
          borderRadius: '8px',
          textAlign: 'center'
        }}>
          <Text type="secondary" style={{ fontSize: '13px' }}>
            تاریخ انتخاب شده: <strong>{getPersianWeekday(selectedDate)} {getPersianDay(selectedDate)} {getPersianMonthName(selectedDate)} {getPersianYear(selectedDate)}</strong>
          </Text>
        </div>
      )}
    </Card>
  );
};

export default PersianCalendar;
