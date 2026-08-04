'use client';

import { useState } from 'react';
import { Input, Select, Button, Space, Drawer, DatePicker, Switch, Form } from 'antd';
import { SearchOutlined, FilterOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';

const { Option } = Select;

export default function AdvancedSearch({ onSearch, filters, placeholder }) {
  const { t } = useLanguage();
  const [drawerVisible, setDrawerVisible] = useState(false);
  const [form] = Form.useForm();
  const [searchText, setSearchText] = useState('');

  const handleSearch = (value) => {
    setSearchText(value);
    onSearch({ text: value, filters: form.getFieldsValue() });
  };

  const handleFilterApply = () => {
    const values = form.getFieldsValue();
    onSearch({ text: searchText, filters: values });
    setDrawerVisible(false);
  };

  const handleFilterReset = () => {
    form.resetFields();
    onSearch({ text: searchText, filters: {} });
    setDrawerVisible(false);
  };

  return (
    <>
      <Space size="middle" style={{ width: '100%' }}>
        <Input.Search
          placeholder={placeholder || 'جستجو...'}
          prefix={<SearchOutlined />}
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          onSearch={handleSearch}
          enterButton
          style={{ flex: 1 }}
        />
        <Button
          icon={<FilterOutlined />}
          onClick={() => setDrawerVisible(true)}
        >
          فیلتر
        </Button>
      </Space>

      <Drawer
        title="فیلترهای پیشرفته"
        placement="right"
        onClose={() => setDrawerVisible(false)}
        open={drawerVisible}
        size="large"
        extra={
          <Space>
            <Button onClick={handleFilterReset}>ریست</Button>
            <Button type="primary" onClick={handleFilterApply}>
              اعمال فیلتر
            </Button>
          </Space>
        }
      >
        <Form form={form} layout="vertical">
          {filters?.map((filter) => (
            <Form.Item key={filter.name} name={filter.name} label={filter.label}>
              {filter.type === 'select' && (
                <Select placeholder={filter.placeholder} allowClear>
                  {filter.options?.map((opt) => (
                    <Option key={opt.value} value={opt.value}>
                      {opt.label}
                    </Option>
                  ))}
                </Select>
              )}
              {filter.type === 'date' && (
                <DatePicker style={{ width: '100%' }} />
              )}
              {filter.type === 'dateRange' && (
                <DatePicker.RangePicker style={{ width: '100%' }} />
              )}
              {filter.type === 'switch' && (
                <Switch />
              )}
              {!filter.type && (
                <Input placeholder={filter.placeholder} />
              )}
            </Form.Item>
          ))}
        </Form>
      </Drawer>
    </>
  );
}
