'use client';

import { useState, useEffect } from 'react';
import { Input, Modal, Button, Space, Tooltip, Select } from 'antd';
import { CalendarOutlined, LeftOutlined, RightOutlined, CloseOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import jalali from 'dayjs-jalali';

dayjs.extend(jalali);

const jMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
const jWeekDays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
const jWeekDaysShort = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

export default function JalaliDatePicker({
  value,
  onChange,
  placeholder = 'انتخاب تاریخ',
  disabled = false,
  allowClear = true,
}) {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedDate, setSelectedDate] = useState(null);
  const [tempDate, setTempDate] = useState(null);
  const [currentYear, setCurrentYear] = useState(dayjs().year());
  const [currentMonth, setCurrentMonth] = useState(dayjs().month() + 1);
  const [displayValue, setDisplayValue] = useState('');

  // مقداردهی اولیه
  useEffect(() => {
    if (value) {
      let parsed = null;
      if (typeof value === 'string') {
        parsed = dayjs(value);
      } else if (dayjs.isDayjs(value)) {
        parsed = value;
      }
      if (parsed && parsed.isValid()) {
        setSelectedDate(parsed);
        setDisplayValue(parsed.format('jYYYY/jMM/jDD'));
        setCurrentYear(parsed.year());
        setCurrentMonth(parsed.month() + 1);
      }
    } else {
      setSelectedDate(null);
      setDisplayValue('');
    }
  }, [value]);

  const getDaysInMonth = (year, month) => {
    return dayjs(`${year}-${month}-01`).daysInMonth();
  };

  const getFirstDayOfMonth = (year, month) => {
    return dayjs(`${year}-${month}-01`).day();
  };

  const handleDateSelect = (day, month, year) => {
    const date = dayjs(`${year}-${month}-${day}`);
    if (date.isValid()) {
      setTempDate(date);
    }
  };

  const handleConfirm = () => {
    if (tempDate && tempDate.isValid()) {
      setSelectedDate(tempDate);
      setDisplayValue(tempDate.format('jYYYY/jMM/jDD'));
      onChange?.(tempDate);
    }
    setIsModalOpen(false);
  };

  const goToPrevMonth = () => {
    if (currentMonth === 1) {
      setCurrentMonth(12);
      setCurrentYear(currentYear - 1);
    } else {
      setCurrentMonth(currentMonth - 1);
    }
  };

  const goToNextMonth = () => {
    if (currentMonth === 12) {
      setCurrentMonth(1);
      setCurrentYear(currentYear + 1);
    } else {
      setCurrentMonth(currentMonth + 1);
    }
  };

  const goToToday = () => {
    const today = dayjs();
    setCurrentYear(today.year());
    setCurrentMonth(today.month() + 1);
    setTempDate(today);
  };

  const clearDate = () => {
    setSelectedDate(null);
    setTempDate(null);
    setDisplayValue('');
    onChange?.(null);
    setIsModalOpen(false);
  };

  const openModal = () => {
    if (selectedDate) {
      setTempDate(selectedDate);
      setCurrentYear(selectedDate.year());
      setCurrentMonth(selectedDate.month() + 1);
    } else {
      const today = dayjs();
      setTempDate(today);
      setCurrentYear(today.year());
      setCurrentMonth(today.month() + 1);
    }
    setIsModalOpen(true);
  };

  const daysInMonth = getDaysInMonth(currentYear, currentMonth);
  const firstDayOfMonth = getFirstDayOfMonth(currentYear, currentMonth);

  const days = [];
  for (let i = 0; i < firstDayOfMonth; i++) {
    days.push(null);
  }
  for (let i = 1; i <= daysInMonth; i++) {
    const date = dayjs(`${currentYear}-${currentMonth}-${i}`);
    const isToday = date.format('jYYYY/jMM/jDD') === dayjs().format('jYYYY/jMM/jDD');
    const isSelected = tempDate && date.format('jYYYY/jMM/jDD') === tempDate.format('jYYYY/jMM/jDD');
    const isFriday = date.day() === 6;
    days.push({ day: i, date, isToday, isSelected, isFriday });
  }

  const weeks = [];
  for (let i = 0; i < days.length; i += 7) {
    weeks.push(days.slice(i, i + 7));
  }

  return (
    <>
      <Input
        placeholder={placeholder}
        value={displayValue}
        readOnly
        disabled={disabled}
        suffix={
          <Space size={4}>
            {allowClear && displayValue && (
              <CloseOutlined
                onClick={(e) => {
                  e.stopPropagation();
                  clearDate();
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
          <div style={{ textAlign: 'center' }}>
            <Space>
              <CalendarOutlined />
              <span>انتخاب تاریخ</span>
            </Space>
          </div>
        }
        open={isModalOpen}
        onCancel={() => setIsModalOpen(false)}
        footer={null}
        width={400}
        centered
      >
        <div style={{ padding: '8px 0' }}>
          {/* نمایش تاریخ انتخاب شده */}
          {tempDate && tempDate.isValid() && (
            <div style={{
              marginBottom: 16,
              padding: 8,
              background: '#dbeafe',
              borderRadius: 8,
              textAlign: 'center',
              fontSize: 14,
              fontWeight: 600,
              color: '#1e40af'
            }}>
              {tempDate.format('jYYYY/jMM/jDD')}
            </div>
          )}

          {/* هدر تقویم */}
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
              {jMonths[currentMonth - 1]} {currentYear}
            </span>
            <Button type="text" icon={<LeftOutlined />} onClick={goToNextMonth} />
          </div>

          {/* روزهای هفته */}
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(7, 1fr)',
            marginBottom: 8,
            padding: '8px 0',
            borderBottom: '1px solid #f0f0f0'
          }}>
            {jWeekDaysShort.map((day, idx) => (
              <div key={idx} style={{
                textAlign: 'center',
                fontWeight: 'bold',
                color: idx === 6 ? '#ef4444' : '#666',
                fontSize: 12
              }}>
                <Tooltip title={jWeekDays[idx]}>
                  <span>{day}</span>
                </Tooltip>
              </div>
            ))}
          </div>

          {/* روزهای ماه */}
          <div style={{ marginBottom: 16 }}>
            {weeks.map((week, weekIdx) => (
              <div key={weekIdx} style={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', marginBottom: 4 }}>
                {week.map((day, dayIdx) => (
                  <div
                    key={dayIdx}
                    onClick={() => day && handleDateSelect(day.day, currentMonth, currentYear)}
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

          {/* دکمه‌های پایین */}
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
}
