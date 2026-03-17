import { StyleSheet, Text, View, ViewStyle } from 'react-native';

import { AppTheme } from '../../theme/theme';

type BrandSignatureProps = {
  theme: AppTheme;
  style?: ViewStyle;
  compact?: boolean;
};

export function BrandSignature({ theme, style, compact = false }: BrandSignatureProps) {
  return (
    <View style={[styles.row, style]}>
      <View
        style={[
          styles.markWrap,
          compact ? styles.markWrapCompact : null,
          {
            backgroundColor: theme.colors.accentSoft,
          },
        ]}
      >
        <Text
          style={[
            styles.mark,
            compact ? styles.markCompact : null,
            {
              color: theme.colors.primary,
            },
          ]}
        >
          V
        </Text>
      </View>
      <Text
        style={[
          styles.wordmark,
          compact ? styles.wordmarkCompact : null,
          {
            color: theme.colors.textPrimary,
          },
        ]}
      >
        Veloria
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  markWrap: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  markWrapCompact: {
    width: 34,
    height: 34,
    borderRadius: 12,
  },
  mark: {
    fontSize: 24,
    lineHeight: 24,
    fontWeight: '900',
    marginTop: -2,
  },
  markCompact: {
    fontSize: 20,
    lineHeight: 20,
  },
  wordmark: {
    fontSize: 28,
    lineHeight: 28,
    fontWeight: '800',
    letterSpacing: -0.6,
  },
  wordmarkCompact: {
    fontSize: 22,
    lineHeight: 22,
  },
});
