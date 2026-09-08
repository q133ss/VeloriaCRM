import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { addDays, formatDateLabel, formatWeekdayShort, toIsoDate } from '../../../shared/format/ruDate';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { useAppTheme } from '../../../theme/theme';
import { clientPortalApi } from '../api/clientPortalApi';
import { useClientPortal } from '../model/clientPortalContext';
import { BookingCategoryOption, BookingServiceOption } from '../model/types';

type Props = NativeStackScreenProps<RootStackParamList, 'Booking'>;

const VISIBLE_DAYS = 14;
const ANY_SERVICE = 'any' as const;
type ServiceSelection = BookingServiceOption | typeof ANY_SERVICE | null;

function buildUpcomingDates(): string[] {
  const today = new Date();

  return Array.from({ length: VISIBLE_DAYS }, (_, index) => toIsoDate(addDays(today, index)));
}

export function BookingScreen({ navigation, route }: Props) {
  const { token, master } = useClientPortal();
  const theme = useAppTheme(master?.branding);

  const [categories, setCategories] = useState<BookingCategoryOption[]>([]);
  const [services, setServices] = useState<BookingServiceOption[]>([]);
  const [selectedCategoryId, setSelectedCategoryId] = useState<number | null>(null);
  const [selectedService, setSelectedService] = useState<ServiceSelection>(null);
  const [loadingCatalog, setLoadingCatalog] = useState(true);
  const [catalogError, setCatalogError] = useState<string | null>(null);

  const dates = useMemo(buildUpcomingDates, []);
  const [selectedDate, setSelectedDate] = useState(dates[0]);
  const [selectedTime, setSelectedTime] = useState<string | null>(null);
  const [slots, setSlots] = useState<string[]>([]);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [slotsError, setSlotsError] = useState<string | null>(null);

  const preselectServiceId = route.params?.serviceId;

  useEffect(() => {
    if (!token) {
      return;
    }

    let cancelled = false;

    async function loadCatalog() {
      setLoadingCatalog(true);
      setCatalogError(null);

      try {
        const [categoriesResponse, servicesResponse] = await Promise.all([
          clientPortalApi.getServiceCategories(token as string),
          clientPortalApi.getServices(token as string),
        ]);

        if (cancelled) {
          return;
        }

        const nextServices = servicesResponse.data.services.map((service): BookingServiceOption => ({
          id: service.id,
          categoryId: service.category_id,
          name: service.name,
          durationLabel: service.duration_min ? `${service.duration_min} мин` : 'Длительность уточняется',
          priceLabel: service.base_price !== null ? `от ${service.base_price} ₽` : 'Цена уточняется',
          durationMin: service.duration_min,
        }));

        setCategories(categoriesResponse.data.categories);
        setServices(nextServices);

        const preselected = preselectServiceId
          ? nextServices.find((service) => service.id === preselectServiceId)
          : undefined;

        if (preselected) {
          setSelectedService(preselected);
          setSelectedCategoryId(preselected.categoryId);
        }
      } catch (error) {
        if (!cancelled) {
          const message = error instanceof Error ? error.message : 'Не удалось загрузить услуги.';
          setCatalogError(message);
        }
      } finally {
        if (!cancelled) {
          setLoadingCatalog(false);
        }
      }
    }

    void loadCatalog();

    return () => {
      cancelled = true;
    };
    // preselectServiceId only matters on first mount — once the client changes
    // their own selection it must not be overridden if the route params object
    // happens to re-render with the same values.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token]);

  useEffect(() => {
    if (!token || !selectedService) {
      setSlots([]);
      setSelectedTime(null);
      return;
    }

    let cancelled = false;

    async function loadSlots() {
      setLoadingSlots(true);
      setSlotsError(null);
      setSelectedTime(null);

      try {
        const response = selectedService === ANY_SERVICE
          ? await clientPortalApi.getSlots(token as string, { date: selectedDate })
          : await clientPortalApi.getServiceSlots(token as string, (selectedService as BookingServiceOption).id, { date: selectedDate });

        if (!cancelled) {
          setSlots(response.data.slots);
        }
      } catch (error) {
        if (!cancelled) {
          const message = error instanceof Error ? error.message : 'Не удалось загрузить свободное время.';
          setSlotsError(message);
          setSlots([]);
        }
      } finally {
        if (!cancelled) {
          setLoadingSlots(false);
        }
      }
    }

    void loadSlots();

    return () => {
      cancelled = true;
    };
  }, [token, selectedService, selectedDate]);

  const visibleServices = selectedCategoryId === null
    ? services
    : services.filter((service) => service.categoryId === selectedCategoryId);

  const canContinue = selectedService !== null && selectedTime !== null;

  const handleContinue = () => {
    if (!selectedService || !selectedTime) {
      return;
    }

    navigation.navigate('BookingConfirmation', {
      serviceId: selectedService === ANY_SERVICE ? null : selectedService.id,
      serviceLabel: selectedService === ANY_SERVICE ? 'Любая услуга' : selectedService.name,
      date: selectedDate,
      time: selectedTime,
    });
  };

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.root}>
        <View style={styles.topBar}>
          <Pressable onPress={() => navigation.goBack()} style={styles.backButton}>
            <Text style={[styles.backText, { color: theme.colors.textSecondary }]}>Назад</Text>
          </Pressable>
          <Text style={[styles.title, { color: theme.colors.textPrimary }]}>Новая запись</Text>
          <View style={styles.backSpacer} />
        </View>

        {loadingCatalog ? (
          <ActivityIndicator color={theme.colors.primary} style={styles.loader} />
        ) : catalogError ? (
          <Text style={[styles.errorText, { color: theme.colors.textSecondary }]}>{catalogError}</Text>
        ) : (
          <>
            <View style={styles.blockGap}>
              <Text style={[styles.blockLabel, { color: theme.colors.textMuted }]}>Услуга</Text>

              {categories.length > 0 ? (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
                  <Pressable
                    onPress={() => setSelectedCategoryId(null)}
                    style={[
                      styles.chip,
                      {
                        backgroundColor: selectedCategoryId === null ? theme.colors.primary : theme.colors.surfaceMuted,
                      },
                    ]}
                  >
                    <Text style={[styles.chipText, { color: selectedCategoryId === null ? '#fffaf2' : theme.colors.textSecondary }]}>
                      Все
                    </Text>
                  </Pressable>
                  {categories.map((category) => {
                    const active = selectedCategoryId === category.id;

                    return (
                      <Pressable
                        key={category.id}
                        onPress={() => setSelectedCategoryId(category.id)}
                        style={[
                          styles.chip,
                          { backgroundColor: active ? theme.colors.primary : theme.colors.surfaceMuted },
                        ]}
                      >
                        <Text style={[styles.chipText, { color: active ? '#fffaf2' : theme.colors.textSecondary }]}>
                          {category.name}
                        </Text>
                      </Pressable>
                    );
                  })}
                </ScrollView>
              ) : null}

              <Pressable
                onPress={() => setSelectedService(ANY_SERVICE)}
                style={[
                  styles.serviceRow,
                  {
                    borderColor: selectedService === ANY_SERVICE ? theme.colors.primary : theme.colors.borderSoft,
                    backgroundColor: theme.colors.surfaceElevated,
                  },
                ]}
              >
                <Text style={[styles.serviceName, { color: theme.colors.textPrimary }]}>Любая услуга</Text>
                <Text style={[styles.serviceMeta, { color: theme.colors.textSecondary }]}>
                  Подберём время на месте
                </Text>
              </Pressable>

              {visibleServices.map((service) => {
                const active = selectedService !== null && selectedService !== ANY_SERVICE && selectedService.id === service.id;

                return (
                  <Pressable
                    key={service.id}
                    onPress={() => setSelectedService(service)}
                    style={[
                      styles.serviceRow,
                      {
                        borderColor: active ? theme.colors.primary : theme.colors.borderSoft,
                        backgroundColor: theme.colors.surfaceElevated,
                      },
                    ]}
                  >
                    <Text style={[styles.serviceName, { color: theme.colors.textPrimary }]}>{service.name}</Text>
                    <Text style={[styles.serviceMeta, { color: theme.colors.textSecondary }]}>
                      {service.durationLabel} · {service.priceLabel}
                    </Text>
                  </Pressable>
                );
              })}

              {visibleServices.length === 0 ? (
                <Text style={[styles.errorText, { color: theme.colors.textSecondary }]}>
                  В этой категории пока нет услуг.
                </Text>
              ) : null}
            </View>

            <View style={styles.blockGap}>
              <Text style={[styles.blockLabel, { color: theme.colors.textMuted }]}>Дата</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
                {dates.map((date) => {
                  const active = date === selectedDate;

                  return (
                    <Pressable
                      key={date}
                      onPress={() => setSelectedDate(date)}
                      style={[
                        styles.dateChip,
                        { backgroundColor: active ? theme.colors.primary : theme.colors.surfaceMuted },
                      ]}
                    >
                      <Text style={[styles.dateWeekday, { color: active ? '#fffaf2' : theme.colors.textMuted }]}>
                        {formatWeekdayShort(date)}
                      </Text>
                      <Text style={[styles.dateDay, { color: active ? '#fffaf2' : theme.colors.textPrimary }]}>
                        {formatDateLabel(date).split(' ')[0]}
                      </Text>
                    </Pressable>
                  );
                })}
              </ScrollView>
            </View>

            <View style={styles.blockGap}>
              <Text style={[styles.blockLabel, { color: theme.colors.textMuted }]}>Время</Text>

              {!selectedService ? (
                <Text style={[styles.errorText, { color: theme.colors.textSecondary }]}>
                  Сначала выберите услугу.
                </Text>
              ) : loadingSlots ? (
                <ActivityIndicator color={theme.colors.primary} style={styles.loader} />
              ) : slotsError ? (
                <Text style={[styles.errorText, { color: theme.colors.textSecondary }]}>{slotsError}</Text>
              ) : slots.length === 0 ? (
                <Text style={[styles.errorText, { color: theme.colors.textSecondary }]}>
                  На эту дату свободного времени нет — попробуйте другой день.
                </Text>
              ) : (
                <View style={styles.slotGrid}>
                  {slots.map((slot) => {
                    const active = slot === selectedTime;

                    return (
                      <Pressable
                        key={slot}
                        onPress={() => setSelectedTime(slot)}
                        style={[
                          styles.slotChip,
                          {
                            backgroundColor: active ? theme.colors.primary : theme.colors.surfaceMuted,
                            borderColor: active ? theme.colors.primary : theme.colors.borderSoft,
                          },
                        ]}
                      >
                        <Text style={[styles.slotText, { color: active ? '#fffaf2' : theme.colors.textPrimary }]}>
                          {slot}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
              )}
            </View>

            <PrimaryButton
              disabled={!canContinue}
              onPress={handleContinue}
              theme={theme}
              title="Продолжить"
              style={styles.continueButton}
            />
          </>
        )}
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    paddingHorizontal: 18,
    paddingTop: 8,
    gap: 22,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  backButton: {
    paddingVertical: 10,
    paddingRight: 8,
    minWidth: 52,
  },
  backSpacer: {
    minWidth: 52,
  },
  backText: {
    fontSize: 15,
    fontWeight: '600',
  },
  title: {
    fontSize: 18,
    fontWeight: '800',
  },
  loader: {
    marginTop: 24,
  },
  blockGap: {
    gap: 12,
  },
  blockLabel: {
    fontSize: 13,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  chipRow: {
    gap: 10,
    paddingRight: 8,
  },
  chip: {
    borderRadius: 999,
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  chipText: {
    fontSize: 14,
    fontWeight: '700',
  },
  serviceRow: {
    borderWidth: 1,
    borderRadius: 20,
    paddingHorizontal: 16,
    paddingVertical: 14,
    gap: 4,
  },
  serviceName: {
    fontSize: 16,
    fontWeight: '700',
  },
  serviceMeta: {
    fontSize: 13,
  },
  dateChip: {
    borderRadius: 18,
    paddingHorizontal: 14,
    paddingVertical: 10,
    alignItems: 'center',
    minWidth: 56,
    gap: 4,
  },
  dateWeekday: {
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  dateDay: {
    fontSize: 16,
    fontWeight: '800',
  },
  slotGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  slotChip: {
    borderWidth: 1,
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  slotText: {
    fontSize: 15,
    fontWeight: '700',
  },
  continueButton: {
    marginTop: 4,
    marginBottom: 24,
  },
  errorText: {
    fontSize: 14,
    lineHeight: 20,
  },
});
