import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useFocusEffect } from '@react-navigation/native';
import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, AppState, Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { formatDateLabel } from '../../../shared/format/ruDate';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { SectionCard } from '../../../shared/ui/SectionCard';
import { SegmentedControl } from '../../../shared/ui/SegmentedControl';
import { useAppTheme } from '../../../theme/theme';
import { AppointmentListItemDto } from '../api/contracts';
import { clientPortalApi } from '../api/clientPortalApi';
import { appointmentStatusLabel, canPayNow, formatRubles, isAwaitingPayment, minutesLeft } from '../model/appointmentStatus';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Appointments'>;
type Tab = 'upcoming' | 'history';

export function AppointmentsScreen({ navigation }: Props) {
  const { token, master } = useClientPortal();
  const theme = useAppTheme(master?.branding);

  const [tab, setTab] = useState<Tab>('upcoming');
  const [appointments, setAppointments] = useState<AppointmentListItemDto[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [now, setNow] = useState(() => Date.now());

  const load = useCallback(async () => {
    if (!token) {
      return;
    }

    setError(null);

    try {
      const response = await clientPortalApi.getAppointments(token);

      setAppointments(response.data.appointments);
      setNow(Date.now());
    } catch (fetchError) {
      const message = fetchError instanceof Error ? fetchError.message : 'Не удалось загрузить записи.';
      setError(message);
    }
  }, [token]);

  // Coming back to this screen, or back to the app from the payment page, shows the
  // booking as it is now: "Ждёт оплаты" turns into "Подтверждено" without a pull-to-refresh.
  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  useEffect(() => {
    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') {
        void load();
      }
    });

    return () => subscription.remove();
  }, [load]);

  const handlePay = async (url: string) => {
    try {
      await Linking.openURL(url);
    } catch {
      Alert.alert('Не получилось открыть оплату', 'Попробуйте ещё раз чуть позже.');
    }
  };

  const visible = (appointments ?? []).filter((item) => (tab === 'upcoming' ? item.is_upcoming : !item.is_upcoming));

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.root}>
        <View style={styles.topBar}>
          <Pressable onPress={() => navigation.goBack()} style={styles.backButton}>
            <Text style={[styles.backText, { color: theme.colors.textSecondary }]}>Назад</Text>
          </Pressable>
          <Text style={[styles.title, { color: theme.colors.textPrimary }]}>Мои записи</Text>
          <View style={styles.backSpacer} />
        </View>

        <SegmentedControl
          theme={theme}
          value={tab}
          onChange={setTab}
          options={[
            { label: 'Предстоящие', value: 'upcoming' },
            { label: 'История', value: 'history' },
          ]}
        />

        {appointments === null && !error ? (
          <ActivityIndicator color={theme.colors.primary} style={styles.loader} />
        ) : error ? (
          <Text style={[styles.emptyText, { color: theme.colors.textSecondary }]}>{error}</Text>
        ) : visible.length === 0 ? (
          <View style={styles.emptyState}>
            <Text style={[styles.emptyText, { color: theme.colors.textSecondary }]}>
              {tab === 'upcoming' ? 'Пока нет предстоящих записей.' : 'История записей пока пуста.'}
            </Text>
            {tab === 'upcoming' ? (
              <PrimaryButton
                onPress={() => navigation.navigate('Booking', {})}
                theme={theme}
                title="Записаться"
                style={styles.emptyButton}
              />
            ) : null}
          </View>
        ) : (
          <View style={styles.list}>
            {visible.map((appointment) => (
              <SectionCard key={appointment.id} theme={theme}>
                <View style={styles.itemRow}>
                  <View style={styles.flexOne}>
                    <Text style={[styles.itemTitle, { color: theme.colors.textPrimary }]}>
                      {appointment.service_label}
                    </Text>
                    <Text style={[styles.itemMeta, { color: theme.colors.textSecondary }]}>
                      {appointment.date ? formatDateLabel(appointment.date) : '—'}
                      {appointment.time ? ` · ${appointment.time}` : ''}
                    </Text>
                  </View>
                  <View style={[styles.statusPill, { backgroundColor: theme.colors.accentSoft }]}>
                    <Text style={[styles.statusText, { color: theme.colors.textPrimary }]}>
                      {appointmentStatusLabel(appointment)}
                    </Text>
                  </View>
                </View>

                {isAwaitingPayment(appointment) && appointment.is_upcoming ? (
                  <View style={styles.paymentBlock}>
                    <Text style={[styles.itemMeta, { color: theme.colors.textSecondary }]}>
                      {canPayNow(appointment, now)
                        ? `Предоплата ${formatRubles(appointment.payment?.amount ?? 0)}. Время закреплено ещё на ${minutesLeft(appointment.payment?.expires_at, now)} мин.`
                        : 'Время закрепления вышло. Если вы уже оплатили, статус скоро обновится.'}
                    </Text>
                    {canPayNow(appointment, now) ? (
                      <PrimaryButton
                        onPress={() => void handlePay(appointment.payment?.confirmation_url as string)}
                        theme={theme}
                        title={`Оплатить ${formatRubles(appointment.payment?.amount ?? 0)}`}
                        style={styles.payButton}
                      />
                    ) : null}
                  </View>
                ) : appointment.payment?.state === 'paid' ? (
                  <Text style={[styles.itemMeta, { color: theme.colors.textSecondary }]}>
                    {`Предоплата ${formatRubles(appointment.payment.amount)} внесена`}
                  </Text>
                ) : null}
              </SectionCard>
            ))}
          </View>
        )}
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    paddingHorizontal: 18,
    paddingTop: 8,
    gap: 18,
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
  list: {
    gap: 12,
  },
  itemRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: 12,
  },
  flexOne: {
    flex: 1,
  },
  itemTitle: {
    fontSize: 17,
    fontWeight: '800',
  },
  itemMeta: {
    fontSize: 14,
    marginTop: 6,
  },
  paymentBlock: {
    marginTop: 12,
    gap: 12,
  },
  payButton: {
    alignSelf: 'stretch',
  },
  statusPill: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '700',
  },
  emptyState: {
    alignItems: 'center',
    paddingTop: 32,
    gap: 16,
  },
  emptyText: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
  },
  emptyButton: {
    alignSelf: 'stretch',
  },
});
