'use client';

import { useMemo, useState } from 'react';
import Link from 'next/link';
import {
  AppstoreOutlined,
  LeftOutlined,
  RightOutlined,
} from '@ant-design/icons';

import styles from './DoctorWebHome.module.css';

export default function HomeMegaMenu({ copy, direction = 'rtl' }) {
  const groups = copy?.megaMenu?.length
    ? copy.megaMenu
    : [];

  const [open, setOpen] = useState(false);
  const [activeKey, setActiveKey] = useState(groups[0]?.key);

  const activeGroup = useMemo(
    () =>
      groups.find((group) => group.key === activeKey) ||
      groups[0],
    [groups, activeKey],
  );

  const ArrowIcon =
    direction === 'rtl' ? LeftOutlined : RightOutlined;

  if (!groups.length) {
    return null;
  }

  return (
    <div
      className={styles.megaRoot}
      onMouseEnter={() => setOpen(true)}
      onMouseLeave={() => setOpen(false)}
    >
      <button
        type="button"
        className={styles.megaTrigger}
        onClick={() => setOpen((value) => !value)}
      >
        <AppstoreOutlined />
        <span>{copy.nav.allServices}</span>
      </button>

      {open && (
        <div className={styles.megaPanel}>
          <aside className={styles.megaSidebar}>
            {groups.map((group) => (
              <button
                type="button"
                key={group.key}
                className={
                  group.key === activeGroup?.key
                    ? styles.megaSidebarActive
                    : styles.megaSidebarItem
                }
                onMouseEnter={() => setActiveKey(group.key)}
                onFocus={() => setActiveKey(group.key)}
              >
                <span>{group.title}</span>
                <ArrowIcon />
              </button>
            ))}
          </aside>

          <div className={styles.megaContent}>
            <div className={styles.megaColumns}>
              {activeGroup?.columns?.map((column) => (
                <div key={column.title}>
                  <h3>{column.title}</h3>

                  {column.items.map(([label, href]) => (
                    <Link
                      key={`${label}-${href}`}
                      href={href}
                      onClick={() => setOpen(false)}
                    >
                      <span>{label}</span>
                      <ArrowIcon />
                    </Link>
                  ))}
                </div>
              ))}
            </div>
          </div>

          <div className={styles.megaPromo}>
            <span>دکتر وب</span>
            <strong>تمام خدمات سلامت در یک پلتفرم</strong>
            <p>
              پزشک، داروخانه، آزمایشگاه، خدمات در منزل و
              گردشگری درمانی
            </p>

            <Link href="/doctors">مشاهده پزشکان</Link>
          </div>
        </div>
      )}
    </div>
  );
}
