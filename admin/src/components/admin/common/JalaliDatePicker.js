// src/components/admin/common/JalaliDatePicker.js
'use client';

import { useState, useEffect, useCallback } from 'react';
import { Input, Modal, Button, Space, Tooltip, Select } from 'antd';
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

  // ===== state برای سلکت‌ها =====
  const [selectedYear, setSelectedYear] = useState(null);
  const [selectedMonth, setSelectedMonth] = useState(null);
  const [selectedDay, setSelectedDay] = useState(null);

  const jalaaliMonths = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
  ];

  const weekDays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
  const weekDaysShort = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

  // ===== مقداردهی اولیه =====
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
        setDisplayValue(parsed.format(format));
        setCurrentMonth(parsed);
        setSelectedYear(parsed.jYear());
        setSelectedMonth(parsed.jMonth() + 1);
        setSelectedDay(parsed.jDate());
      }
    } else {
      setSelectedDate(null);
      setDisplayValue('');
      setSelectedYear(null);
      setSelectedMonth(null);
      setSelectedDay(null);
    }
  }, [value, format]);

  // ===== گرفتن روزهای ماه =====
  const getDaysInMonth = (year, month) => {
    return moment.jDaysInMonth(year, month);
  };

  // ===== تغییر سال =====
  const handleYearChange = (year) => {
    setSelectedYear(year);
    if (selectedMonth && selectedDay) {
      const daysInMonth = getDaysInMonth(year, selectedMonth);
      if (selectedDay > daysInMonth) {
        setSelectedDay(daysInMonth);
      }
      const date = moment(`${year}/${selectedMonth}/${Math.min(selectedDay, daysInMonth)}`, 'jYYYY/jM/jD');
      if (date.isValid()) {
        setTempSelectedDate(date);
        setCurrentMonth(date);
      }
    }
  };

  // ===== تغییر ماه =====
  const handleMonthChange = (month) => {
    setSelectedMonth(month);
    if (selectedYear && selectedDay) {
      const daysInMonth = getDaysInMonth(selectedYear, month);
      if (selectedDay > daysInMonth) {
        setSelectedDay(daysInMonth);
      }
      const date = moment(`${selectedYear}/${month}/${Math.min(selectedDay, daysInMonth)}`, 'jYYYY/jM/jD');
      if (date.isValid()) {
        setTempSelectedDate(date);
        setCurrentMonth(date);
      }
    } else if (selectedYear) {
      const date = moment(`${selectedYear}/${month}/1`, 'jYYYY/jM/jD');
      if (date.isValid()) {
        setTempSelectedDate(date);
        setCurrentMonth(date);
        setSelectedDay(1);
      }
    }
  };

  // ===== تغییر روز =====
  const handleDayChange = (day) => {
    setSelectedDay(day);
    if (selectedYear && selectedMonth) {
      const date = moment(`${selectedYear}/${selectedMonth}/${day}`, 'jYYYY/jM/jD');
      if (date.isValid()) {
        setTempSelectedDate(date);
        setCurrentMonth(date);
      }
    }
  };

  // ===== از تقویم انتخاب کنه =====
  const handleDateSelect = (dayObj) => {
    if (!dayObj) return;
    setTempSelectedDate(dayObj.date);
    setSelectedYear(dayObj.date.jYear());
    setSelectedMonth(dayObj.date.jMonth() + 1);
    setSelectedDay(dayObj.date.jDate());
  };

  // ===== تأیید =====
  const handleConfirm = () => {
    if (tempSelectedDate && tempSelectedDate.isValid()) {
      setSelectedDate(tempSelectedDate);
      setDisplayValue(tempSelectedDate.format(format));
      onChange?.(tempSelectedDate);
    }
    setIsModalOpen(false);
  };

  const goToPrevMonth = () => {
    const newDate = moment(currentMonth).subtract(1, 'jMonth');
    setCurrentMonth(newDate);
    setSelectedYear(newDate.jYear());
    setSelectedMonth(newDate.jMonth() + 1);
  };

  const goToNextMonth = () => {
    const newDate = moment(currentMonth).add(1, 'jMonth');
    setCurrentMonth(newDate);
    setSelectedYear(newDate.jYear());
    setSelectedMonth(newDate.jMonth() + 1);
  };

  const goToToday = () => {
    const today = moment();
    setCurrentMonth(today);
    setTempSelectedDate(today);
    setSelectedYear(today.jYear());
    setSelectedMonth(today.jMonth() + 1);
    setSelectedDay(today.jDate());
  };

  const clearDate = () => {
    setSelectedDate(null);
    setTempSelectedDate(null);
    setDisplayValue('');
    setSelectedYear(null);
    setSelectedMonth(null);
    setSelectedDay(null);
    onChange?.(null);
    setIsModalOpen(false);
  };

  const openModal = () => {
    if (selectedDate) {
      setTempSelectedDate(selectedDate);
      setCurrentMonth(selectedDate);
      setSelectedYear(selectedDate.jYear());
      setSelectedMonth(selectedDate.jMonth() + 1);
      setSelectedDay(selectedDate.jDate());
    } else {
      const today = moment();
      setTempSelectedDate(today);
      setCurrentMonth(today);
      setSelectedYear(today.jYear());
      setSelectedMonth(today.jMonth() + 1);
      setSelectedDay(today.jDate());
    }
    setIsModalOpen(true);
  };

  // ===== ساخت لیست‌ها =====
  const monthOptions = Array.from({ length: 12 }, (_, i) => ({
    value: i + 1,
    label: jalaaliMonths[i]
  }));

  const yearOptions = Array.from({ length: 201 }, (_, i) => ({
    value: 1300 + i,
    label: (1300 + i).toString()
  }));

  const getDayOptions = () => {
    if (!selectedYear || !selectedMonth) {
      return Array.from({ length: 31 }, (_, i) => ({ value: i + 1, label: i + 1 }));
    }
    const daysInMonth = getDaysInMonth(selectedYear, selectedMonth);
    return Array.from({ length: daysInMonth }, (_, i) => ({ value: i + 1, label: i + 1 }));
  };

  // ===== تقویم =====
  const year = currentMonth.jYear();
  const month = currentMonth.jMonth();
  const daysInMonth = getDaysInMonth(year, month + 1);
  const firstDayOfMonth = moment(`${year}/${month + 1}/1`, 'jYYYY/jM/jD').day();

  const days = [];
  for (let i = 0; i < firstDayOfMonth; i++) {
    days.push(null);
  }
  for (let i = 1; i <= daysInMonth; i++) {
    const date = moment(`${year}/${month + 1}/${i}`, 'jYYYY/jM/jD');
    const isToday = date.format('jYYYY/jMM/jDD') === moment().format('jYYYY/jMM/jDD');
    const isSelected = tempSelectedDate && date.format('jYYYY/jMM/jDD') === tempSelectedDate.format('jYYYY/jMM/jDD');
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
            width={420}
            centered
            destroyOnClose
        >
          <div style={{ direction: 'rtl', padding: '8px 0' }}>
            {/* ===== سلکت‌ها ===== */}
            <div style={{
              display: 'flex',
              gap: 12,
              marginBottom: 16,
            }}>
              <div style={{ flex: 2 }}>
                <div style={{ fontSize: 12, color: '#64748b', marginBottom: 4 }}>سال</div>
                <Select
                    value={selectedYear}
                    onChange={handleYearChange}
                    placeholder="سال"
                    showSearch
                    optionFilterProp="label"
                    style={{ width: '100%' }}
                    options={yearOptions}
                    filterOption={(input, option) =>
                        option.label.toString().includes(input)
                    }
                />
              </div>
              <div style={{ flex: 1.5 }}>
                <div style={{ fontSize: 12, color: '#64748b', marginBottom: 4 }}>ماه</div>
                <Select
                    value={selectedMonth}
                    onChange={handleMonthChange}
                    placeholder="ماه"
                    style={{ width: '100%' }}
                    options={monthOptions}
                />
              </div>
              <div style={{ flex: 1 }}>
                <div style={{ fontSize: 12, color: '#64748b', marginBottom: 4 }}>روز</div>
                <Select
                    value={selectedDay}
                    onChange={handleDayChange}
                    placeholder="روز"
                    style={{ width: '100%' }}
                    options={getDayOptions()}
                />
              </div>
            </div>

            {/* ===== تاریخ انتخاب شده ===== */}
            {tempSelectedDate && tempSelectedDate.isValid() && (
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
                  {tempSelectedDate.format('jYYYY/jMM/jDD')}
                </div>
            )}

            {/* ===== تقویم ===== */}
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