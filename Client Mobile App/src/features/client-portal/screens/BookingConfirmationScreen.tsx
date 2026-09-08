import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { ApiError } from '../../../shared/api/http';
import { formatDateLabel } from '../../../shared/format/ruDate';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
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

  if (stage === 'booked' || stage === 'waitlisted') {
    return (
      <ScreenContainer theme={theme}>
        <View style={styles.centeredRoot}>
          <Text style={[styles.successTitle, { color: theme.colors.textPrimary }]}>
            {stage === 'booked' ? 'Запись создана' : 'Вы в листе ожидания'}
          </Text>
          <Text style={[styles.successBody, { color: theme.colors.textSecondary }]}>
            {stage === 'booked'
              ? `${serviceLabel} · ${formatDateLabel(date)} в ${time}`
              : 'Мастер свяжется, как только появится подходящее окно.'}
          </Text>
          <PrimaryButton
            onPress={() => navigation.navigate('Home')}
            theme={theme}
            title="На главный экран"
            style={styles.successButton}
          />
        </View>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.root}>
        <View style={styles.topBar}>
          <Pressable onPress={() => navigation.goBack()} style={styles.backButton}>
            <Text style={[styles.backText, { color: theme.colors.textSecondary }]}>Назад</Text>
          </Pressable>
          <Text style={[styles.title, { color: theme.colors.textPrimary }]}>Подтверждение</Text>
          <View style={styles.backSpacer} />
        </View>

        <SectionCard theme={theme}>
          <Text style={[styles.summaryLabel, { color: theme.colors.textMuted }]}>Услуга</Text>
          <Text style={[styles.summaryValue, { color: theme.colors.textPrimary }]}>{serviceLabel}</Text>

          <Text style={[styles.summaryLabel, styles.summarySpacing, { color: theme.colors.textMuted }]}>
            Дата и время
          </Text>
          <Text style={[styles.summaryValue, { color: theme.colors.textPrimary }]}>
            {formatDateLabel(date)} · {time}
          </Text>
        </SectionCard>

        {stage === 'conflict' ? (
          <SectionCard theme={theme}>
            <Text style={[styles.summaryValue, { color: theme.colors.textPrimary }]}>
              Это время уже заняли, пока вы выбирали.
            </Text>
            <Text style={[styles.errorText, { color: theme.colors.textSecondary }]}>
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
                  style={styles.flexButton}
                />
              ) : null}
              <PrimaryButton
                onPress={() => navigation.goBack()}
                theme={theme}
                title="Другое время"
                variant="secondary"
                style={styles.flexButton}
              />
            </View>
          </SectionCard>
        ) : (
          <>
            <TextField
              label="Комментарий мастеру (необязательно)"
              multiline
              numberOfLines={3}
              onChangeText={setNote}
              placeholder="Например: аллергия на определенный лак"
              theme={theme}
              value={note}
            />

            {stage === 'error' && errorMessage ? (
              <Text style={[styles.errorText, { color: theme.colors.textSecondary }]}>{errorMessage}</Text>
            ) : null}

            <PrimaryButton
              disabled={stage === 'submitting'}
              onPress={handleConfirm}
              theme={theme}
              title={stage === 'submitting' ? 'Записываем...' : 'Подтвердить запись'}
              style={styles.confirmButton}
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
  summaryLabel: {
    fontSize: 13,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  summarySpacing: {
    marginTop: 16,
  },
  summaryValue: {
    fontSize: 20,
    lineHeight: 26,
    fontWeight: '800',
    marginTop: 6,
  },
  confirmButton: {
    marginTop: 4,
    marginBottom: 24,
  },
  errorText: {
    fontSize: 14,
    lineHeight: 20,
    marginTop: 10,
  },
  conflictActions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 16,
  },
  flexButton: {
    flex: 1,
  },
  centeredRoot: {
    flex: 1,
    paddingHorizontal: 24,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  successTitle: {
    fontSize: 24,
    fontWeight: '800',
    textAlign: 'center',
  },
  successBody: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
  },
  successButton: {
    marginTop: 20,
    alignSelf: 'stretch',
  },
});
