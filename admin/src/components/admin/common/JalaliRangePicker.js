// src/components/admin/common/JalaliRangePicker.js
'use client';

import { useState, useEffect, useCallback } from 'react';
import { Input, Modal, Button, Space, Alert, Tag, Tooltip } from 'antd';
import {
    CalendarOutlined,
    CloseOutlined,
    LeftOutlined,
    RightOutlined,
    SwapOutlined,
    ClearOutlined,
    CheckOutlined,
    StarOutlined,
    ThunderboltOutlined
} from '@ant-design/icons';
import moment from 'moment-jalaali';

moment.loadPersian({ dialect: 'persian-modern' });

// آرایه ماه‌های شمسی
const jMonths = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
];

// آرایه روزهای هفته
const jWeekDays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

const JalaliRangePicker = ({
                               value,
                               onChange,
                               placeholder = ['از تاریخ', 'تا تاریخ'],
                               disabled = false,
                               allowClear = true,
                               showQuickSelect = true
                           }) => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [error, setError] = useState('');
    const [activePicker, setActivePicker] = useState('start');
    const [currentYear, setCurrentYear] = useState(moment().jYear());
    const [currentMonth, setCurrentMonth] = useState(moment().jMonth() + 1);
    const [hoverDate, setHoverDate] = useState(null);

    useEffect(() => {
        if (value && Array.isArray(value) && value.length === 2) {
            setStartDate(value[0] || '');
            setEndDate(value[1] || '');
        } else if (value && typeof value === 'string') {
            setStartDate(value);
            setEndDate('');
        } else {
            setStartDate('');
            setEndDate('');
        }
    }, [value]);

    // بررسی اعتبار تاریخ جلالی
    const isValidJalaliDate = (dateStr) => {
        if (!dateStr || dateStr.length !== 10) return false;
        const parts = dateStr.split('/');
        if (parts.length !== 3) return false;
        const year = parseInt(parts[0]);
        const month = parseInt(parts[1]);
        const day = parseInt(parts[2]);
        if (year < 1300 || year > 1500) return false;
        if (month < 1 || month > 12) return false;
        if (day < 1 || day > 31) return false;

        const testDate = moment(`${year}/${month}/${day}`, 'jYYYY/jM/jD');
        return testDate.isValid();
    };

    // مقایسه دو تاریخ (برمیگردونه true اگه date1 > date2)
    const isDate1GreaterThanDate2 = (date1, date2) => {
        if (!date1 || !date2) return false;
        const d1 = moment(date1, 'jYYYY/jMM/jDD');
        const d2 = moment(date2, 'jYYYY/jMM/jDD');
        return d1.isAfter(d2);
    };

    const getDaysInMonth = (year, month) => {
        return moment.jDaysInMonth(year, month);
    };

    const getFirstDayOfMonth = (year, month) => {
        const firstDay = moment(`${year}/${month}/1`, 'jYYYY/jM/jD');
        return firstDay.day();
    };

    const goToToday = () => {
        const today = moment();
        setCurrentYear(today.jYear());
        setCurrentMonth(today.jMonth() + 1);
    };

    const changeMonth = (offset) => {
        let newMonth = currentMonth + offset;
        let newYear = currentYear;

        if (newMonth > 12) {
            newMonth = 1;
            newYear++;
        } else if (newMonth < 1) {
            newMonth = 12;
            newYear--;
        }

        setCurrentMonth(newMonth);
        setCurrentYear(newYear);
    };

    const changeYear = (offset) => {
        setCurrentYear(prev => prev + offset);
    };

    // انتخاب سریع
    const setToday = () => {
        const today = moment().format('jYYYY/jMM/jDD');
        if (activePicker === 'start') {
            if (endDate && isDate1GreaterThanDate2(today, endDate)) {
                setError('تاریخ شروع نباید از تاریخ پایان بزرگتر باشد');
                return;
            }
            setStartDate(today);
            setError('');
        } else {
            if (startDate && isDate1GreaterThanDate2(startDate, today)) {
                setError('تاریخ پایان باید بعد از تاریخ شروع باشد');
                return;
            }
            setEndDate(today);
            setError('');
        }
    };

    const setTomorrow = () => {
        const tomorrow = moment().add(1, 'day').format('jYYYY/jMM/jDD');
        if (activePicker === 'start') {
            if (endDate && isDate1GreaterThanDate2(tomorrow, endDate)) {
                setError('تاریخ شروع نباید از تاریخ پایان بزرگتر باشد');
                return;
            }
            setStartDate(tomorrow);
            setError('');
        } else {
            if (startDate && isDate1GreaterThanDate2(startDate, tomorrow)) {
                setError('تاریخ پایان باید بعد از تاریخ شروع باشد');
                return;
            }
            setEndDate(tomorrow);
            setError('');
        }
    };

    const setThisWeek = () => {
        const now = moment();
        const startOfWeek = now.clone().startOf('jWeek').format('jYYYY/jMM/jDD');
        const endOfWeek = now.clone().endOf('jWeek').format('jYYYY/jMM/jDD');
        setStartDate(startOfWeek);
        setEndDate(endOfWeek);
        setError('');
    };

    const setThisMonth = () => {
        const now = moment();
        const startOfMonth = now.clone().startOf('jMonth').format('jYYYY/jMM/jDD');
        const endOfMonth = now.clone().endOf('jMonth').format('jYYYY/jMM/jDD');
        setStartDate(startOfMonth);
        setEndDate(endOfMonth);
        setError('');
    };

    const setNextMonth = () => {
        const next = moment().add(1, 'jMonth');
        const startOfMonth = next.clone().startOf('jMonth').format('jYYYY/jMM/jDD');
        const endOfMonth = next.clone().endOf('jMonth').format('jYYYY/jMM/jDD');
        setStartDate(startOfMonth);
        setEndDate(endOfMonth);
        setError('');
    };

    const setThreeMonths = () => {
        const now = moment();
        const start = now.format('jYYYY/jMM/jDD');
        const end = now.clone().add(3, 'jMonth').format('jYYYY/jMM/jDD');
        setStartDate(start);
        setEndDate(end);
        setError('');
    };

    const setSixMonths = () => {
        const now = moment();
        const start = now.format('jYYYY/jMM/jDD');
        const end = now.clone().add(6, 'jMonth').format('jYYYY/jMM/jDD');
        setStartDate(start);
        setEndDate(end);
        setError('');
    };

    const setOneYear = () => {
        const now = moment();
        const start = now.format('jYYYY/jMM/jDD');
        const end = now.clone().add(1, 'jYear').format('jYYYY/jMM/jDD');
        setStartDate(start);
        setEndDate(end);
        setError('');
    };

    // جابجایی تاریخ‌ها
    const swapDates = () => {
        const temp = startDate;
        setStartDate(endDate);
        setEndDate(temp);
        setError('');
    };

    // کلیک روی روز تقویم
    const handleDateClick = (dateInfo) => {
        const clickedDate = dateInfo.jalaliDate;

        if (activePicker === 'start') {
            if (endDate && isDate1GreaterThanDate2(clickedDate, endDate)) {
                setError('تاریخ شروع نباید از تاریخ پایان بزرگتر باشد');
                return;
            }
            setStartDate(clickedDate);
            setError('');
            setActivePicker('end');
        } else {
            if (startDate && isDate1GreaterThanDate2(startDate, clickedDate)) {
                setError('تاریخ پایان باید بعد از تاریخ شروع باشد');
                return;
            }
            setEndDate(clickedDate);
            setError('');
        }
    };

    const handleConfirm = () => {
        if (!startDate && !endDate) {
            setError('لطفاً حداقل یک تاریخ را انتخاب کنید');
            return;
        }

        if (startDate && endDate && isDate1GreaterThanDate2(startDate, endDate)) {
            setError('تاریخ شروع نباید از تاریخ پایان بزرگتر باشد');
            return;
        }

        if (startDate && endDate) {
            onChange?.([startDate, endDate]);
        } else if (startDate) {
            onChange?.(startDate);
        } else if (endDate) {
            onChange?.(endDate);
        }

        setIsModalOpen(false);
        setError('');
    };

    const handleClear = () => {
        setStartDate('');
        setEndDate('');
        setError('');
        onChange?.(null);
        setIsModalOpen(false);
    };

    const getDaysDiff = () => {
        if (!startDate || !endDate) return null;
        if (!isValidJalaliDate(startDate) || !isValidJalaliDate(endDate)) return null;
        if (isDate1GreaterThanDate2(startDate, endDate)) return null;

        const start = moment(startDate, 'jYYYY/jMM/jDD');
        const end = moment(endDate, 'jYYYY/jMM/jDD');
        return end.diff(start, 'days') + 1;
    };

    const renderCalendar = () => {
        const daysInMonth = getDaysInMonth(currentYear, currentMonth);
        const firstDayOfMonth = getFirstDayOfMonth(currentYear, currentMonth);

        let prevMonthYear = currentYear;
        let prevMonth = currentMonth - 1;
        if (prevMonth < 1) {
            prevMonth = 12;
            prevMonthYear = currentYear - 1;
        }
        const daysInPrevMonth = getDaysInMonth(prevMonthYear, prevMonth);

        let nextMonthYear = currentYear;
        let nextMonth = currentMonth + 1;
        if (nextMonth > 12) {
            nextMonth = 1;
            nextMonthYear = currentYear + 1;
        }

        const days = [];

        // روزهای ماه قبل
        for (let i = firstDayOfMonth - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            const dateStr = `${prevMonthYear}/${prevMonth}/${day}`;
            days.push({
                day,
                dateStr,
                isCurrentMonth: false,
                jalaliDate: dateStr
            });
        }

        // روزهای ماه جاری
        for (let i = 1; i <= daysInMonth; i++) {
            const dateStr = `${currentYear}/${currentMonth}/${i}`;
            days.push({
                day: i,
                dateStr,
                isCurrentMonth: true,
                jalaliDate: dateStr
            });
        }

        // روزهای ماه بعد
        const remainingDays = 42 - days.length;
        for (let i = 1; i <= remainingDays; i++) {
            const dateStr = `${nextMonthYear}/${nextMonth}/${i}`;
            days.push({
                day: i,
                dateStr,
                isCurrentMonth: false,
                jalaliDate: dateStr
            });
        }

        // تقسیم به هفته‌ها
        const weeks = [];
        for (let i = 0; i < days.length; i += 7) {
            weeks.push(days.slice(i, i + 7));
        }

        return weeks;
    };

    const daysDiff = getDaysDiff();
    const weeks = renderCalendar();
    const isRangeValid = startDate && endDate && !isDate1GreaterThanDate2(startDate, endDate);

    const isSelectedStart = (dateStr) => startDate === dateStr;
    const isSelectedEnd = (dateStr) => endDate === dateStr;
    const isInRange = (dateStr) => {
        if (!startDate || !endDate) return false;
        if (isDate1GreaterThanDate2(startDate, endDate)) return false;
        return dateStr > startDate && dateStr < endDate;
    };
    const isHoverInRange = (dateStr) => {
        if (!hoverDate || !startDate || activePicker !== 'end') return false;
        if (endDate) return false;
        if (isDate1GreaterThanDate2(startDate, hoverDate)) return false;
        return dateStr > startDate && dateStr < hoverDate;
    };

    const hasError = startDate && endDate && isDate1GreaterThanDate2(startDate, endDate);

    return (
        <>
            {/* دو باکس ورودی */}
            <div style={{ display: 'flex', gap: 12, direction: 'rtl', alignItems: 'flex-end' }}>
                <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 12, marginBottom: 4, color: '#64748b' }}>
                        <StarOutlined style={{ fontSize: 10, marginLeft: 4 }} />
                        تاریخ شروع
                    </div>
                    <Input
                        placeholder={placeholder[0]}
                        value={startDate}
                        readOnly
                        disabled={disabled}
                        status={hasError ? 'error' : ''}
                        suffix={
                            <>
                                {allowClear && startDate && (
                                    <CloseOutlined onClick={() => {
                                        setStartDate('');
                                        setError('');
                                    }} />
                                )}
                                <span />
                            </>
                        }
                        onClick={() => !disabled && setIsModalOpen(true) && setActivePicker('start')}
                    />
                </div>
                <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 12, marginBottom: 4, color: '#64748b' }}>
                        <ThunderboltOutlined style={{ fontSize: 10, marginLeft: 4 }} />
                        تاریخ پایان
                    </div>
                    <Input
                        placeholder={placeholder[1]}
                        value={endDate}
                        readOnly
                        disabled={disabled}
                        status={hasError ? 'error' : ''}
                        suffix={
                            <>
                                {allowClear && endDate && (
                                    <CloseOutlined onClick={() => {
                                        setEndDate('');
                                        setError('');
                                    }} />
                                )}
                                <span />
                            </>
                        }
                        onClick={() => !disabled && setIsModalOpen(true) && setActivePicker('end')}
                    />
                </div>
                <Button
                    onClick={swapDates}
                    disabled={!startDate && !endDate}
                    icon={<SwapOutlined />}
                >
                    جابجایی
                </Button>
                {allowClear && (startDate || endDate) && (
                    <Button danger onClick={handleClear} icon={<ClearOutlined />}>
                        پاک کردن
                    </Button>
                )}
            </div>

            <Modal
                title={
                    <div style={{ textAlign: 'center', direction: 'rtl' }}>
                        <CalendarOutlined style={{ marginLeft: 8, color: '#3b82f6' }} />
                        <span style={{ fontSize: 16, fontWeight: 600 }}>
              {activePicker === 'start' ? 'انتخاب تاریخ شروع' : 'انتخاب تاریخ پایان'}
            </span>
                        {daysDiff && (
                            <Tag color="green" style={{ marginRight: 12 }}>
                                {daysDiff} روز
                            </Tag>
                        )}
                        {hasError && (
                            <Tag color="red" style={{ marginRight: 12 }}>
                                ❌ تاریخ شروع از پایان بزرگتر است
                            </Tag>
                        )}
                    </div>
                }
                open={isModalOpen}
                onCancel={() => {
                    setIsModalOpen(false);
                    setError('');
                }}
                footer={null}
                width={550}
                centered
                destroyOnClose
            >
                <div style={{ direction: 'rtl', padding: '8px 0' }}>
                    {/* نمایش بازه انتخاب شده */}
                    <div style={{
                        display: 'flex',
                        gap: 12,
                        marginBottom: 20,
                        padding: 16,
                        background: hasError ? '#fef2f2' : '#f8fafc',
                        borderRadius: 16
                    }}>
                        <div style={{ flex: 1, textAlign: 'center' }}>
                            <div style={{ fontSize: 11, color: '#64748b' }}>تاریخ شروع</div>
                            <div
                                style={{
                                    fontSize: 16,
                                    fontWeight: 700,
                                    cursor: 'pointer',
                                    color: activePicker === 'start' ? '#3b82f6' : '#333'
                                }}
                                onClick={() => setActivePicker('start')}
                            >
                                {startDate || '______'}
                            </div>
                        </div>
                        <div style={{ fontSize: 20, color: '#cbd5e1' }}>→</div>
                        <div style={{ flex: 1, textAlign: 'center' }}>
                            <div style={{ fontSize: 11, color: '#64748b' }}>تاریخ پایان</div>
                            <div
                                style={{
                                    fontSize: 16,
                                    fontWeight: 700,
                                    cursor: 'pointer',
                                    color: activePicker === 'end' ? '#3b82f6' : '#333'
                                }}
                                onClick={() => setActivePicker('end')}
                            >
                                {endDate || '______'}
                            </div>
                        </div>
                    </div>

                    {/* انتخاب سریع */}
                    {showQuickSelect && (
                        <div style={{ marginBottom: 20 }}>
                            <div style={{ fontSize: 12, color: '#64748b', marginBottom: 8 }}>انتخاب سریع:</div>
                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
                                <Button size="small" onClick={setToday}>امروز</Button>
                                <Button size="small" onClick={setTomorrow}>فردا</Button>
                                <Button size="small" onClick={setThisWeek}>این هفته</Button>
                                <Button size="small" onClick={setThisMonth}>این ماه</Button>
                                <Button size="small" onClick={setNextMonth}>ماه آینده</Button>
                                <Button size="small" onClick={setThreeMonths}>۳ ماه آینده</Button>
                                <Button size="small" onClick={setSixMonths}>۶ ماه آینده</Button>
                                <Button size="small" onClick={setOneYear}>یک سال آینده</Button>
                            </div>
                        </div>
                    )}

                    {/* هدر تقویم */}
                    <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        padding: '8px 12px',
                        marginBottom: 16,
                        backgroundColor: '#f1f5f9',
                        borderRadius: 12
                    }}>
                        <Space>
                            <Button size="small" onClick={() => changeYear(-1)}>⟪</Button>
                            <Button size="small" onClick={() => changeMonth(-1)}>◀</Button>
                            <Button size="small" onClick={() => changeMonth(1)}>▶</Button>
                            <Button size="small" onClick={() => changeYear(1)}>⟫</Button>
                        </Space>
                        <div style={{ fontSize: 16, fontWeight: 600 }}>
                            {jMonths[currentMonth - 1]} {currentYear}
                        </div>
                        <Button size="small" onClick={goToToday}>امروز</Button>
                    </div>

                    {/* روزهای هفته */}
                    <div style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(7, 1fr)',
                        textAlign: 'center',
                        marginBottom: 8,
                        fontWeight: 600,
                        color: '#64748b',
                        fontSize: 12
                    }}>
                        {jWeekDays.map((day, idx) => (
                            <div key={day} style={{ padding: '6px 0' }}>
                                {day}
                                {idx === 6 && <span style={{ color: '#ef4444' }}>*</span>}
                            </div>
                        ))}
                    </div>

                    {/* روزهای ماه */}
                    {weeks.map((week, weekIndex) => (
                        <div key={weekIndex} style={{
                            display: 'grid',
                            gridTemplateColumns: 'repeat(7, 1fr)',
                            textAlign: 'center',
                            marginBottom: 4
                        }}>
                            {week.map((day, dayIndex) => {
                                let cellStyle = {
                                    padding: '10px 0',
                                    cursor: 'pointer',
                                    borderRadius: '8px',
                                    margin: '2px',
                                    fontSize: 13,
                                    transition: 'all 0.2s ease'
                                };

                                if (!day.isCurrentMonth) {
                                    cellStyle.color = '#cbd5e1';
                                    cellStyle.opacity = 0.5;
                                }

                                if (isSelectedStart(day.jalaliDate)) {
                                    cellStyle.backgroundColor = '#3b82f6';
                                    cellStyle.color = 'white';
                                    cellStyle.borderRadius = '8px';
                                }

                                if (isSelectedEnd(day.jalaliDate)) {
                                    cellStyle.backgroundColor = '#3b82f6';
                                    cellStyle.color = 'white';
                                    cellStyle.borderRadius = '8px';
                                }

                                if (isInRange(day.jalaliDate)) {
                                    cellStyle.backgroundColor = '#dbeafe';
                                    cellStyle.borderRadius = 0;
                                }

                                if (isHoverInRange(day.jalaliDate)) {
                                    cellStyle.backgroundColor = '#bfdbfe';
                                    cellStyle.borderRadius = 0;
                                }

                                const dayOfWeek = moment(day.jalaliDate, 'jYYYY/jMM/jDD').day();
                                const isToday = moment(day.jalaliDate, 'jYYYY/jMM/jDD').isSame(moment(), 'day');

                                if (dayOfWeek === 6 && !isSelectedStart(day.jalaliDate) && !isSelectedEnd(day.jalaliDate)) {
                                    cellStyle.color = '#ef4444';
                                }

                                if (isToday && !isSelectedStart(day.jalaliDate) && !isSelectedEnd(day.jalaliDate)) {
                                    cellStyle.border = '1px solid #3b82f6';
                                }

                                return (
                                    <Tooltip
                                        key={dayIndex}
                                        title={moment(day.jalaliDate, 'jYYYY/jMM/jDD').format('dddd jD jMMMM jYYYY')}
                                    >
                                        <div
                                            style={cellStyle}
                                            onClick={() => handleDateClick(day)}
                                            onMouseEnter={() => setHoverDate(day.jalaliDate)}
                                            onMouseLeave={() => setHoverDate(null)}
                                        >
                                            {day.day}
                                        </div>
                                    </Tooltip>
                                );
                            })}
                        </div>
                    ))}

                    {error && (
                        <Alert
                            message={error}
                            type="error"
                            showIcon
                            style={{ marginTop: 16, borderRadius: 10 }}
                            closable
                            onClose={() => setError('')}
                        />
                    )}

                    {isRangeValid && daysDiff && (
                        <div style={{
                            marginTop: 16,
                            padding: 12,
                            background: '#f0fdf4',
                            borderRadius: 12,
                            textAlign: 'center'
                        }}>
                            <Tag color="green" style={{ fontSize: 14, borderRadius: 20 }}>
                                📊 تعداد روزهای بازه: {daysDiff} روز
                            </Tag>
                        </div>
                    )}

                    <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        marginTop: 20,
                        paddingTop: 16,
                        borderTop: '1px solid #f0f0f0'
                    }}>
                        <Button danger onClick={handleClear} icon={<ClearOutlined />}>
                            پاک کردن همه
                        </Button>
                        <Space>
                            <Button onClick={() => setIsModalOpen(false)}>
                                انصراف
                            </Button>
                            <Button
                                type="primary"
                                onClick={handleConfirm}
                                disabled={!startDate && !endDate}
                                icon={<CheckOutlined />}
                                size="large"
                            >
                                تأیید
                            </Button>
                        </Space>
                    </div>
                </div>
            </Modal>
        </>
    );
};

export default JalaliRangePicker;