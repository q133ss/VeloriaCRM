import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useEffect, useRef, useState } from 'react';
import { Alert, AppState, Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { ApiError } from '../../../shared/api/http';
import { formatDateLabel } from '../../../shared/format/ruDate';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { SectionCard } from '../../../shared/ui/SectionCard';
import { TextField } from '../../../shared/ui/TextField';
import { useAppTheme } from '../../../theme/theme';
import { BookingPaymentDto, PrepaymentQuoteDto } from '../api/contracts';
import { clientPortalApi } from '../api/clientPortalApi';
import { formatRubles, minutesLeft } from '../model/appointmentStatus';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'BookingConfirmation'>;

type Stage = 'review' | 'submitting' | 'booked' | 'awaitingPayment' | 'conflict' | 'waitlisted' | 'error';

export function BookingConfirmationScreen({ navigation, route }: Props) {
  const { token, master } = useClientPortal();
  const theme = useAppTheme(master?.branding);
  const { serviceId, serviceLabel, date, time } = route.params;

  const [note, setNote] = useState('');
  const [stage, setStage] = useState<Stage>('review');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [quote, setQuote] = useState<PrepaymentQuoteDto | null>(null);
  const [payment, setPayment] = useState<BookingPaymentDto | null>(null);
  const [appointmentId, setAppointmentId] = useState<number | null>(null);
  const [now, setNow] = useState(() => Date.now());

  // What this booking would ask up front, told before she confirms. If it cannot be
  // fetched the booking still works: the answer to the booking itself is what counts.
  useEffect(() => {
    if (!token || serviceId === null) {
      return;
    }

    let cancelled = false;

    clientPortalApi
      .getPrepaymentQuote(token, { service_id: serviceId, date, time })
      .then((response) => {
        if (!cancelled) {
          setQuote(response.data);
        }
      })
      .catch(() => undefined);

    return () => {
      cancelled = true;
    };
  }, [token, serviceId, date, time]);

  const prepay = quote?.required && quote.amount ? quote : null;

  const handleConfirm = async () => {
    if (!token) {
      return;
    }

    setStage('submitting');
    setErrorMessage(null);

    try {
      const response = await clientPortalApi.createAppointment(token, {
        service_id: serviceId ?? undefined,
        date,
        time,
        note: note.trim() || undefined,
      });

      // The time is held, not booked, until the prepayment arrives.
      if (response.data.payment) {
        setPayment(response.data.payment);
        setAppointmentId(response.data.appointment.id);
        setNow(Date.now());
        setStage('awaitingPayment');
        return;
      }

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

  const handlePay = async () => {
    if (!payment) {
      return;
    }

    try {
      await Linking.openURL(payment.confirmation_url);
    } catch {
      Alert.alert('Не получилось открыть оплату', 'Попробуйте ещё раз или оплатите из раздела «Мои записи».');
    }
  };

  // She pays in the browser and comes back: look again, so "one more step" turns
  // into "booked" (or into "the time was released") without her doing anything.
  const stageRef = useRef(stage);
  stageRef.current = stage;

  useEffect(() => {
    if (stage !== 'awaitingPayment' || !token || appointmentId === null) {
      return;
    }

    const check = async () => {
      setNow(Date.now());

      try {
        const response = await clientPortalApi.getAppointments(token);
        const item = response.data.appointments.find((appointment) => appointment.id === appointmentId);

        if (stageRef.current !== 'awaitingPayment' || !item) {
          return;
        }

        if (item.payment?.state === 'paid') {
          setStage('booked');
        } else if (item.status === 'cancelled') {
          setErrorMessage('Предоплата не поступила вовремя, и время освободилось. Можно записаться заново.');
          setStage('error');
        }
      } catch {
        // Offline for a moment: the next return to the app asks again.
      }
    };

    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') {
        void check();
      }
    });
    const ticker = setInterval(() => setNow(Date.now()), 30000);

    return () => {
      subscription.remove();
      clearInterval(ticker);
    };
  }, [stage, token, appointmentId]);

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

  if (stage === 'awaitingPayment' && payment) {
    const left = minutesLeft(payment.expires_at, now);

    return (
      <ScreenContainer theme={theme}>
        <View style={styles.centeredRoot}>
          <Text style={[styles.successTitle, { color: theme.colors.textPrimary }]}>Остался один шаг</Text>
          <Text style={[styles.successBody, { color: theme.colors.textSecondary }]}>
            {`${serviceLabel} · ${formatDateLabel(date)} в ${time}`}
          </Text>
          <Text style={[styles.successBody, { color: theme.colors.textPrimary }]}>
            {`Оплатите предоплату ${formatRubles(payment.amount)} — и запись будет подтверждена. Она входит в стоимость услуги.`}
          </Text>
          {left !== null ? (
            <Text style={[styles.successBody, { color: theme.colors.textMuted }]}>
              {left > 0
                ? `Время закреплено за вами ещё на ${left} мин. Если оплата не пройдёт, оно освободится.`
                : 'Время закрепления вышло. Если вы уже оплатили, статус скоро обновится.'}
            </Text>
          ) : null}
          <PrimaryButton
            onPress={handlePay}
            theme={theme}
            title={`Оплатить ${formatRubles(payment.amount)}`}
            style={styles.successButton}
          />
          <PrimaryButton
            onPress={() => navigation.navigate('Appointments')}
            theme={theme}
            title="Мои записи"
            variant="secondary"
            style={styles.secondaryButton}
          />
          <Text style={[styles.hint, { color: theme.colors.textMuted }]}>
            После оплаты вернитесь в приложение — статус обновится сам.
          </Text>
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

          {prepay && stage !== 'conflict' ? (
            <>
              <Text style={[styles.summaryLabel, styles.summarySpacing, { color: theme.colors.textMuted }]}>
                Предоплата
              </Text>
              <Text style={[styles.summaryValue, { color: theme.colors.textPrimary }]}>
                {formatRubles(prepay.amount as number)}
              </Text>
              <Text style={[styles.errorText, { color: theme.colors.textSecondary }]}>
                {`Входит в стоимость услуги. После записи время закрепится за вами на ${prepay.hold_minutes ?? 15} мин — за это время нужно оплатить.`}
              </Text>
            </>
          ) : null}
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
              title={
                stage === 'submitting'
                  ? 'Записываем...'
                  : prepay
                    ? `Записаться и оплатить ${formatRubles(prepay.amount as number)}`
                    : 'Подтвердить запись'
              }
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
  secondaryButton: {
    alignSelf: 'stretch',
  },
  hint: {
    fontSize: 13,
    lineHeight: 18,
    textAlign: 'center',
    marginTop: 4,
  },
});
