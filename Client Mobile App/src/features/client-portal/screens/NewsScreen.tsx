import { Ionicons } from '@expo/vector-icons';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Image } from 'expo-image';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { ScreenHeader } from '../../../shared/ui/ScreenHeader';
import { SectionCard } from '../../../shared/ui/SectionCard';
import { useAppTheme } from '../../../theme/theme';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'News'>;

export function NewsScreen({ navigation }: Props) {
  const { home, master } = useClientPortal();
  const theme = useAppTheme(master?.branding);

  const updates = home?.updates ?? [];

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.root}>
        <ScreenHeader theme={theme} title="Новости мастера" onBack={() => navigation.goBack()} />

        {updates.length === 0 ? (
          <View style={styles.empty}>
            <View style={[styles.emptyIcon, { backgroundColor: theme.colors.accentSoft }]}>
              <Ionicons name="newspaper-outline" size={28} color={theme.colors.primary} />
            </View>
            <Text style={[styles.emptyTitle, { color: theme.colors.textPrimary }]}>Пока нет новостей</Text>
            <Text style={[styles.emptyText, { color: theme.colors.textSecondary }]}>
              Когда мастер что-то опубликует, это появится здесь.
            </Text>
          </View>
        ) : (
          <View style={styles.list}>
            {updates.map((item) => (
              <Pressable
                key={item.id}
                accessibilityRole="button"
                onPress={() => navigation.navigate('NewsDetail', {
                  id: item.id,
                  title: item.title,
                  body: item.body,
                  imageUrl: item.imageUrl,
                  date: item.date,
                })}
              >
                <SectionCard theme={theme}>
                  {item.imageUrl ? (
                    <Image source={{ uri: item.imageUrl }} style={styles.image} contentFit="cover" />
                  ) : null}
                  <Text style={[styles.newsDate, { color: theme.colors.textMuted }]}>{item.date}</Text>
                  <Text style={[styles.newsTitle, { color: theme.colors.textPrimary }]}>{item.title}</Text>
                  <Text style={[styles.newsExcerpt, { color: theme.colors.textSecondary }]} numberOfLines={3}>
                    {item.excerpt}
                  </Text>
                </SectionCard>
              </Pressable>
            ))}
          </View>
        )}
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 16,
    gap: 20,
  },
  list: {
    gap: 12,
  },
  image: {
    width: '100%',
    height: 160,
    borderRadius: 14,
    marginBottom: 14,
  },
  newsDate: {
    fontSize: 12,
  },
  newsTitle: {
    fontSize: 17,
    lineHeight: 23,
    fontWeight: '700',
    marginTop: 4,
  },
  newsExcerpt: {
    marginTop: 6,
    fontSize: 15,
    lineHeight: 22,
  },
  empty: {
    alignItems: 'center',
    paddingTop: 56,
    gap: 10,
  },
  emptyIcon: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 6,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '700',
  },
  emptyText: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    paddingHorizontal: 24,
  },
});
