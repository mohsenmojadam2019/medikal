'use client';

import { useState, useEffect, useRef } from 'react';
import { Input, Popover, Button, Space } from 'antd';
import { CalendarOutlined, CloseOutlined } from '@ant-design/icons';
import JalaliCalendar from 'react-jalali-datepicker';
import dayjs from 'dayjs';
import jalali from 'dayjs/plugin/jalali';
import 'dayjs/locale/fa';

dayjs.extend(jalali);
dayjs.locale('fa');

export default function JalaliDatePicker({
  value,
  onChange,
  placeholder = 'انتخاب تاریخ',
  format = 'jYYYY/jMM/jDD',
  disabled = false,
  size = 'middle',
  allowClear = true,
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedDate, setSelectedDate] = useState(null);
  const [displayValue, setDisplayValue] = useState('');
  const inputRef = useRef(null);

  // ===== تبدیل تاریخ شمسی به میلادی =====
  const convertJalaliToGregorian = (jalaliDate) => {
    if (!jalaliDate) return null;
    try {
      const [year, month, day] = jalaliDate.split('/').map(Number);
      const gregorian = dayjs(`${year}/${month}/${day}`, 'jYYYY/jMM/jDD');
      return gregorian.isValid() ? gregorian : null;
    } catch {
      return null;
    }
  };

  // ===== تبدیل میلادی به شمسی =====
  const convertGregorianToJalali = (gregorianDate) => {
    if (!gregorianDate) return '';
    try {
      const d = dayjs(gregorianDate);
      if (!d.isValid()) return '';
      return d.format('jYYYY/jMM/jDD');
    } catch {
      return '';
    }
  };

  // ===== مقداردهی اولیه =====
  useEffect(() => {
    if (value) {
      const jalaliStr = convertGregorianToJalali(value);
      setDisplayValue(jalaliStr);
      if (jalaliStr) {
        setSelectedDate(jalaliStr);
      }
    } else {
      setDisplayValue('');
      setSelectedDate(null);
    }
  }, [value]);

  // ===== انتخاب تاریخ =====
  const handleDateSelect = (jalaliDate) => {
    const gregorian = convertJalaliToGregorian(jalaliDate);
    setSelectedDate(jalaliDate);
    setDisplayValue(jalaliDate);
    setIsOpen(false);
    if (onChange && gregorian) {
      onChange(gregorian.toISOString());
    }
  };

  // ===== پاک کردن =====
  const handleClear = () => {
    setSelectedDate(null);
    setDisplayValue('');
    if (onChange) {
      onChange(null);
    }
    setIsOpen(false);
  };

  // ===== محتوای تقویم =====
  const calendarContent = (
    <div style={{ padding: '8px', direction: 'rtl' }}>
      <JalaliCalendar
        selected={selectedDate}
        onChange={handleDateSelect}
        locale="fa"
        showTodayButton
        todayLabel="امروز"
        showMonthPicker
        showYearPicker
      />
      <div style={{ textAlign: 'center', marginTop: 8 }}>
        <Button size="small" onClick={handleClear}>
          {allowClear ? 'پاک کردن' : 'بستن'}
        </Button>
      </div>
    </div>
  );

  return (
    <Popover
      content={calendarContent}
      trigger="click"
      open={isOpen}
      onOpenChange={setIsOpen}
      placement="bottomLeft"
      overlayStyle={{ maxWidth: 320 }}
    >
      <Input
        ref={inputRef}
        placeholder={placeholder}
        value={displayValue}
        readOnly
        disabled={disabled}
        size={size}
        prefix={<CalendarOutlined style={{ color: '#94a3b8' }} />}
        suffix={
          allowClear && displayValue ? (
            <CloseOutlined
              style={{ color: '#94a3b8', cursor: 'pointer' }}
              onClick={(e) => {
                e.stopPropagation();
                handleClear();
              }}
            />
          ) : null
        }
        style={{ cursor: 'pointer' }}
      />
    </Popover>
  );
}
