import { Image } from 'expo-image';
import { StyleSheet, Text, View, ViewStyle } from 'react-native';

import { AppTheme } from '../../theme/theme';

type BrandSignatureProps = {
  theme: AppTheme;
  style?: ViewStyle;
  compact?: boolean;
  // Both unset pre-login (there's no unauthenticated master-lookup endpoint) and
  // for a Lite master's clients (App\Http\Controllers\Api\V1\Client\AuthController::me()
  // sends `branding: null` for them) — the default Veloria mark is what's left.
  logoUrl?: string | null;
  displayName?: string | null;
};

export function BrandSignature({ theme, style, compact = false, logoUrl, displayName }: BrandSignatureProps) {
  const markWrapStyle = [
    styles.markWrap,
    compact ? styles.markWrapCompact : null,
    { backgroundColor: theme.colors.accentSoft },
  ];

  return (
    <View style={[styles.row, style]}>
      {logoUrl ? (
        <Image
          source={{ uri: logoUrl }}
          style={markWrapStyle}
          contentFit="cover"
          transition={150}
        />
      ) : (
        <View style={markWrapStyle}>
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
      )}
      <Text
        style={[
          styles.wordmark,
          compact ? styles.wordmarkCompact : null,
          {
            color: theme.colors.textPrimary,
          },
        ]}
      >
        {displayName?.trim() || 'Veloria'}
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
