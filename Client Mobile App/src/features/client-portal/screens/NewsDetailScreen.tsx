import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Image } from 'expo-image';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
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
        <View style={styles.topBar}>
          <Pressable onPress={() => navigation.goBack()} style={styles.backButton}>
            <Text style={[styles.backText, { color: theme.colors.textSecondary }]}>Назад</Text>
          </Pressable>
        </View>

        {imageUrl ? (
          <Image source={{ uri: imageUrl }} style={styles.image} contentFit="cover" />
        ) : null}

        <Text style={[styles.date, { color: theme.colors.textMuted }]}>{date}</Text>
        <Text style={[styles.title, { color: theme.colors.textPrimary }]}>{title}</Text>
        <Text style={[styles.body, { color: theme.colors.textSecondary }]}>{body}</Text>
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    paddingHorizontal: 18,
    paddingTop: 8,
    gap: 14,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  backButton: {
    paddingVertical: 10,
    paddingRight: 8,
  },
  backText: {
    fontSize: 15,
    fontWeight: '600',
  },
  image: {
    width: '100%',
    height: 220,
    borderRadius: 20,
  },
  date: {
    fontSize: 13,
  },
  title: {
    fontSize: 24,
    lineHeight: 30,
    fontWeight: '800',
  },
  body: {
    fontSize: 16,
    lineHeight: 24,
  },
});
