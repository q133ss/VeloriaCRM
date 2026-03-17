import { LinearGradient } from 'expo-linear-gradient';
import { StyleSheet, Text, View } from 'react-native';

import { BrandSignature } from '../../../shared/ui/BrandSignature';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { useAppTheme } from '../../../theme/theme';

export function SplashScreen() {
  const theme = useAppTheme();

  return (
    <ScreenContainer theme={theme} scrollable={false}>
      <LinearGradient
        colors={['#ff00fc', '#ff7ffd']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.root}
      >
        <View style={styles.centerBlock}>
          <BrandSignature compact={false} theme={theme} />
          <Text style={styles.tagline}>Клиентское приложение для записи и новостей мастера</Text>
        </View>

        <View style={styles.footer}>
          <View style={styles.loaderTrack}>
            <View style={styles.loaderBar} />
          </View>
          <Text style={styles.footerText}>Загрузка Veloria...</Text>
        </View>
      </LinearGradient>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 28,
    paddingVertical: 56,
  },
  centerBlock: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 18,
  },
  tagline: {
    color: '#fff5ff',
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    maxWidth: 260,
  },
  footer: {
    width: '100%',
    alignItems: 'center',
    gap: 10,
  },
  loaderTrack: {
    width: 140,
    height: 6,
    backgroundColor: 'rgba(255,255,255,0.28)',
    borderRadius: 999,
    overflow: 'hidden',
  },
  loaderBar: {
    width: 76,
    height: '100%',
    borderRadius: 999,
    backgroundColor: '#ffffff',
  },
  footerText: {
    color: '#fff1ff',
    fontSize: 13,
    fontWeight: '600',
  },
});
