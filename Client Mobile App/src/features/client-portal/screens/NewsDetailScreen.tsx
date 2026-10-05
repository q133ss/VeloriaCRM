import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Image } from 'expo-image';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { ScreenHeader } from '../../../shared/ui/ScreenHeader';
import { useAppTheme } from '../../../theme/theme';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'NewsDetail'>;

export function NewsDetailScreen({ navigation, route }: Props) {
  const { master } = useClientPortal();
  const theme = useAppTheme(master?.branding);
  const { title, body, imageUrl, date } = route.params;

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.root}>
        <ScreenHeader theme={theme} onBack={() => navigation.goBack()} />

        {imageUrl ? (
          <Image source={{ uri: imageUrl }} style={styles.image} contentFit="cover" />
        ) : null}

        <View style={styles.article}>
          <Text style={[styles.date, { color: theme.colors.textMuted }]}>{date}</Text>
          <Text style={[styles.title, { color: theme.colors.textPrimary }]}>{title}</Text>
          <Text style={[styles.body, { color: theme.colors.textSecondary }]}>{body}</Text>
        </View>
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 24,
    gap: 20,
  },
  image: {
    width: '100%',
    height: 220,
    borderRadius: 20,
  },
  article: {
    gap: 10,
  },
  date: {
    fontSize: 13,
  },
  title: {
    fontSize: 26,
    lineHeight: 32,
    fontWeight: '700',
    letterSpacing: -0.4,
  },
  body: {
    fontSize: 16,
    lineHeight: 25,
  },
});
