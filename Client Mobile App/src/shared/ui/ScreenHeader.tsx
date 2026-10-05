import { Ionicons } from '@expo/vector-icons';
import { ReactNode } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { AppTheme } from '../../theme/theme';

type ScreenHeaderProps = {
  theme: AppTheme;
  title?: string;
  subtitle?: string;
  onBack?: () => void;
  right?: ReactNode;
};

export function ScreenHeader({ theme, title, subtitle, onBack, right }: ScreenHeaderProps) {
  return (
    <View style={styles.row}>
      {onBack ? (
        <Pressable
          accessibilityLabel="Назад"
          accessibilityRole="button"
          hitSlop={8}
          onPress={onBack}
          style={[styles.back, { backgroundColor: theme.colors.surface, borderColor: theme.colors.borderSoft }]}
        >
          <Ionicons name="chevron-back" size={22} color={theme.colors.textPrimary} />
        </Pressable>
      ) : null}

      <View style={styles.text}>
        {title ? (
          <Text numberOfLines={1} style={[styles.title, { color: theme.colors.textPrimary }]}>
            {title}
          </Text>
        ) : null}
        {subtitle ? (
          <Text numberOfLines={1} style={[styles.subtitle, { color: theme.colors.textSecondary }]}>
            {subtitle}
          </Text>
        ) : null}
      </View>

      {right}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    minHeight: 44,
  },
  back: {
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  text: {
    flex: 1,
  },
  title: {
    fontSize: 20,
    fontWeight: '700',
    letterSpacing: -0.3,
  },
  subtitle: {
    fontSize: 13,
    marginTop: 1,
  },
});
