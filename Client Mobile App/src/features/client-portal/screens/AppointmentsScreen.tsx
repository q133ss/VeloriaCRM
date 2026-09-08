import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { formatDateLabel } from '../../../shared/format/ruDate';
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
  const { token, master } = useClientPortal();
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
                      {statusLabel(appointment.status)}
                    </Text>
                  </View>
                </View>
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
