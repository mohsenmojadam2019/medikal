'use client';

import { Input } from 'antd';
import { SearchOutlined } from '@ant-design/icons';

export default function SearchBox() {
  return (
    <div className="search-box">
      <Input
        size="large"
        placeholder="جستجو"
        prefix={<SearchOutlined />}
        suffix={<span className="search-shortcut">Ctrl + K</span>}
        onPressEnter={(e) => {
          console.log('Search:', e.target.value);
        }}
      />
    </div>
  );
}
