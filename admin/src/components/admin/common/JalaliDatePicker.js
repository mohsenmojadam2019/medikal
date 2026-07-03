// src/components/admin/common/JalaliDatePicker.js
'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import { Input, Modal, Button, Space, Tooltip } from 'antd';
import { CalendarOutlined, LeftOutlined, RightOutlined, CloseOutlined } from '@ant-design/icons';
import moment from 'moment-jalaali';

moment.loadPersian({ dialect: 'persian-modern' });

const JalaliDatePicker = ({
                            value,
                            onChange,
                            placeholder = 'انتخاب تاریخ',
                            disabled = false,
                            allowClear = true,
                            format = 'jYYYY/jMM/jDD'
                          }) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedDate, setSelectedDate] = useState(null);
  const [tempSelectedDate, setTempSelectedDate] = useState(null);
  const [currentMonth, setCurrentMonth] = useState(moment());
  const [displayValue, setDisplayValue] = useState('');
  const [inputValue, setInputValue] = useState('');
  const inputRef = useRef(null);

  // نام ماه‌های جلالی
  const jalaaliMonths = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
  ];

  // نام روزهای هفته
  const weekDays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
  const weekDaysShort = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

  // مقداردهی اولیه
  useEffect(() => {
    if (value) {
      let parsed = null;
      if (typeof value === 'string') {
        parsed = moment(value, 'jYYYY/jMM/jDD');
      } else if (moment.isMoment(value)) {
        parsed = value;
      }
      if (parsed && parsed.isValid()) {
        setSelectedDate(parsed);
        const formatted = parsed.format(format);
        setDisplayValue(formatted);
        setInputValue(formatted);
        setCurrentMonth(parsed);
      }
    } else {
      setSelectedDate(null);
      setDisplayValue('');
      setInputValue('');
    }
  }, [value, format]);

  // گرفتن روزهای ماه جاری
  const getDaysInMonth = useCallback(() => {
    const year = currentMonth.jYear();
    const month = currentMonth.jMonth();
    const daysInMonth = moment.jDaysInMonth(year, month + 1);
    const firstDayOfMonth = moment(`${year}/${month + 1}/1`, 'jYYYY/jM/jD');
    const startDayOfWeek = firstDayOfMonth.day();

    const days = [];

    for (let i = 0; i < startDayOfWeek; i++) {
      days.push(null);
    }

    for (let i = 1; i <= daysInMonth; i++) {
      const date = moment(`${year}/${month + 1}/${i}`, 'jYYYY/jM/jD');
      const isToday = date.format('jYYYY/jMM/jDD') === moment().format('jYYYY/jMM/jDD');
      const isSelected = tempSelectedDate && date.format('jYYYY/jMM/jDD') === tempSelectedDate.format('jYYYY/jMM/jDD');
      const isFriday = date.day() === 6;

      days.push({
        day: i,
        date: date,
        isToday,
        isSelected,
        isFriday,
      });
    }

    return days;
  }, [currentMonth, tempSelectedDate]);

  const handleDateSelect = (dayObj) => {
    if (!dayObj) return;
    setTempSelectedDate(dayObj.date);
  };

  const handleConfirm = () => {
    if (tempSelectedDate) {
      setSelectedDate(tempSelectedDate);
      const formatted = tempSelectedDate.format(format);
      setDisplayValue(formatted);
      setInputValue(formatted);
      onChange?.(tempSelectedDate);
    }
    setIsModalOpen(false);
  };

  const goToPrevMonth = () => {
    const newDate = moment(currentMonth).subtract(1, 'jMonth');
    setCurrentMonth(newDate);
  };

  const goToNextMonth = () => {
    const newDate = moment(currentMonth).add(1, 'jMonth');
    setCurrentMonth(newDate);
  };

  const goToToday = () => {
    const today = moment();
    setCurrentMonth(today);
    setTempSelectedDate(today);
  };

  const clearDate = () => {
    setSelectedDate(null);
    setTempSelectedDate(null);
    setDisplayValue('');
    setInputValue('');
    onChange?.(null);
    setIsModalOpen(false);
  };

  const openModal = () => {
    setTempSelectedDate(selectedDate);
    if (selectedDate) {
      setCurrentMonth(selectedDate);
    } else {
      setCurrentMonth(moment());
    }
    setIsModalOpen(true);
  };

  // ===== مدیریت ورودی دستی =====
  const handleInputChange = (e) => {
    const val = e.target.value;
    setInputValue(val);

    // اگر مقدار خالی بود
    if (!val.trim()) {
      setSelectedDate(null);
      setDisplayValue('');
      onChange?.(null);
      return;
    }

    // بررسی فرمت 1405/01/12
    const regex = /^(\d{4})\/(\d{1,2})\/(\d{1,2})$/;
    const match = val.match(regex);

    if (match) {
      const year = parseInt(match[1]);
      const month = parseInt(match[2]);
      const day = parseInt(match[3]);

      // بررسی اعتبار سال
      if (year < 1300 || year > 1500) return;
      if (month < 1 || month > 12) return;
      if (day < 1 || day > 31) return;

      const parsed = moment(`${year}/${month}/${day}`, 'jYYYY/jM/jD');
      if (parsed && parsed.isValid()) {
        setSelectedDate(parsed);
        setDisplayValue(parsed.format(format));
        onChange?.(parsed);
        return;
      }
    }

    // اگر فرمت نامعتبر بود ولی قبلاً تاریخی انتخاب شده بود
    if (selectedDate) {
      setSelectedDate(null);
      setDisplayValue('');
      onChange?.(null);
    }
  };

  const handleInputBlur = () => {
    if (selectedDate) {
      setInputValue(selectedDate.format(format));
    } else {
      setInputValue('');
    }
  };

  const days = getDaysInMonth();
  const year = currentMonth.jYear();
  const month = currentMonth.jMonth();

  const weeks = [];
  for (let i = 0; i < days.length; i += 7) {
    weeks.push(days.slice(i, i + 7));
  }

  return (
      <>
        <Input
            ref={inputRef}
            placeholder={placeholder}
            value={inputValue}
            onChange={handleInputChange}
            onBlur={handleInputBlur}
            disabled={disabled}
            suffix={
              <Space size={4}>
                {allowClear && inputValue && (
                    <CloseOutlined
                        onClick={(e) => {
                          e.stopPropagation();
                          clearDate();
                          inputRef.current?.focus();
                        }}
                        style={{ cursor: 'pointer', color: '#999' }}
                    />
                )}
                <CalendarOutlined
                    onClick={(e) => {
                      e.stopPropagation();
                      if (!disabled) openModal();
                    }}
                    style={{ cursor: disabled ? 'not-allowed' : 'pointer' }}
                />
              </Space>
            }
            onClick={() => !disabled && openModal()}
            style={{ cursor: disabled ? 'not-allowed' : 'pointer' }}
        />

        <Modal
            title={
              <div style={{ textAlign: 'center', direction: 'rtl' }}>
                <Space>
                  <CalendarOutlined />
                  <span>انتخاب تاریخ</span>
                </Space>
              </div>
            }
            open={isModalOpen}
            onCancel={() => setIsModalOpen(false)}
            footer={null}
            width={360}
            centered
            destroyOnHidden
        >
          <div style={{ direction: 'rtl', textAlign: 'center', padding: '8px 0' }}>
            {/* نمایش تاریخ انتخاب شده */}
            {tempSelectedDate && (
                <div style={{
                  marginBottom: 16,
                  padding: 8,
                  background: '#dbeafe',
                  borderRadius: 8,
                  fontSize: 14,
                  fontWeight: 600,
                  color: '#1e40af'
                }}>
                  {tempSelectedDate.format('jYYYY/jMM/jDD')}
                </div>
            )}

            <div style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              marginBottom: 16,
              padding: '8px 0',
              borderBottom: '1px solid #f0f0f0'
            }}>
              <Button type="text" icon={<RightOutlined />} onClick={goToPrevMonth} />
              <span style={{ fontSize: 16, fontWeight: 'bold' }}>
              {jalaaliMonths[month]} {year}
            </span>
              <Button type="text" icon={<LeftOutlined />} onClick={goToNextMonth} />
            </div>

            <div style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(7, 1fr)',
              marginBottom: 8,
              padding: '8px 0',
              borderBottom: '1px solid #f0f0f0'
            }}>
              {weekDaysShort.map((day, idx) => (
                  <div key={idx} style={{
                    textAlign: 'center',
                    fontWeight: 'bold',
                    color: idx === 6 ? '#ef4444' : '#666',
                    fontSize: 12
                  }}>
                    <Tooltip title={weekDays[idx]}>
                      <span>{day}</span>
                    </Tooltip>
                  </div>
              ))}
            </div>

            <div style={{ marginBottom: 16 }}>
              {weeks.map((week, weekIdx) => (
                  <div key={weekIdx} style={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', marginBottom: 4 }}>
                    {week.map((day, dayIdx) => (
                        <div
                            key={dayIdx}
                            onClick={() => day && handleDateSelect(day)}
                            style={{
                              textAlign: 'center',
                              padding: '8px 4px',
                              cursor: day ? 'pointer' : 'default',
                              borderRadius: '50%',
                              backgroundColor: day?.isSelected ? '#3b82f6' : (day?.isToday ? '#e6f7ff' : 'transparent'),
                              color: day?.isSelected ? 'white' : (day?.isFriday ? '#ef4444' : (day?.isToday ? '#3b82f6' : '#333')),
                              fontWeight: day?.isSelected ? 'bold' : (day?.isFriday ? 'bold' : 'normal'),
                              transition: 'all 0.2s',
                            }}
                            onMouseEnter={(e) => {
                              if (day && !day.isSelected) {
                                e.currentTarget.style.backgroundColor = '#f5f5f5';
                              }
                            }}
                            onMouseLeave={(e) => {
                              if (day && !day.isSelected) {
                                e.currentTarget.style.backgroundColor = '';
                              }
                            }}
                        >
                          {day?.day || ''}
                        </div>
                    ))}
                  </div>
              ))}
            </div>

            <div style={{
              display: 'flex',
              justifyContent: 'space-between',
              marginTop: 16,
              paddingTop: 16,
              borderTop: '1px solid #f0f0f0'
            }}>
              <Button onClick={goToToday} size="small">
                امروز
              </Button>
              <Button onClick={clearDate} size="small" danger>
                پاک کردن
              </Button>
              <Button type="primary" onClick={handleConfirm} size="small">
                تأیید
              </Button>
            </div>
          </div>
        </Modal>
      </>
  );
};

export default JalaliDatePicker;