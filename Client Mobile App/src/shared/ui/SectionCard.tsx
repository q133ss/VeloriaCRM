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
          backgroundColor: theme.colors.surface,
          borderColor: theme.colors.borderSoft,
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
    borderRadius: 20,
    padding: 18,
  },
});
