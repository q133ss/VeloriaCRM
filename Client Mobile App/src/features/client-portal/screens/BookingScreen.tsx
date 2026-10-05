import { Ionicons } from '@expo/vector-icons';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { addDays, formatDateLabel, formatWeekdayShort, toIsoDate } from '../../../shared/format/ruDate';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { ScreenHeader } from '../../../shared/ui/ScreenHeader';
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

  const selectedServiceLabel = selectedService === ANY_SERVICE
    ? 'Любая услуга'
    : selectedService?.name ?? null;
  const summary = canContinue
    ? `${selectedServiceLabel} · ${formatDateLabel(selectedDate)}, ${selectedTime}`
    : 'Выберите услугу, дату и время';

  const colors = theme.colors;

  const footer = (
    <View style={[styles.footer, { backgroundColor: colors.surface, borderTopColor: colors.borderSoft }]}>
      <Text
        numberOfLines={1}
        style={[styles.footerSummary, { color: canContinue ? colors.textPrimary : colors.textSecondary }]}
      >
        {summary}
      </Text>
      <PrimaryButton
        disabled={!canContinue}
        onPress={handleContinue}
        theme={theme}
        title="Продолжить"
      />
    </View>
  );

  const renderServiceRow = (key: string, name: string, meta: string, active: boolean, onPress: () => void, index: number) => (
    <Pressable
      key={key}
      accessibilityRole="radio"
      accessibilityState={{ selected: active }}
      onPress={onPress}
      style={({ pressed }) => [
        styles.serviceRow,
        index > 0 ? { borderTopWidth: 1, borderTopColor: colors.borderSoft } : null,
        pressed ? { backgroundColor: colors.surfaceMuted } : null,
      ]}
    >
      <View style={styles.serviceText}>
        <Text style={[styles.serviceName, { color: colors.textPrimary }]}>{name}</Text>
        <Text style={[styles.serviceMeta, { color: colors.textSecondary }]}>{meta}</Text>
      </View>
      <Ionicons
        name={active ? 'radio-button-on' : 'radio-button-off'}
        size={24}
        color={active ? colors.primary : colors.textMuted}
      />
    </Pressable>
  );

  const chip = (label: string, active: boolean, onPress: () => void, key: string) => (
    <Pressable
      key={key}
      accessibilityRole="button"
      accessibilityState={{ selected: active }}
      onPress={onPress}
      style={[
        styles.chip,
        {
          backgroundColor: active ? colors.primary : colors.surface,
          borderColor: active ? colors.primary : colors.borderSoft,
        },
      ]}
    >
      <Text style={[styles.chipText, { color: active ? '#ffffff' : colors.textSecondary }]}>{label}</Text>
    </Pressable>
  );

  return (
    <ScreenContainer theme={theme} footer={footer}>
      <View style={styles.root}>
        <ScreenHeader theme={theme} title="Новая запись" onBack={() => navigation.goBack()} />

        {loadingCatalog ? (
          <ActivityIndicator color={colors.primary} style={styles.loader} />
        ) : catalogError ? (
          <Text style={[styles.hintText, { color: colors.textSecondary }]}>{catalogError}</Text>
        ) : (
          <>
            <View style={styles.block}>
              <Text style={[styles.blockTitle, { color: colors.textPrimary }]}>Услуга</Text>

              {categories.length > 0 ? (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
                  {chip('Все', selectedCategoryId === null, () => setSelectedCategoryId(null), 'all')}
                  {categories.map((category) =>
                    chip(category.name, selectedCategoryId === category.id, () => setSelectedCategoryId(category.id), String(category.id)),
                  )}
                </ScrollView>
              ) : null}

              <View style={[styles.group, { backgroundColor: colors.surface, borderColor: colors.borderSoft }]}>
                {renderServiceRow('any', 'Любая услуга', 'Подберём время на месте', selectedService === ANY_SERVICE, () => setSelectedService(ANY_SERVICE), 0)}
                {visibleServices.map((service, index) =>
                  renderServiceRow(
                    String(service.id),
                    service.name,
                    `${service.durationLabel} · ${service.priceLabel}`,
                    selectedService !== null && selectedService !== ANY_SERVICE && selectedService.id === service.id,
                    () => setSelectedService(service),
                    index + 1,
                  ),
                )}
              </View>

              {visibleServices.length === 0 ? (
                <Text style={[styles.hintText, { color: colors.textSecondary }]}>
                  В этой категории пока нет услуг.
                </Text>
              ) : null}
            </View>

            <View style={styles.block}>
              <Text style={[styles.blockTitle, { color: colors.textPrimary }]}>Дата</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
                {dates.map((date) => {
                  const active = date === selectedDate;

                  return (
                    <Pressable
                      key={date}
                      accessibilityRole="button"
                      accessibilityState={{ selected: active }}
                      onPress={() => setSelectedDate(date)}
                      style={[
                        styles.dateChip,
                        {
                          backgroundColor: active ? colors.primary : colors.surface,
                          borderColor: active ? colors.primary : colors.borderSoft,
                        },
                      ]}
                    >
                      <Text style={[styles.dateWeekday, { color: active ? '#ffffff' : colors.textMuted }]}>
                        {formatWeekdayShort(date)}
                      </Text>
                      <Text style={[styles.dateDay, { color: active ? '#ffffff' : colors.textPrimary }]}>
                        {formatDateLabel(date).split(' ')[0]}
                      </Text>
                    </Pressable>
                  );
                })}
              </ScrollView>
            </View>

            <View style={styles.block}>
              <Text style={[styles.blockTitle, { color: colors.textPrimary }]}>Время</Text>

              {!selectedService ? (
                <Text style={[styles.hintText, { color: colors.textSecondary }]}>
                  Сначала выберите услугу.
                </Text>
              ) : loadingSlots ? (
                <ActivityIndicator color={colors.primary} style={styles.loader} />
              ) : slotsError ? (
                <Text style={[styles.hintText, { color: colors.textSecondary }]}>{slotsError}</Text>
              ) : slots.length === 0 ? (
                <Text style={[styles.hintText, { color: colors.textSecondary }]}>
                  На эту дату свободного времени нет — попробуйте другой день.
                </Text>
              ) : (
                <View style={styles.slotGrid}>
                  {slots.map((slot) => {
                    const active = slot === selectedTime;

                    return (
                      <Pressable
                        key={slot}
                        accessibilityRole="button"
                        accessibilityState={{ selected: active }}
                        onPress={() => setSelectedTime(slot)}
                        style={[
                          styles.slotChip,
                          {
                            backgroundColor: active ? colors.primary : colors.surface,
                            borderColor: active ? colors.primary : colors.borderSoft,
                          },
                        ]}
                      >
                        <Text style={[styles.slotText, { color: active ? '#ffffff' : colors.textPrimary }]}>
                          {slot}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
              )}
            </View>
          </>
        )}
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 16,
    gap: 28,
  },
  loader: {
    marginTop: 16,
  },
  block: {
    gap: 12,
  },
  blockTitle: {
    fontSize: 18,
    fontWeight: '700',
    letterSpacing: -0.2,
  },
  chipRow: {
    gap: 8,
    paddingRight: 8,
  },
  chip: {
    borderRadius: 999,
    borderWidth: 1,
    paddingHorizontal: 16,
    paddingVertical: 9,
  },
  chipText: {
    fontSize: 14,
    fontWeight: '600',
  },
  group: {
    borderWidth: 1,
    borderRadius: 20,
    overflow: 'hidden',
  },
  serviceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 18,
    paddingVertical: 14,
    minHeight: 64,
  },
  serviceText: {
    flex: 1,
  },
  serviceName: {
    fontSize: 16,
    fontWeight: '600',
  },
  serviceMeta: {
    fontSize: 14,
    marginTop: 2,
  },
  dateChip: {
    borderRadius: 14,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 10,
    alignItems: 'center',
    minWidth: 56,
    gap: 2,
  },
  dateWeekday: {
    fontSize: 12,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  dateDay: {
    fontSize: 17,
    fontWeight: '700',
  },
  slotGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  slotChip: {
    width: '22.5%',
    borderWidth: 1,
    borderRadius: 12,
    paddingVertical: 12,
    alignItems: 'center',
  },
  slotText: {
    fontSize: 15,
    fontWeight: '600',
  },
  hintText: {
    fontSize: 14,
    lineHeight: 20,
  },
  footer: {
    borderTopWidth: 1,
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 12,
    gap: 10,
  },
  footerSummary: {
    fontSize: 14,
    fontWeight: '600',
    textAlign: 'center',
  },
});
