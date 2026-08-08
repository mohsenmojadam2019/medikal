'use client';

import {
  useCallback,
  useEffect,
  useState,
} from 'react';

import {
  App,
  Avatar,
  Button,
  Card,
  Col,
  Divider,
  Empty,
  Input,
  Rate,
  Row,
  Select,
  Space,
  Spin,
  Tag,
  Typography,
} from 'antd';

import {
  CalendarOutlined,
  EnvironmentOutlined,
  FilterOutlined,
  HeartFilled,
  HeartOutlined,
  SearchOutlined,
  SortAscendingOutlined,
} from '@ant-design/icons';

import { useRouter } from 'next/navigation';

import { useLanguage } from '@/lib/context/LanguageContext';

import HomeHeader from '@/components/home/HomeHeader';
import HomeFooter from '@/components/home/HomeFooter';
import Breadcrumb from '@/components/shared/Breadcrumb';

const { Title, Text } = Typography;
const { Search } = Input;

function extractCollection(payload) {
  if (Array.isArray(payload)) {
    return payload;
  }

  if (Array.isArray(payload?.data?.data)) {
    return payload.data.data;
  }

  if (Array.isArray(payload?.data)) {
    return payload.data;
  }

  if (Array.isArray(payload?.results)) {
    return payload.results;
  }

  return [];
}

function getDoctorName(doctor) {
  return (
    doctor?.user?.name ||
    doctor?.full_name ||
    doctor?.name ||
    'پزشک'
  );
}

function getSpecialtyName(doctor) {
  return (
    doctor?.specialty?.name ||
    doctor?.specialty_name ||
    'پزشک عمومی'
  );
}

function getDoctorImage(doctor) {
  return (
    doctor?.profile_image ||
    doctor?.avatar_url ||
    doctor?.user?.avatar_url ||
    null
  );
}

export default function DoctorsPage() {
  const router = useRouter();

  const {
    direction = 'rtl',
  } = useLanguage();

  const {
    message: appMessage,
  } = App.useApp();

  const [doctors, setDoctors] = useState([]);
  const [specialties, setSpecialties] = useState([]);
  const [favorites, setFavorites] = useState([]);

  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [specialtyFilter, setSpecialtyFilter] = useState(null);
  const [sortBy, setSortBy] = useState('rating');

  useEffect(() => { const value = new URLSearchParams(window.location.search).get('specialty'); if (value) setSearchTerm(value); }, []);

  const fetchDoctors = useCallback(async () => {
    setLoading(true);

    try {
      const response = await fetch(
        '/backend-api/api/doctors?per_page=100',
        {
          headers: {
            Accept: 'application/json',
          },
          cache: 'no-store',
        },
      );

      const payload = await response.json().catch(() => null);

      if (!response.ok) {
        throw new Error(
          payload?.message ||
          'خطا در دریافت لیست پزشکان',
        );
      }

      if (payload?.success === false) {
        throw new Error(
          payload?.message ||
          'خطا در دریافت لیست پزشکان',
        );
      }

      let doctorsData = extractCollection(payload);

      if (specialtyFilter) {
        doctorsData = doctorsData.filter((doctor) => {
          const doctorSpecialtyId =
            doctor?.specialty_id ||
            doctor?.specialty?.id;

          return String(doctorSpecialtyId) ===
            String(specialtyFilter);
        });
      }

      const normalizedSearch = searchTerm
        .trim()
        .toLocaleLowerCase('fa');

      if (normalizedSearch) {
        doctorsData = doctorsData.filter((doctor) => {
          const searchableText = [
            getDoctorName(doctor),
            getSpecialtyName(doctor),
            doctor?.clinic_name,
            doctor?.address,
          ]
            .filter(Boolean)
            .join(' ')
            .toLocaleLowerCase('fa');

          return searchableText.includes(normalizedSearch);
        });
      }

      doctorsData.sort((firstDoctor, secondDoctor) => {
        if (sortBy === 'rating') {
          return (
            Number(secondDoctor?.rating || 0) -
            Number(firstDoctor?.rating || 0)
          );
        }

        if (sortBy === 'fee') {
          return (
            Number(firstDoctor?.consultation_fee || 0) -
            Number(secondDoctor?.consultation_fee || 0)
          );
        }

        if (sortBy === 'experience') {
          return (
            Number(secondDoctor?.experience || 0) -
            Number(firstDoctor?.experience || 0)
          );
        }

        return 0;
      });

      setDoctors(doctorsData);
    } catch (error) {
      console.error('Doctors request failed:', error);

      appMessage.error(
        error?.message ||
        'خطا در ارتباط با سرور',
      );

      setDoctors([]);
    } finally {
      setLoading(false);
    }
  }, [
    appMessage,
    searchTerm,
    sortBy,
    specialtyFilter,
  ]);

  const fetchSpecialties = useCallback(async () => {
    try {
      const response = await fetch(
        '/backend-api/api/specialties?per_page=200',
        {
          headers: {
            Accept: 'application/json',
          },
          cache: 'no-store',
        },
      );

      const payload = await response.json().catch(() => null);

      if (!response.ok || payload?.success === false) {
        throw new Error(
          payload?.message ||
          'خطا در دریافت تخصص‌ها',
        );
      }

      setSpecialties(extractCollection(payload));
    } catch (error) {
      console.error(
        'Specialties request failed:',
        error,
      );

      setSpecialties([]);
    }
  }, []);

  useEffect(() => {
    fetchSpecialties();

    try {
      const storedFavorites = JSON.parse(
        localStorage.getItem('favoriteDoctors') ||
        '[]',
      );

      setFavorites(
        Array.isArray(storedFavorites)
          ? storedFavorites.map(String)
          : [],
      );
    } catch {
      setFavorites([]);
    }
  }, [fetchSpecialties]);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      fetchDoctors();
    }, 300);

    return () => {
      window.clearTimeout(timer);
    };
  }, [fetchDoctors]);

  const toggleFavorite = (doctorId) => {
    const normalizedId = String(doctorId);

    setFavorites((currentFavorites) => {
      const updatedFavorites =
        currentFavorites.includes(normalizedId)
          ? currentFavorites.filter(
              (item) => item !== normalizedId,
            )
          : [
              ...currentFavorites,
              normalizedId,
            ];

      localStorage.setItem(
        'favoriteDoctors',
        JSON.stringify(updatedFavorites),
      );

      return updatedFavorites;
    });
  };

  const resetFilters = () => {
    setSearchTerm('');
    setSpecialtyFilter(null);
    setSortBy('rating');
  };

  const handleBookAppointment = (doctorId) => {
    if (!doctorId) {
      appMessage.error(
        'شناسه پزشک نامعتبر است',
      );

      return;
    }

    localStorage.setItem(
      'selectedDoctorId',
      String(doctorId),
    );

    router.push(
      `/appointments/new?doctorId=${doctorId}`,
    );
  };

  if (loading) {
    return (
      <div dir={direction}>
        <HomeHeader />

        <main
          style={{
            minHeight: '60vh',
            display: 'grid',
            placeItems: 'center',
            background: '#f5f8fc',
            padding: '60px 20px',
          }}
        >
          <div
            style={{
              textAlign: 'center',
            }}
          >
            <Spin size="large" />

            <p
              style={{
                marginTop: '16px',
                color: '#64748b',
              }}
            >
              در حال بارگذاری پزشکان...
            </p>
          </div>
        </main>

        <HomeFooter />
      </div>
    );
  }

  return (
    <div dir={direction}>
      <HomeHeader />

      <main
        style={{
          minHeight: 'calc(100vh - 200px)',
          position: 'relative',
          backgroundImage:
            "url('/image/bac-1.png')",
          backgroundSize: 'cover',
          backgroundPosition: 'center',
          backgroundRepeat: 'no-repeat',
          backgroundAttachment: 'fixed',
        }}
      >
        <div
          style={{
            position: 'absolute',
            inset: 0,
            zIndex: 0,
            background:
              'rgba(248, 251, 255, 0.9)',
          }}
        />

        <div
          style={{
            width: '100%',
            maxWidth: '1200px',
            margin: '0 auto',
            padding: '30px 20px 60px',
            position: 'relative',
            zIndex: 1,
          }}
        >
          <Breadcrumb />

          <div
            style={{
              marginBottom: '32px',
              textAlign: 'center',
            }}
          >
            <Title
              level={2}
              style={{
                marginBottom: '6px',
                fontSize: '32px',
                color: '#102a43',
              }}
            >
              پزشکان متخصص
            </Title>

            <Text
              type="secondary"
              style={{
                fontSize: '16px',
              }}
            >
              بهترین پزشکان را بر اساس تخصص و
              امتیاز انتخاب کنید
            </Text>
          </div>

          <Card
            style={{
              marginBottom: '24px',
              border: '1px solid #e8eef6',
              borderRadius: '18px',
              background:
                'rgba(255,255,255,0.96)',
              boxShadow:
                '0 10px 35px rgba(20, 56, 95, 0.06)',
              backdropFilter: 'blur(12px)',
            }}
          >
            <Row
              gutter={[
                16,
                16,
              ]}
              align="middle"
            >
              <Col
                xs={24}
                md={8}
              >
                <Search
                  placeholder="جستجوی پزشک، تخصص یا مطب"
                  value={searchTerm}
                  onChange={(event) =>
                    setSearchTerm(
                      event.target.value,
                    )
                  }
                  onSearch={(value) =>
                    setSearchTerm(value)
                  }
                  size="large"
                  allowClear
                  enterButton={
                    <Button
                      type="primary"
                      icon={
                        <SearchOutlined />
                      }
                    >
                      جستجو
                    </Button>
                  }
                />
              </Col>

              <Col
                xs={12}
                md={5}
              >
                <Select
                  placeholder="انتخاب تخصص"
                  style={{
                    width: '100%',
                  }}
                  allowClear
                  value={specialtyFilter}
                  onChange={setSpecialtyFilter}
                  size="large"
                  suffixIcon={
                    <FilterOutlined />
                  }
                  options={[
                    {
                      label: 'همه تخصص‌ها',
                      value: null,
                    },
                    ...specialties.map(
                      (specialty) => ({
                        label:
                          specialty?.name ||
                          'تخصص',
                        value:
                          specialty?.id,
                      }),
                    ),
                  ]}
                />
              </Col>

              <Col
                xs={12}
                md={5}
              >
                <Select
                  placeholder="مرتب‌سازی"
                  style={{
                    width: '100%',
                  }}
                  value={sortBy}
                  onChange={setSortBy}
                  size="large"
                  suffixIcon={
                    <SortAscendingOutlined />
                  }
                  options={[
                    {
                      label:
                        'بیشترین امتیاز',
                      value: 'rating',
                    },
                    {
                      label:
                        'کمترین هزینه',
                      value: 'fee',
                    },
                    {
                      label:
                        'بیشترین سابقه',
                      value: 'experience',
                    },
                  ]}
                />
              </Col>

              <Col
                xs={24}
                md={6}
              >
                <Button
                  onClick={fetchDoctors}
                  size="large"
                  block
                  style={{
                    height: '44px',
                    borderRadius: '12px',
                  }}
                >
                  بروزرسانی لیست
                </Button>
              </Col>
            </Row>
          </Card>

          <div
            style={{
              marginBottom: '16px',
              display: 'flex',
              alignItems: 'center',
              justifyContent:
                'space-between',
            }}
          >
            <Text type="secondary">
              <strong
                style={{
                  color: '#0f172a',
                  fontSize: '18px',
                }}
              >
                {doctors.length}
              </strong>{' '}
              پزشک یافت شد
            </Text>
          </div>

          <Row
            gutter={[
              24,
              24,
            ]}
          >
            {doctors.length === 0 ? (
              <Col span={24}>
                <Card
                  style={{
                    borderRadius: '18px',
                    background:
                      'rgba(255,255,255,0.96)',
                  }}
                >
                  <Empty
                    description="هیچ پزشکی یافت نشد"
                    image={
                      Empty.PRESENTED_IMAGE_SIMPLE
                    }
                  >
                    <Button
                      type="primary"
                      onClick={resetFilters}
                    >
                      بازنشانی فیلترها
                    </Button>
                  </Empty>
                </Card>
              </Col>
            ) : (
              doctors.map((doctor) => {
                const doctorId =
                  doctor?.id;

                const isFavorite =
                  favorites.includes(
                    String(doctorId),
                  );

                const rating = Number(
                  doctor?.rating ||
                  doctor?.average_rating ||
                  0,
                );

                const fee = Number(
                  doctor?.consultation_fee ||
                  doctor?.fee ||
                  0,
                );

                const isAvailable =
                  doctor?.is_available !==
                  false;

                const doctorName =
                  getDoctorName(doctor);

                const specialtyName =
                  getSpecialtyName(doctor);

                return (
                  <Col
                    xs={24}
                    md={12}
                    lg={8}
                    xl={6}
                    key={doctorId}
                  >
                    <Card
                      hoverable
                      className="doctor-card-modern"
                      style={{
                        height: '100%',
                        overflow: 'hidden',
                        position: 'relative',
                        border:
                          '1px solid #e8eef6',
                        borderRadius: '20px',
                        background:
                          'rgba(255,255,255,0.97)',
                        boxShadow:
                          '0 8px 30px rgba(20, 56, 95, 0.06)',
                        backdropFilter:
                          'blur(10px)',
                      }}
                      styles={{
                        body: {
                          padding: '20px',
                        },
                      }}
                    >
                      <button
                        type="button"
                        aria-label={
                          isFavorite
                            ? 'حذف از علاقه‌مندی‌ها'
                            : 'افزودن به علاقه‌مندی‌ها'
                        }
                        onClick={(event) => {
                          event.stopPropagation();

                          toggleFavorite(
                            doctorId,
                          );
                        }}
                        style={{
                          width: '36px',
                          height: '36px',
                          position:
                            'absolute',
                          zIndex: 10,
                          top: '12px',
                          right: '12px',
                          display: 'flex',
                          alignItems:
                            'center',
                          justifyContent:
                            'center',
                          border: 'none',
                          borderRadius:
                            '50%',
                          background:
                            'rgba(255,255,255,0.94)',
                          boxShadow:
                            '0 2px 10px rgba(0,0,0,0.08)',
                          cursor: 'pointer',
                        }}
                      >
                        {isFavorite ? (
                          <HeartFilled
                            style={{
                              color:
                                '#ef4444',
                              fontSize:
                                '18px',
                            }}
                          />
                        ) : (
                          <HeartOutlined
                            style={{
                              color:
                                '#94a3b8',
                              fontSize:
                                '18px',
                            }}
                          />
                        )}
                      </button>

                      {rating >= 4.8 && (
                        <div
                          style={{
                            position:
                              'absolute',
                            zIndex: 10,
                            top: '12px',
                            left: '12px',
                            padding:
                              '4px 11px',
                            borderRadius:
                              '50px',
                            background:
                              '#fff4d6',
                            color:
                              '#9a6500',
                            fontSize:
                              '10px',
                            fontWeight:
                              '700',
                          }}
                        >
                          ویژه
                        </div>
                      )}

                      <div
                        style={{
                          marginBottom:
                            '16px',
                          textAlign:
                            'center',
                        }}
                      >
                        <div
                          style={{
                            position:
                              'relative',
                            display:
                              'inline-block',
                          }}
                        >
                          <Avatar
                            size={88}
                            src={getDoctorImage(
                              doctor,
                            )}
                            style={{
                              border:
                                '4px solid #fff',
                              background:
                                'linear-gradient(135deg, #1597d4, #36c3be)',
                              boxShadow:
                                '0 6px 20px rgba(18,100,160,0.16)',
                              fontSize:
                                '32px',
                            }}
                          >
                            {doctorName
                              ?.charAt(0)}
                          </Avatar>

                          <div
                            title={
                              isAvailable
                                ? 'فعال'
                                : 'غیرفعال'
                            }
                            style={{
                              width:
                                '17px',
                              height:
                                '17px',
                              position:
                                'absolute',
                              right: '2px',
                              bottom: '3px',
                              border:
                                '3px solid #fff',
                              borderRadius:
                                '50%',
                              background:
                                isAvailable
                                  ? '#10b981'
                                  : '#ef4444',
                            }}
                          />
                        </div>
                      </div>

                      <div
                        style={{
                          textAlign:
                            'center',
                        }}
                      >
                        <h3
                          style={{
                            margin:
                              '0 0 5px',
                            color:
                              '#102a43',
                            fontSize:
                              '17px',
                            fontWeight:
                              '800',
                          }}
                        >
                          {doctorName}
                        </h3>

                        <Text
                          style={{
                            color:
                              '#168ec4',
                            fontSize:
                              '14px',
                            fontWeight:
                              '600',
                          }}
                        >
                          {specialtyName}
                        </Text>

                        {doctor?.clinic_name && (
                          <div
                            style={{
                              marginTop:
                                '7px',
                              display:
                                'flex',
                              alignItems:
                                'center',
                              justifyContent:
                                'center',
                              gap: '5px',
                            }}
                          >
                            <EnvironmentOutlined
                              style={{
                                color:
                                  '#94a3b8',
                                fontSize:
                                  '12px',
                              }}
                            />

                            <Text
                              type="secondary"
                              style={{
                                fontSize:
                                  '12px',
                              }}
                            >
                              {
                                doctor.clinic_name
                              }
                            </Text>
                          </div>
                        )}
                      </div>

                      <Divider
                        style={{
                          margin:
                            '14px 0',
                        }}
                      />

                      <div
                        style={{
                          marginBottom:
                            '12px',
                          display:
                            'flex',
                          alignItems:
                            'center',
                          justifyContent:
                            'space-between',
                          gap: '8px',
                        }}
                      >
                        <Space size={5}>
                          <Rate
                            disabled
                            value={rating}
                            allowHalf
                            style={{
                              color:
                                '#f59e0b',
                              fontSize:
                                '13px',
                            }}
                          />

                          <Text
                            strong
                            style={{
                              fontSize:
                                '13px',
                            }}
                          >
                            {rating.toFixed(
                              1,
                            )}
                          </Text>
                        </Space>

                        <Text
                          type="secondary"
                          style={{
                            fontSize:
                              '12px',
                          }}
                        >
                          {doctor?.total_reviews ||
                            0}{' '}
                          نظر
                        </Text>
                      </div>

                      <div
                        style={{
                          marginBottom:
                            '13px',
                          padding:
                            '10px 14px',
                          borderRadius:
                            '12px',
                          background:
                            '#f5f9fd',
                          textAlign:
                            'center',
                        }}
                      >
                        {fee > 0 ? (
                          <>
                            <Text
                              strong
                              style={{
                                color:
                                  '#168ec4',
                                fontSize:
                                  '19px',
                              }}
                            >
                              {fee.toLocaleString(
                                'fa-IR',
                              )}
                            </Text>

                            <Text
                              type="secondary"
                              style={{
                                fontSize:
                                  '13px',
                              }}
                            >
                              {' '}
                              تومان
                            </Text>
                          </>
                        ) : (
                          <Text
                            type="secondary"
                            style={{
                              fontSize:
                                '13px',
                            }}
                          >
                            هزینه با مرکز هماهنگ
                            می‌شود
                          </Text>
                        )}
                      </div>

                      <Space
                        direction="vertical"
                        size="small"
                        style={{
                          width: '100%',
                        }}
                      >
                        <Button
                          type="primary"
                          size="large"
                          icon={
                            <CalendarOutlined />
                          }
                          onClick={() =>
                            handleBookAppointment(
                              doctorId,
                            )
                          }
                          style={{
                            width: '100%',
                            height: '44px',
                            border: 'none',
                            borderRadius:
                              '12px',
                            background:
                              'linear-gradient(135deg, #158ec8, #17aaa3)',
                            fontWeight:
                              '700',
                          }}
                        >
                          رزرو نوبت
                        </Button>

                        <Button
                          size="large"
                          onClick={() =>
                            router.push(
                              `/doctors/${doctorId}`,
                            )
                          }
                          style={{
                            width: '100%',
                            height: '40px',
                            borderColor:
                              '#dce6f0',
                            borderRadius:
                              '12px',
                            color:
                              '#475569',
                          }}
                        >
                          مشاهده پروفایل
                        </Button>
                      </Space>

                      <div
                        style={{
                          marginTop:
                            '10px',
                          display: 'flex',
                          justifyContent:
                            'center',
                          gap: '5px',
                          flexWrap: 'wrap',
                        }}
                      >
                        <Tag
                          color={
                            isAvailable
                              ? 'green'
                              : 'red'
                          }
                          style={{
                            margin: 0,
                            borderRadius:
                              '50px',
                            fontSize:
                              '10px',
                          }}
                        >
                          {isAvailable
                            ? 'فعال'
                            : 'غیرفعال'}
                        </Tag>

                        {Number(
                          doctor?.experience ||
                            0,
                        ) > 0 && (
                          <Tag
                            color="blue"
                            style={{
                              margin: 0,
                              borderRadius:
                                '50px',
                              fontSize:
                                '10px',
                            }}
                          >
                            {
                              doctor.experience
                            }{' '}
                            سال سابقه
                          </Tag>
                        )}
                      </div>
                    </Card>
                  </Col>
                );
              })
            )}
          </Row>

          {doctors.length > 0 && (
            <div
              style={{
                marginTop: '45px',
                padding: '20px',
                textAlign: 'center',
              }}
            >
              <Text type="secondary">
                {doctors.length} پزشک متخصص
                آماده ارائه خدمت هستند
              </Text>
            </div>
          )}
        </div>
      </main>

      <HomeFooter />

      <style jsx global>{`
        .doctor-card-modern {
          transition:
            transform 0.25s ease,
            box-shadow 0.25s ease,
            border-color 0.25s ease !important;
        }

        .doctor-card-modern:hover {
          transform: translateY(-6px);
          border-color: #afdbea !important;
          box-shadow: 0 18px 50px rgba(20, 100, 145, 0.13) !important;
        }

        .doctor-card-modern .ant-btn-primary:hover {
          transform: translateY(-1px);
          box-shadow: 0 8px 22px rgba(18, 142, 180, 0.24);
        }

        @media (max-width: 768px) {
          .doctor-card-modern .ant-card-body {
            padding: 16px !important;
          }
        }
      `}</style>
    </div>
  );
}
