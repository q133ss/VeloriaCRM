import { ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';

import { AppTheme } from '../../theme/theme';

type SectionCardProps = {
  children: ReactNode;
  theme: AppTheme;
};

export function SectionCard({ children, theme }: SectionCardProps) {
  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: theme.colors.surfaceElevated,
          borderColor: theme.colors.borderSoft,
          shadowColor: theme.colors.shadow,
        },
      ]}
    >
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    borderWidth: 1,
    borderRadius: 28,
    padding: 18,
    shadowOffset: {
      width: 0,
      height: 14,
    },
    shadowOpacity: 0.18,
    shadowRadius: 22,
    elevation: 4,
  },
});
