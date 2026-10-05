import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useEffect, useRef, useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { BrandSignature } from '../../../shared/ui/BrandSignature';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { useAppTheme } from '../../../theme/theme';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Auth'>;
type AuthStep = 'credentials' | 'otp' | 'magic-link-sent' | 'select-master';

const EMAIL_PATTERN = /^\S+@\S+\.\S+$/;

export function AuthScreen({ navigation }: Props) {
  const theme = useAppTheme();
  const {
    authBusy,
    pendingAuth,
    pendingSelection,
    requestLoginCode,
    requestMagicLink,
    confirmLoginCode,
    selectMaster,
    resetPendingAuth,
  } = useClientPortal();

  const [step, setStep] = useState<AuthStep>(() => {
    if (pendingSelection) {
      return 'select-master';
    }
    if (pendingAuth?.mode === 'magic-link') {
      return 'magic-link-sent';
    }
    if (pendingAuth?.mode === 'login') {
      return 'otp';
    }
    return 'credentials';
  });
  const [email, setEmail] = useState('');
  const [code, setCode] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [emailFocused, setEmailFocused] = useState(false);
  const codeInputRef = useRef<TextInput>(null);

  // A magic-link deep link can resolve to a master-selection prompt while this
  // screen is already mounted (app was backgrounded on Auth) — keep the step in
  // sync instead of only reading pendingSelection once at mount.
  useEffect(() => {
    if (pendingSelection) {
      setStep('select-master');
    }
  }, [pendingSelection]);

  const canConfirmCode = code.trim().length >= 6;

  const fail = (cause: unknown, fallback: string) => {
    setError(cause instanceof Error && cause.message ? cause.message : fallback);
  };

  const handleRequestOtp = async () => {
    if (!EMAIL_PATTERN.test(email.trim())) {
      setError('Проверьте email: похоже, в нём опечатка.');
      return;
    }
    setError(null);
    try {
      await requestLoginCode(email.trim());
      setStep('otp');
    } catch (cause) {
      fail(cause, 'Не удалось отправить код. Попробуйте ещё раз.');
    }
  };

  const handleRequestMagicLink = async () => {
    if (!EMAIL_PATTERN.test(email.trim())) {
      setError('Проверьте email: похоже, в нём опечатка.');
      return;
    }
    setError(null);
    try {
      await requestMagicLink(email.trim());
      setStep('magic-link-sent');
    } catch (cause) {
      fail(cause, 'Не удалось отправить ссылку. Попробуйте ещё раз.');
    }
  };

  const handleConfirmOtp = async () => {
    setError(null);
    try {
      await confirmLoginCode(code.trim());
    } catch (cause) {
      fail(cause, 'Код не подошёл. Проверьте письмо и попробуйте ещё раз.');
    }
  };

  const handleSelectMaster = async (masterId: number) => {
    setError(null);
    try {
      await selectMaster(masterId);
    } catch (cause) {
      fail(cause, 'Не удалось выбрать мастера.');
    }
  };

  const handleChangeEmail = () => {
    setStep('credentials');
    setCode('');
    setError(null);
    resetPendingAuth();
  };

  const sentTo = pendingAuth?.email ?? email;

  const copy = {
    credentials: {
      title: 'Вход для клиентов',
      body: 'Введите email, который вы оставляли мастеру. Мы пришлём код — пароль не нужен.',
    },
    otp: {
      title: 'Введите код',
      body: `Мы отправили 6 цифр на ${sentTo}.`,
    },
    'magic-link-sent': {
      title: 'Проверьте почту',
      body: `Мы отправили ссылку на ${sentTo}. Откройте письмо на этом телефоне и нажмите «Войти».`,
    },
    'select-master': {
      title: 'К какому мастеру войти?',
      body: 'Ваш email привязан к нескольким мастерам.',
    },
  }[step];

  const errorText = error ? (
    <Text accessibilityRole="alert" style={[styles.error, { color: theme.colors.danger }]}>
      {error}
    </Text>
  ) : null;

  const changeEmailLink = (
    <Pressable onPress={handleChangeEmail} style={styles.textAction}>
      <Text style={[styles.textActionLabel, { color: theme.colors.textSecondary }]}>Другой email</Text>
    </Pressable>
  );

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.root}>
        <View style={styles.topBar}>
          <BrandSignature compact theme={theme} />
          {navigation.canGoBack() ? (
            <Pressable onPress={() => navigation.goBack()} hitSlop={12}>
              <Text style={[styles.backText, { color: theme.colors.textSecondary }]}>Назад</Text>
            </Pressable>
          ) : null}
        </View>

        <View style={styles.content}>
          <Text style={[styles.title, { color: theme.colors.textPrimary }]}>{copy.title}</Text>
          <Text style={[styles.body, { color: theme.colors.textSecondary }]}>{copy.body}</Text>

          {step === 'credentials' ? (
            <>
              <Text style={[styles.label, { color: theme.colors.textPrimary }]}>Email</Text>
              <TextInput
                accessibilityLabel="Email"
                autoCapitalize="none"
                autoComplete="email"
                autoCorrect={false}
                inputMode="email"
                keyboardType="email-address"
                onBlur={() => setEmailFocused(false)}
                onChangeText={(value) => {
                  setEmail(value);
                  setError(null);
                }}
                onFocus={() => setEmailFocused(true)}
                onSubmitEditing={handleRequestOtp}
                placeholder="name@mail.com"
                placeholderTextColor={theme.colors.textMuted}
                returnKeyType="go"
                style={[
                  styles.input,
                  {
                    backgroundColor: theme.colors.inputBackground,
                    borderColor: error
                      ? theme.colors.danger
                      : emailFocused
                        ? theme.colors.primary
                        : theme.colors.borderSoft,
                    color: theme.colors.textPrimary,
                  },
                ]}
                textContentType="emailAddress"
                value={email}
              />
              {errorText}

              <PrimaryButton
                disabled={authBusy}
                onPress={handleRequestOtp}
                theme={theme}
                title={authBusy ? 'Отправляем…' : 'Получить код'}
                style={styles.cta}
              />

              <Pressable disabled={authBusy} onPress={handleRequestMagicLink} style={styles.textAction}>
                <Text style={[styles.textActionLabel, { color: theme.colors.primary }]}>
                  Лучше пришлите ссылку
                </Text>
              </Pressable>

              <Text style={[styles.help, { color: theme.colors.textSecondary }]}>
                Письмо не приходит? Возможно, мастер записал другой адрес — уточните у него.
              </Text>
            </>
          ) : null}

          {step === 'otp' ? (
            <>
              <Pressable onPress={() => codeInputRef.current?.focus()} style={styles.codeRow}>
                {Array.from({ length: 6 }, (_, index) => {
                  const active = index === Math.min(code.length, 5);
                  return (
                    <View
                      key={index}
                      style={[
                        styles.codeCell,
                        {
                          backgroundColor: theme.colors.inputBackground,
                          borderColor: error
                            ? theme.colors.danger
                            : active
                              ? theme.colors.primary
                              : theme.colors.borderSoft,
                        },
                      ]}
                    >
                      <Text style={[styles.codeDigit, { color: theme.colors.textPrimary }]}>
                        {code[index] ?? ''}
                      </Text>
                    </View>
                  );
                })}
                <TextInput
                  ref={codeInputRef}
                  accessibilityLabel="Код из письма"
                  autoComplete="one-time-code"
                  autoFocus
                  caretHidden
                  inputMode="numeric"
                  keyboardType="number-pad"
                  maxLength={6}
                  onChangeText={(value) => {
                    setCode(value.replace(/\D/g, '').slice(0, 6));
                    setError(null);
                  }}
                  onSubmitEditing={handleConfirmOtp}
                  style={styles.codeHiddenInput}
                  textContentType="oneTimeCode"
                  value={code}
                />
              </Pressable>
              {errorText}

              <PrimaryButton
                disabled={!canConfirmCode || authBusy}
                onPress={handleConfirmOtp}
                theme={theme}
                title={authBusy ? 'Проверяем…' : 'Войти'}
                style={styles.cta}
              />

              <Pressable disabled={authBusy} onPress={handleRequestOtp} style={styles.textAction}>
                <Text style={[styles.textActionLabel, { color: theme.colors.primary }]}>
                  Отправить код ещё раз
                </Text>
              </Pressable>
              {changeEmailLink}
            </>
          ) : null}

          {step === 'magic-link-sent' ? (
            <>
              {errorText}
              <PrimaryButton
                disabled={authBusy}
                onPress={handleRequestMagicLink}
                theme={theme}
                title={authBusy ? 'Отправляем…' : 'Отправить ссылку ещё раз'}
                variant="secondary"
                style={styles.cta}
              />
              {changeEmailLink}
            </>
          ) : null}

          {step === 'select-master' && pendingSelection ? (
            <>
              <View style={styles.masterList}>
                {pendingSelection.masters.map((choice) => (
                  <Pressable
                    key={choice.masterId}
                    accessibilityRole="button"
                    disabled={authBusy}
                    onPress={() => handleSelectMaster(choice.masterId)}
                    style={({ pressed }) => [
                      styles.masterRow,
                      {
                        borderColor: theme.colors.borderSoft,
                        backgroundColor: pressed ? theme.colors.surfaceMuted : theme.colors.inputBackground,
                      },
                    ]}
                  >
                    <Text style={[styles.masterName, { color: theme.colors.textPrimary }]}>
                      {choice.masterName}
                    </Text>
                    <Text style={[styles.masterChevron, { color: theme.colors.textMuted }]}>›</Text>
                  </Pressable>
                ))}
              </View>
              {errorText}
              {changeEmailLink}
            </>
          ) : null}
        </View>
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    paddingHorizontal: 24,
    paddingTop: 12,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    minHeight: 44,
  },
  backText: {
    fontSize: 15,
    fontWeight: '600',
  },
  content: {
    paddingTop: 48,
  },
  title: {
    fontSize: 30,
    lineHeight: 36,
    fontWeight: '800',
    letterSpacing: -0.6,
  },
  body: {
    fontSize: 16,
    lineHeight: 24,
    marginTop: 10,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    marginTop: 32,
    marginBottom: 8,
  },
  input: {
    minHeight: 56,
    borderWidth: 1.5,
    borderRadius: 16,
    paddingHorizontal: 16,
    fontSize: 17,
  },
  error: {
    fontSize: 14,
    lineHeight: 20,
    marginTop: 10,
  },
  cta: {
    marginTop: 20,
    minHeight: 56,
    borderRadius: 16,
  },
  textAction: {
    alignItems: 'center',
    paddingVertical: 14,
    marginTop: 4,
  },
  textActionLabel: {
    fontSize: 15,
    fontWeight: '600',
  },
  help: {
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
    marginTop: 20,
  },
  codeRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 32,
  },
  codeCell: {
    flex: 1,
    height: 60,
    borderRadius: 14,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  codeDigit: {
    fontSize: 26,
    fontWeight: '700',
  },
  codeHiddenInput: {
    position: 'absolute',
    width: '100%',
    height: '100%',
    opacity: 0,
  },
  masterList: {
    gap: 10,
    marginTop: 28,
  },
  masterRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderRadius: 16,
    paddingHorizontal: 16,
    minHeight: 60,
  },
  masterName: {
    fontSize: 17,
    fontWeight: '600',
  },
  masterChevron: {
    fontSize: 24,
  },
});
