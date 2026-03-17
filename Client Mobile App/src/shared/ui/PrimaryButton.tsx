import { Pressable, StyleSheet, Text, ViewStyle } from 'react-native';

import { AppTheme } from '../../theme/theme';

type ButtonVariant = 'primary' | 'secondary' | 'ghost';

type PrimaryButtonProps = {
  title: string;
  onPress: () => void;
  theme: AppTheme;
  variant?: ButtonVariant;
  disabled?: boolean;
  style?: ViewStyle;
};

export function PrimaryButton({
  title,
  onPress,
  theme,
  variant = 'primary',
  disabled = false,
  style,
}: PrimaryButtonProps) {
  const isPrimary = variant === 'primary';
  const isSecondary = variant === 'secondary';

  return (
    <Pressable
      accessibilityRole="button"
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        {
          backgroundColor: isPrimary
            ? pressed
              ? theme.colors.primaryPressed
              : theme.colors.primary
            : isSecondary
              ? pressed
                ? theme.colors.surfaceMuted
                : theme.colors.surface
              : 'transparent',
          borderColor: isPrimary ? 'transparent' : theme.colors.borderSoft,
          opacity: disabled ? 0.55 : 1,
        },
        style,
      ]}
    >
      <Text
        style={[
          styles.label,
          {
            color: isPrimary ? '#fffaf2' : theme.colors.textPrimary,
          },
        ]}
      >
        {title}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  button: {
    minHeight: 54,
    borderRadius: 18,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
  },
  label: {
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 0.2,
  },
});
