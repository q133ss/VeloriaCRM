import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { LinearGradient } from 'expo-linear-gradient';
import { useEffect, useMemo, useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { BrandSignature } from '../../../shared/ui/BrandSignature';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { TextField } from '../../../shared/ui/TextField';
import { useAppTheme } from '../../../theme/theme';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Auth'>;
type AuthStep = 'credentials' | 'otp';

export function AuthScreen({ navigation }: Props) {
  const theme = useAppTheme();
  const { authBusy, master, pendingAuth, requestLoginCode, confirmLoginCode, resetPendingAuth } = useClientPortal();

  const [step, setStep] = useState<AuthStep>('credentials');
  const [email, setEmail] = useState('');
  const [code, setCode] = useState('');

  useEffect(() => {
    setStep('credentials');
    setCode('');
    resetPendingAuth();
  }, [resetPendingAuth]);

  const codeDigits = useMemo(() => code.padEnd(6, ' ').slice(0, 6).split(''), [code]);

  if (!master) {
    return null;
  }

  const canRequestCode = email.includes('@');
  const canConfirmCode = code.trim().length >= 6;

  const handleRequestOtp = async () => {
    try {
      await requestLoginCode(email.trim());

      setStep('otp');
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Не удалось отправить код.';
      Alert.alert('Ошибка', message);
    }
  };

  const handleConfirmOtp = async () => {
    try {
      await confirmLoginCode(code.trim());
      navigation.reset({
        index: 0,
        routes: [{ name: 'Home' }],
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Не удалось подтвердить код.';
      Alert.alert('Ошибка', message);
    }
  };

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.root}>
        <View style={styles.topBar}>
          {navigation.canGoBack() ? (
            <Pressable onPress={() => navigation.goBack()} style={styles.backButton}>
              <Text style={[styles.backText, { color: theme.colors.textSecondary }]}>Назад</Text>
            </Pressable>
          ) : (
            <View style={styles.backSpacer} />
          )}
          <BrandSignature compact theme={theme} />
        </View>

        <LinearGradient
          colors={['#ff00fc', '#ff76fd']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.heroCard}
        >
          <View style={styles.heroGlowLarge} />
          <View style={styles.heroGlowSmall} />

          <Text style={styles.heroEyebrow}>{step === 'otp' ? 'Email verification' : 'Login'}</Text>
          <Text style={styles.heroTitle}>
            {step === 'otp' ? 'Подтвердите вход' : 'Вход в клиентский кабинет Veloria'}
          </Text>
          <Text style={styles.heroBody}>
            {step === 'otp'
              ? 'Введите код из письма, чтобы открыть свои записи, свободные окна и новости мастера.'
              : `Только вход. Клиент уже должен быть добавлен мастером в CRM ${master.studioName}.`}
          </Text>

          <View style={styles.heroDots}>
            <View style={styles.heroDotActive} />
            <View style={styles.heroDotIdle} />
            <View style={styles.heroDotIdle} />
          </View>
        </LinearGradient>

        <View
          style={[
            styles.formCard,
            {
              backgroundColor: theme.colors.surface,
              borderColor: theme.colors.borderSoft,
              shadowColor: theme.colors.shadow,
            },
          ]}
        >
          {step === 'credentials' ? (
            <>
              <Text style={[styles.formTitle, { color: theme.colors.textPrimary }]}>Welcome back</Text>
              <Text style={[styles.formSubtitle, { color: theme.colors.textSecondary }]}>
                Введите email, который мастер уже привязал к вашему профилю. Регистрации в приложении нет.
              </Text>

              <View style={styles.fields}>
                <TextField
                  autoCapitalize="none"
                  keyboardType="email-address"
                  label="Email"
                  onChangeText={setEmail}
                  placeholder="client@veloria.app"
                  theme={theme}
                  value={email}
                />
              </View>

              <PrimaryButton
                disabled={!canRequestCode || authBusy}
                onPress={handleRequestOtp}
                theme={theme}
                title={authBusy ? 'Отправляем код...' : 'Войти по email'}
                style={styles.primaryButton}
              />

              <Text style={[styles.helperText, { color: theme.colors.textMuted }]}>
                Если письма нет, значит мастер еще не добавил этот email в вашу карточку.
              </Text>
            </>
          ) : (
            <>
              <Text style={[styles.formTitle, { color: theme.colors.textPrimary }]}>Email verification</Text>
              <Text style={[styles.formSubtitle, { color: theme.colors.textSecondary }]}>
                Код отправлен на {pendingAuth?.email ?? email}. Введите 6-значный код из письма.
              </Text>

              <View style={styles.codePreviewRow}>
                {codeDigits.map((digit, index) => (
                  <View
                    key={index}
                    style={[
                      styles.codeCell,
                      {
                        borderColor: theme.colors.borderSoft,
                        backgroundColor: theme.colors.inputBackground,
                      },
                    ]}
                  >
                    <Text style={[styles.codeCellText, { color: theme.colors.textPrimary }]}>
                      {digit.trim() === '' ? '' : digit}
                    </Text>
                  </View>
                ))}
              </View>

              <TextField
                keyboardType="number-pad"
                label="Код из письма"
                maxLength={6}
                onChangeText={(value) => setCode(value.replace(/\D/g, '').slice(0, 6))}
                placeholder="425137"
                theme={theme}
                value={code}
              />

              <PrimaryButton
                disabled={!canConfirmCode || authBusy}
                onPress={handleConfirmOtp}
                theme={theme}
                title={authBusy ? 'Проверяем...' : 'Подтвердить вход'}
                style={styles.primaryButton}
              />

              <Pressable
                onPress={() => {
                  setStep('credentials');
                  setCode('');
                  resetPendingAuth();
                }}
                style={styles.secondaryAction}
              >
                <Text style={[styles.secondaryActionText, { color: theme.colors.primary }]}>
                  Изменить email
                </Text>
              </Pressable>
            </>
          )}
        </View>
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
    gap: 12,
  },
  backButton: {
    paddingVertical: 10,
    paddingRight: 8,
  },
  backSpacer: {
    width: 52,
  },
  backText: {
    fontSize: 15,
    fontWeight: '600',
  },
  heroCard: {
    overflow: 'hidden',
    borderRadius: 34,
    paddingHorizontal: 22,
    paddingVertical: 28,
    minHeight: 270,
    justifyContent: 'flex-end',
  },
  heroGlowLarge: {
    position: 'absolute',
    width: 240,
    height: 240,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.16)',
    top: -70,
    right: -40,
  },
  heroGlowSmall: {
    position: 'absolute',
    width: 140,
    height: 140,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.13)',
    top: 34,
    left: -28,
  },
  heroEyebrow: {
    color: '#fff5ff',
    fontSize: 13,
    fontWeight: '700',
    letterSpacing: 0.5,
    textTransform: 'uppercase',
    marginBottom: 12,
  },
  heroTitle: {
    color: '#ffffff',
    fontSize: 31,
    lineHeight: 35,
    fontWeight: '800',
    maxWidth: 280,
  },
  heroBody: {
    color: '#fff2ff',
    fontSize: 14,
    lineHeight: 21,
    marginTop: 14,
    maxWidth: 290,
  },
  heroDots: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
    marginTop: 22,
  },
  heroDotActive: {
    width: 24,
    height: 6,
    borderRadius: 999,
    backgroundColor: '#ffffff',
  },
  heroDotIdle: {
    width: 6,
    height: 6,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.45)',
  },
  formCard: {
    borderWidth: 1,
    borderRadius: 30,
    paddingHorizontal: 20,
    paddingVertical: 22,
    shadowOffset: {
      width: 0,
      height: 18,
    },
    shadowOpacity: 0.12,
    shadowRadius: 32,
    elevation: 5,
  },
  formTitle: {
    fontSize: 28,
    lineHeight: 30,
    fontWeight: '800',
  },
  formSubtitle: {
    fontSize: 14,
    lineHeight: 21,
    marginTop: 10,
  },
  fields: {
    gap: 14,
    marginTop: 22,
  },
  primaryButton: {
    marginTop: 20,
  },
  helperText: {
    fontSize: 13,
    lineHeight: 20,
    marginTop: 16,
  },
  codePreviewRow: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 22,
    marginBottom: 18,
  },
  codeCell: {
    flex: 1,
    minHeight: 58,
    borderRadius: 18,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  codeCellText: {
    fontSize: 24,
    lineHeight: 24,
    fontWeight: '800',
  },
  secondaryAction: {
    alignItems: 'center',
    marginTop: 18,
  },
  secondaryActionText: {
    fontSize: 14,
    fontWeight: '700',
  },
});
