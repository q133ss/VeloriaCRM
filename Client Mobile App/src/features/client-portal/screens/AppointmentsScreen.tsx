import { Ionicons } from '@expo/vector-icons';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { formatDateLabel } from '../../../shared/format/ruDate';
import { BottomNav } from '../../../shared/ui/BottomNav';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { SectionCard } from '../../../shared/ui/SectionCard';
import { SegmentedControl } from '../../../shared/ui/SegmentedControl';
import { useAppTheme } from '../../../theme/theme';
import { AppointmentListItemDto } from '../api/contracts';
import { clientPortalApi } from '../api/clientPortalApi';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Appointments'>;
type Tab = 'upcoming' | 'history';

function statusLabel(status: string): string {
  return status === 'scheduled' ? 'Подтверждено' : status;
}

export function AppointmentsScreen({ navigation }: Props) {
  const { token, master, unreadChatCount } = useClientPortal();
  const theme = useAppTheme(master?.branding);

  const [tab, setTab] = useState<Tab>('upcoming');
  const [appointments, setAppointments] = useState<AppointmentListItemDto[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    let cancelled = false;

    async function load() {
      setError(null);

      try {
        const response = await clientPortalApi.getAppointments(token as string);

        if (!cancelled) {
          setAppointments(response.data.appointments);
        }
      } catch (fetchError) {
        if (!cancelled) {
          const message = fetchError instanceof Error ? fetchError.message : 'Не удалось загрузить записи.';
          setError(message);
        }
      }
    }

    void load();

    return () => {
      cancelled = true;
    };
  }, [token]);

  const visible = (appointments ?? []).filter((item) => (tab === 'upcoming' ? item.is_upcoming : !item.is_upcoming));

  const colors = theme.colors;

  return (
    <ScreenContainer
      theme={theme}
      footer={
        <BottomNav
          theme={theme}
          active="Appointments"
          showChat={master?.hasChat ?? false}
          badges={{ Chat: unreadChatCount }}
          onNavigate={(next) => navigation.navigate(next)}
        />
      }
    >
      <View style={styles.root}>
        <Text style={[styles.title, { color: colors.textPrimary }]}>Мои записи</Text>

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
          <ActivityIndicator color={colors.primary} style={styles.loader} />
        ) : error ? (
          <Text style={[styles.emptyText, { color: colors.textSecondary }]}>{error}</Text>
        ) : visible.length === 0 ? (
          <View style={styles.emptyState}>
            <View style={[styles.emptyIcon, { backgroundColor: colors.accentSoft }]}>
              <Ionicons name="calendar-outline" size={28} color={colors.primary} />
            </View>
            <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>
              {tab === 'upcoming' ? 'Нет предстоящих записей' : 'История пока пуста'}
            </Text>
            <Text style={[styles.emptyText, { color: colors.textSecondary }]}>
              {tab === 'upcoming'
                ? 'Выберите услугу и удобное время — это займёт меньше минуты.'
                : 'Здесь появятся ваши прошедшие визиты.'}
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
              <View
                key={appointment.id}
                style={[styles.item, { backgroundColor: colors.surface, borderColor: colors.borderSoft }]}
              >
                <View style={styles.itemTop}>
                  <Text style={[styles.itemWhen, { color: colors.textPrimary }]}>
                    {appointment.date ? formatDateLabel(appointment.date) : '—'}
                    {appointment.time ? ` · ${appointment.time}` : ''}
                  </Text>
                  <View style={[styles.statusPill, { backgroundColor: colors.accentSoft }]}>
                    <Text style={[styles.statusText, { color: colors.textPrimary }]}>
                      {statusLabel(appointment.status)}
                    </Text>
                  </View>
                </View>
                <Text style={[styles.itemService, { color: colors.textSecondary }]}>
                  {appointment.service_label}
                </Text>
              </View>
            ))}
          </View>
        )}
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 16,
    gap: 20,
  },
  title: {
    fontSize: 28,
    fontWeight: '700',
    letterSpacing: -0.5,
  },
  loader: {
    marginTop: 24,
  },
  list: {
    gap: 12,
  },
  item: {
    borderWidth: 1,
    borderRadius: 20,
    padding: 18,
    gap: 6,
  },
  itemTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: 12,
  },
  itemWhen: {
    flex: 1,
    fontSize: 17,
    fontWeight: '700',
  },
  itemService: {
    fontSize: 15,
  },
  statusPill: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
  },
  emptyState: {
    alignItems: 'center',
    paddingTop: 40,
    gap: 10,
  },
  emptyIcon: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 6,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '700',
  },
  emptyText: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    paddingHorizontal: 16,
  },
  emptyButton: {
    alignSelf: 'stretch',
    marginTop: 14,
  },
});
