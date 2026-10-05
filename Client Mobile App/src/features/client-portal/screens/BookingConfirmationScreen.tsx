import { Ionicons } from '@expo/vector-icons';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { ApiError } from '../../../shared/api/http';
import { formatDateLabel } from '../../../shared/format/ruDate';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { ScreenHeader } from '../../../shared/ui/ScreenHeader';
import { SectionCard } from '../../../shared/ui/SectionCard';
import { TextField } from '../../../shared/ui/TextField';
import { useAppTheme } from '../../../theme/theme';
import { clientPortalApi } from '../api/clientPortalApi';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'BookingConfirmation'>;

type Stage = 'review' | 'submitting' | 'booked' | 'conflict' | 'waitlisted' | 'error';

export function BookingConfirmationScreen({ navigation, route }: Props) {
  const { token, master } = useClientPortal();
  const theme = useAppTheme(master?.branding);
  const { serviceId, serviceLabel, date, time } = route.params;

  const [note, setNote] = useState('');
  const [stage, setStage] = useState<Stage>('review');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const handleConfirm = async () => {
    if (!token) {
      return;
    }

    setStage('submitting');
    setErrorMessage(null);

    try {
      await clientPortalApi.createAppointment(token, {
        service_id: serviceId ?? undefined,
        date,
        time,
        note: note.trim() || undefined,
      });

      setStage('booked');
    } catch (error) {
      if (error instanceof ApiError && error.status === 422 && error.payload?.error?.code === 'slot_unavailable') {
        setStage('conflict');
        return;
      }

      const message = error instanceof Error ? error.message : 'Не удалось создать запись.';
      setErrorMessage(message);
      setStage('error');
    }
  };

  const handleJoinWaitlist = async () => {
    if (!token || serviceId === null) {
      return;
    }

    setStage('submitting');

    try {
      await clientPortalApi.createWaitlist(token, {
        service_id: serviceId,
        preferred_dates: [date],
        notes: note.trim() || undefined,
      });

      setStage('waitlisted');
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Не удалось встать в лист ожидания.';
      setErrorMessage(message);
      setStage('error');
    }
  };

  const colors = theme.colors;

  if (stage === 'booked' || stage === 'waitlisted') {
    return (
      <ScreenContainer theme={theme} scrollable={false}>
        <View style={styles.centeredRoot}>
          <View style={[styles.successBadge, { backgroundColor: colors.accentSoft }]}>
            <Ionicons
              name={stage === 'booked' ? 'checkmark' : 'time-outline'}
              size={44}
              color={colors.primary}
            />
          </View>
          <Text style={[styles.successTitle, { color: colors.textPrimary }]}>
            {stage === 'booked' ? 'Вы записаны' : 'Вы в листе ожидания'}
          </Text>
          <Text style={[styles.successBody, { color: colors.textSecondary }]}>
            {stage === 'booked'
              ? `${serviceLabel}\n${formatDateLabel(date)} в ${time}`
              : 'Мастер свяжется, как только появится подходящее окно.'}
          </Text>
          <View style={styles.successActions}>
            <PrimaryButton
              onPress={() => navigation.navigate('Home')}
              theme={theme}
              title="На главную"
            />
            <PrimaryButton
              onPress={() => navigation.navigate('Appointments')}
              theme={theme}
              title="Мои записи"
              variant="secondary"
            />
          </View>
        </View>
      </ScreenContainer>
    );
  }

  const summaryRows = [
    { icon: 'sparkles-outline' as const, label: 'Услуга', value: serviceLabel },
    { icon: 'calendar-outline' as const, label: 'Дата', value: formatDateLabel(date) },
    { icon: 'time-outline' as const, label: 'Время', value: time },
  ];

  const footer = stage === 'conflict' ? undefined : (
    <View style={[styles.footer, { backgroundColor: colors.surface, borderTopColor: colors.borderSoft }]}>
      <PrimaryButton
        disabled={stage === 'submitting'}
        onPress={handleConfirm}
        theme={theme}
        title={stage === 'submitting' ? 'Записываем…' : 'Подтвердить запись'}
      />
    </View>
  );

  return (
    <ScreenContainer theme={theme} footer={footer}>
      <View style={styles.root}>
        <ScreenHeader theme={theme} title="Подтверждение" onBack={() => navigation.goBack()} />

        <View style={[styles.summary, { backgroundColor: colors.surface, borderColor: colors.borderSoft }]}>
          {summaryRows.map((row, index) => (
            <View
              key={row.label}
              style={[
                styles.summaryRow,
                index > 0 ? { borderTopWidth: 1, borderTopColor: colors.borderSoft } : null,
              ]}
            >
              <View style={[styles.summaryIcon, { backgroundColor: colors.accentSoft }]}>
                <Ionicons name={row.icon} size={20} color={colors.primary} />
              </View>
              <View style={styles.summaryText}>
                <Text style={[styles.summaryLabel, { color: colors.textSecondary }]}>{row.label}</Text>
                <Text style={[styles.summaryValue, { color: colors.textPrimary }]}>{row.value}</Text>
              </View>
            </View>
          ))}
        </View>

        {stage === 'conflict' ? (
          <SectionCard theme={theme}>
            <Text style={[styles.conflictTitle, { color: colors.textPrimary }]}>
              Это время только что заняли
            </Text>
            <Text style={[styles.errorText, { color: colors.textSecondary }]}>
              {serviceId !== null
                ? 'Можно встать в лист ожидания на эту услугу — мастер свяжется, если появится окно.'
                : 'Выберите другое время.'}
            </Text>
            <View style={styles.conflictActions}>
              {serviceId !== null ? (
                <PrimaryButton
                  onPress={handleJoinWaitlist}
                  theme={theme}
                  title="В лист ожидания"
                />
              ) : null}
              <PrimaryButton
                onPress={() => navigation.goBack()}
                theme={theme}
                title="Выбрать другое время"
                variant="secondary"
              />
            </View>
          </SectionCard>
        ) : (
          <>
            <TextField
              label="Комментарий мастеру"
              multiline
              numberOfLines={3}
              onChangeText={setNote}
              placeholder="Необязательно. Например: аллергия на определённый лак"
              style={styles.noteInput}
              theme={theme}
              value={note}
            />

            {stage === 'error' && errorMessage ? (
              <Text style={[styles.errorText, { color: colors.danger }]}>{errorMessage}</Text>
            ) : null}
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
    gap: 20,
  },
  summary: {
    borderWidth: 1,
    borderRadius: 20,
    paddingHorizontal: 18,
  },
  summaryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    paddingVertical: 14,
  },
  summaryIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  summaryText: {
    flex: 1,
  },
  summaryLabel: {
    fontSize: 13,
  },
  summaryValue: {
    fontSize: 17,
    lineHeight: 23,
    fontWeight: '600',
    marginTop: 1,
  },
  noteInput: {
    minHeight: 96,
    textAlignVertical: 'top',
  },
  errorText: {
    fontSize: 14,
    lineHeight: 20,
    marginTop: 8,
  },
  conflictTitle: {
    fontSize: 18,
    fontWeight: '700',
  },
  conflictActions: {
    gap: 10,
    marginTop: 16,
  },
  footer: {
    borderTopWidth: 1,
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  centeredRoot: {
    flex: 1,
    paddingHorizontal: 24,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  successBadge: {
    width: 96,
    height: 96,
    borderRadius: 48,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  successTitle: {
    fontSize: 26,
    fontWeight: '700',
    letterSpacing: -0.4,
    textAlign: 'center',
  },
  successBody: {
    fontSize: 16,
    lineHeight: 24,
    textAlign: 'center',
  },
  successActions: {
    alignSelf: 'stretch',
    gap: 10,
    marginTop: 28,
  },
});
