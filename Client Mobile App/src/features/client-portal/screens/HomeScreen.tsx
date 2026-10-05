import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useCallback } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { BottomNav } from '../../../shared/ui/BottomNav';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { useAppTheme } from '../../../theme/theme';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Home'>;

const VISIBLE_SERVICES = 4;

// `home.services` falls back to mock cards with non-numeric ids (`'svc-1'`)
// when the real fetch failed or came back empty (ClientPortalProvider.loadHomeFeed)
// — those can't be pre-selected on Booking, so let the picker start from scratch.
function resolveNumericServiceId(id: string): number | undefined {
  const numericId = Number(id);

  return Number.isFinite(numericId) ? numericId : undefined;
}

export function HomeScreen({ navigation }: Props) {
  const { home, master, session, refreshHomeFeed } = useClientPortal();
  const theme = useAppTheme(master?.branding);
  const colors = theme.colors;

  // Catches a booking made on BookingConfirmationScreen: coming back to Home
  // should show the new appointment, not the state from before it existed.
  useFocusEffect(
    useCallback(() => {
      void refreshHomeFeed();
    }, [refreshHomeFeed]),
  );

  if (!home || !master) {
    return null;
  }

  const name = session?.name ?? home.clientName;
  const masterName = master.branding?.appDisplayName?.trim() || master.name;
  const initial = name.trim().charAt(0).toUpperCase() || 'К';
  const latestNews = home.updates[0];
  const appointment = home.nextAppointment;

  return (
    <ScreenContainer
      theme={theme}
      footer={
        <BottomNav
          theme={theme}
          active="Home"
          showChat={master.hasChat}
          onNavigate={(tab) => navigation.navigate(tab)}
        />
      }
    >
      <View style={styles.header}>
        <View style={[styles.avatar, { backgroundColor: colors.accentSoft }]}>
          <Text style={[styles.avatarText, { color: colors.primary }]}>{initial}</Text>
        </View>
        <View style={styles.headerText}>
          <Text style={[styles.hello, { color: colors.textPrimary }]} numberOfLines={1}>
            Привет, {name}
          </Text>
          <Text style={[styles.helloMeta, { color: colors.textSecondary }]} numberOfLines={1}>
            Ваш мастер — {masterName}
          </Text>
        </View>
        <Pressable
          accessibilityLabel="Уведомления"
          accessibilityRole="button"
          hitSlop={8}
          onPress={() => navigation.navigate('Notifications')}
          style={[styles.iconButton, { borderColor: colors.borderSoft, backgroundColor: colors.surface }]}
        >
          <Ionicons name="notifications-outline" size={22} color={colors.textPrimary} />
        </Pressable>
      </View>

      <View style={styles.body}>
        {appointment ? (
          <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.borderSoft }]}>
            <View style={styles.cardTop}>
              <Text style={[styles.eyebrow, { color: colors.textSecondary }]}>Ближайшая запись</Text>
              <View style={[styles.pill, { backgroundColor: colors.accentSoft }]}>
                <Text style={[styles.pillText, { color: colors.textPrimary }]}>{appointment.statusLabel}</Text>
              </View>
            </View>
            <Text style={[styles.when, { color: colors.textPrimary }]}>
              {appointment.dateLabel} · {appointment.timeLabel}
            </Text>
            <Text style={[styles.what, { color: colors.textSecondary }]}>{appointment.serviceLabel}</Text>
            <View style={styles.cardActions}>
              <PrimaryButton
                onPress={() => navigation.navigate('Appointments')}
                theme={theme}
                title="Все записи"
                variant="secondary"
                style={styles.flexButton}
              />
              {master.hasChat ? (
                <PrimaryButton
                  onPress={() => navigation.navigate('Chat')}
                  theme={theme}
                  title="Написать мастеру"
                  variant="secondary"
                  style={styles.flexButton}
                />
              ) : null}
            </View>
          </View>
        ) : (
          <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.borderSoft }]}>
            <Text style={[styles.eyebrow, { color: colors.textSecondary }]}>Ближайшая запись</Text>
            <Text style={[styles.when, { color: colors.textPrimary }]}>Пока нет записей</Text>
            <Text style={[styles.what, { color: colors.textSecondary }]}>
              Выберите услугу и удобное время — это займёт меньше минуты.
            </Text>
            <PrimaryButton
              onPress={() => navigation.navigate('Booking', {})}
              theme={theme}
              title="Записаться"
              style={styles.cta}
            />
          </View>
        )}

        <View style={styles.sectionHead}>
          <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Услуги</Text>
          <Pressable hitSlop={8} onPress={() => navigation.navigate('Booking', {})}>
            <Text style={[styles.sectionLink, { color: colors.primary }]}>Все</Text>
          </Pressable>
        </View>

        <View style={[styles.list, { backgroundColor: colors.surface, borderColor: colors.borderSoft }]}>
          {home.services.slice(0, VISIBLE_SERVICES).map((service, index) => (
            <Pressable
              key={service.id}
              accessibilityRole="button"
              onPress={() => navigation.navigate('Booking', { serviceId: resolveNumericServiceId(service.id) })}
              style={({ pressed }) => [
                styles.serviceRow,
                index > 0 ? { borderTopWidth: 1, borderTopColor: colors.borderSoft } : null,
                pressed ? { backgroundColor: colors.surfaceMuted } : null,
              ]}
            >
              <View style={styles.serviceText}>
                <Text style={[styles.serviceTitle, { color: colors.textPrimary }]} numberOfLines={2}>
                  {service.title}
                </Text>
                <Text style={[styles.serviceMeta, { color: colors.textSecondary }]}>
                  {service.duration} · {service.price}
                </Text>
              </View>
              <Ionicons name="chevron-forward" size={20} color={colors.textMuted} />
            </Pressable>
          ))}
        </View>

        {latestNews ? (
          <>
            <View style={styles.sectionHead}>
              <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Новости мастера</Text>
              <Pressable hitSlop={8} onPress={() => navigation.navigate('News')}>
                <Text style={[styles.sectionLink, { color: colors.primary }]}>Все</Text>
              </Pressable>
            </View>
            <Pressable
              accessibilityRole="button"
              onPress={() =>
                navigation.navigate('NewsDetail', {
                  id: latestNews.id,
                  title: latestNews.title,
                  body: latestNews.body,
                  imageUrl: latestNews.imageUrl,
                  date: latestNews.date,
                })
              }
              style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.borderSoft }]}
            >
              <Text style={[styles.newsDate, { color: colors.textMuted }]}>{latestNews.date}</Text>
              <Text style={[styles.newsTitle, { color: colors.textPrimary }]}>{latestNews.title}</Text>
              <Text style={[styles.what, { color: colors.textSecondary }]} numberOfLines={2}>
                {latestNews.excerpt}
              </Text>
            </Pressable>
          </>
        ) : null}
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 8,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: 18,
    fontWeight: '700',
  },
  headerText: {
    flex: 1,
  },
  hello: {
    fontSize: 18,
    fontWeight: '700',
    letterSpacing: -0.2,
  },
  helloMeta: {
    fontSize: 13,
    marginTop: 1,
  },
  iconButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  body: {
    paddingHorizontal: 20,
    paddingTop: 12,
  },
  card: {
    borderWidth: 1,
    borderRadius: 20,
    padding: 18,
  },
  cardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  eyebrow: {
    fontSize: 13,
    fontWeight: '600',
  },
  pill: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  pillText: {
    fontSize: 12,
    fontWeight: '600',
  },
  when: {
    fontSize: 22,
    lineHeight: 28,
    fontWeight: '700',
    letterSpacing: -0.3,
    marginTop: 8,
  },
  what: {
    fontSize: 15,
    lineHeight: 22,
    marginTop: 6,
  },
  cta: {
    marginTop: 18,
    minHeight: 52,
    borderRadius: 14,
  },
  cardActions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 18,
  },
  flexButton: {
    flex: 1,
    minHeight: 48,
    borderRadius: 14,
  },
  sectionHead: {
    flexDirection: 'row',
    alignItems: 'baseline',
    justifyContent: 'space-between',
    marginTop: 28,
    marginBottom: 10,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    letterSpacing: -0.2,
  },
  sectionLink: {
    fontSize: 14,
    fontWeight: '600',
  },
  list: {
    borderWidth: 1,
    borderRadius: 20,
    overflow: 'hidden',
  },
  serviceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 18,
    paddingVertical: 14,
    minHeight: 64,
  },
  serviceText: {
    flex: 1,
  },
  serviceTitle: {
    fontSize: 16,
    fontWeight: '600',
  },
  serviceMeta: {
    fontSize: 14,
    marginTop: 2,
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
});
