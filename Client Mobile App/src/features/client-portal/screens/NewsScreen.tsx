import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Image } from 'expo-image';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
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
        <View style={styles.topBar}>
          <Pressable onPress={() => navigation.goBack()} style={styles.backButton}>
            <Text style={[styles.backText, { color: theme.colors.textSecondary }]}>Назад</Text>
          </Pressable>
          <Text style={[styles.title, { color: theme.colors.textPrimary }]}>Новости мастера</Text>
          <View style={styles.backSpacer} />
        </View>

        {updates.length === 0 ? (
          <Text style={[styles.emptyText, { color: theme.colors.textSecondary }]}>
            Пока нет новостей. Загляните позже.
          </Text>
        ) : (
          <View style={styles.list}>
            {updates.map((item) => (
              <Pressable key={item.id} onPress={() => navigation.navigate('NewsDetail', {
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
                  <View style={styles.rowBetween}>
                    <Text style={[styles.newsTitle, { color: theme.colors.textPrimary }]}>{item.title}</Text>
                    <Text style={[styles.newsDate, { color: theme.colors.textMuted }]}>{item.date}</Text>
                  </View>
                  <Text style={[styles.newsExcerpt, { color: theme.colors.textSecondary }]}>{item.excerpt}</Text>
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
    paddingHorizontal: 18,
    paddingTop: 8,
    gap: 18,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  backButton: {
    paddingVertical: 10,
    paddingRight: 8,
    minWidth: 52,
  },
  backSpacer: {
    minWidth: 52,
  },
  backText: {
    fontSize: 15,
    fontWeight: '600',
  },
  title: {
    fontSize: 18,
    fontWeight: '800',
  },
  list: {
    gap: 12,
  },
  image: {
    width: '100%',
    height: 160,
    borderRadius: 18,
    marginBottom: 14,
  },
  rowBetween: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: 12,
  },
  newsTitle: {
    flex: 1,
    fontSize: 18,
    lineHeight: 24,
    fontWeight: '700',
  },
  newsDate: {
    fontSize: 13,
    marginTop: 2,
  },
  newsExcerpt: {
    marginTop: 12,
    fontSize: 15,
    lineHeight: 22,
  },
  emptyText: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    paddingTop: 32,
  },
});
