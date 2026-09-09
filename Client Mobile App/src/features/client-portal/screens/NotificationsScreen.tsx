import { useFocusEffect } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { SectionCard } from '../../../shared/ui/SectionCard';
import { useAppTheme } from '../../../theme/theme';
import { ClientNotificationDto } from '../api/contracts';
import { clientPortalApi } from '../api/clientPortalApi';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Notifications'>;

export function NotificationsScreen({ navigation }: Props) {
  const { token, master } = useClientPortal();
  const theme = useAppTheme(master?.branding);

  const [notifications, setNotifications] = useState<ClientNotificationDto[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!token) {
      return;
    }

    setError(null);

    try {
      const response = await clientPortalApi.getNotifications(token);
      setNotifications(response.data.notifications);
    } catch (fetchError) {
      const message = fetchError instanceof Error ? fetchError.message : 'Не удалось загрузить уведомления.';
      setError(message);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  const list = notifications ?? [];
  const unreadIds = list.filter((item) => !item.is_read).map((item) => item.id);

  async function markAllRead() {
    if (!token || unreadIds.length === 0) {
      return;
    }

    try {
      await clientPortalApi.markNotificationsRead(token, unreadIds);
      setNotifications((current) =>
        current ? current.map((item) => ({ ...item, is_read: true })) : current,
      );
    } catch {
      // Best-effort — the list stays as-is and the next visit will try again.
    }
  }

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.root}>
        <View style={styles.topBar}>
          <Pressable onPress={() => navigation.goBack()} style={styles.backButton}>
            <Text style={[styles.backText, { color: theme.colors.textSecondary }]}>Назад</Text>
          </Pressable>
          <Text style={[styles.title, { color: theme.colors.textPrimary }]}>Уведомления</Text>
          <View style={styles.backSpacer} />
        </View>

        {unreadIds.length > 0 ? (
          <PrimaryButton
            onPress={markAllRead}
            theme={theme}
            title="Отметить все как прочитанные"
            variant="secondary"
          />
        ) : null}

        {notifications === null && !error ? (
          <ActivityIndicator color={theme.colors.primary} style={styles.loader} />
        ) : error ? (
          <Text style={[styles.emptyText, { color: theme.colors.textSecondary }]}>{error}</Text>
        ) : list.length === 0 ? (
          <Text style={[styles.emptyText, { color: theme.colors.textSecondary }]}>
            Пока нет уведомлений.
          </Text>
        ) : (
          <View style={styles.list}>
            {list.map((item) => (
              <SectionCard key={item.id} theme={theme}>
                <View style={styles.rowBetween}>
                  <Text style={[styles.itemTitle, { color: theme.colors.textPrimary }]}>{item.title}</Text>
                  {!item.is_read ? (
                    <View style={[styles.dot, { backgroundColor: theme.colors.primary }]} />
                  ) : null}
                </View>
                <Text style={[styles.itemMessage, { color: theme.colors.textSecondary }]}>{item.message}</Text>
              </SectionCard>
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
    gap: 16,
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
  loader: {
    marginTop: 24,
  },
  list: {
    gap: 12,
  },
  rowBetween: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: 12,
  },
  itemTitle: {
    flex: 1,
    fontSize: 16,
    fontWeight: '700',
  },
  itemMessage: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 20,
  },
  dot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    marginTop: 4,
  },
  emptyText: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    paddingTop: 32,
  },
});
