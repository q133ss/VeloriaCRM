import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useCallback } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { ScreenHeader } from '../../../shared/ui/ScreenHeader';
import { SectionCard } from '../../../shared/ui/SectionCard';
import { useAppTheme } from '../../../theme/theme';
import { ClientNotificationDto } from '../api/contracts';
import { CHAT_ACTION_URL, useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Notifications'>;

export function NotificationsScreen({ navigation }: Props) {
  const { master, notifications, refreshNotifications, markNotificationsRead } = useClientPortal();
  const theme = useAppTheme(master?.branding);

  useFocusEffect(
    useCallback(() => {
      void refreshNotifications();
    }, [refreshNotifications]),
  );

  const list = notifications ?? [];
  const unreadIds = list.filter((item) => !item.is_read).map((item) => item.id);

  function openNotification(item: ClientNotificationDto) {
    void markNotificationsRead([item.id]);

    if (item.action_url === CHAT_ACTION_URL && master?.hasChat) {
      navigation.navigate('Chat');
    }
  }

  const colors = theme.colors;

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.root}>
        <ScreenHeader
          theme={theme}
          title="Уведомления"
          onBack={() => navigation.goBack()}
          right={
            unreadIds.length > 0 ? (
              <Pressable hitSlop={8} onPress={() => void markNotificationsRead(unreadIds)}>
                <Text style={[styles.markAll, { color: colors.primary }]}>Прочитать все</Text>
              </Pressable>
            ) : null
          }
        />

        {notifications === null ? (
          <ActivityIndicator color={colors.primary} style={styles.loader} />
        ) : list.length === 0 ? (
          <View style={styles.empty}>
            <View style={[styles.emptyIcon, { backgroundColor: colors.accentSoft }]}>
              <Ionicons name="notifications-outline" size={28} color={colors.primary} />
            </View>
            <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>Всё спокойно</Text>
            <Text style={[styles.emptyText, { color: colors.textSecondary }]}>
              Сообщения мастера и напоминания о записи будут появляться здесь.
            </Text>
          </View>
        ) : (
          <View style={[styles.group, { backgroundColor: colors.surface, borderColor: colors.borderSoft }]}>
            {list.map((item, index) => (
              <Pressable
                key={item.id}
                accessibilityRole="button"
                onPress={() => openNotification(item)}
                style={({ pressed }) => [
                  styles.row,
                  index > 0 ? { borderTopWidth: 1, borderTopColor: colors.borderSoft } : null,
                  pressed ? { backgroundColor: colors.surfaceMuted } : null,
                ]}
              >
                <View style={styles.dotSlot}>
                  {!item.is_read ? <View style={[styles.dot, { backgroundColor: colors.primary }]} /> : null}
                </View>
                <View style={styles.rowText}>
                  <Text
                    style={[
                      styles.itemTitle,
                      { color: colors.textPrimary, fontWeight: item.is_read ? '500' : '700' },
                    ]}
                  >
                    {item.title}
                  </Text>
                  <Text style={[styles.itemMessage, { color: colors.textSecondary }]} numberOfLines={3}>
                    {item.message}
                  </Text>
                </View>
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
  markAll: {
    fontSize: 14,
    fontWeight: '600',
  },
  loader: {
    marginTop: 24,
  },
  group: {
    borderWidth: 1,
    borderRadius: 20,
    overflow: 'hidden',
  },
  row: {
    flexDirection: 'row',
    gap: 10,
    paddingRight: 18,
    paddingVertical: 16,
  },
  dotSlot: {
    width: 28,
    alignItems: 'center',
    paddingTop: 6,
  },
  dot: {
    width: 10,
    height: 10,
    borderRadius: 5,
  },
  rowText: {
    flex: 1,
    gap: 3,
  },
  itemTitle: {
    fontSize: 16,
  },
  itemMessage: {
    fontSize: 15,
    lineHeight: 21,
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
