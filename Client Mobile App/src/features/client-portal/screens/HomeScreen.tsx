import { useFocusEffect } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { LinearGradient } from 'expo-linear-gradient';
import { useCallback } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { BrandSignature } from '../../../shared/ui/BrandSignature';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { SectionCard } from '../../../shared/ui/SectionCard';
import { useAppTheme } from '../../../theme/theme';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Home'>;

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

  return (
    <ScreenContainer theme={theme}>
      <View style={styles.brandRow}>
        <BrandSignature
          compact
          theme={theme}
          logoUrl={master.branding?.logoUrl}
          displayName={master.branding?.appDisplayName}
        />
        <View style={styles.headerLinks}>
          {master.hasChat ? (
            <Pressable onPress={() => navigation.navigate('Chat')} hitSlop={8}>
              <Text style={[styles.linkLabel, { color: theme.colors.primary }]}>Чат</Text>
            </Pressable>
          ) : null}
          <Pressable onPress={() => navigation.navigate('Notifications')} hitSlop={8}>
            <Text style={[styles.linkLabel, { color: theme.colors.primary }]}>Уведомления</Text>
          </Pressable>
        </View>
      </View>

      <LinearGradient
        colors={[theme.colors.heroBackground, theme.colors.heroSecondary]}
        start={{ x: 0.1, y: 0 }}
        end={{ x: 0.9, y: 1 }}
        style={styles.header}
      >
        <Text style={styles.greeting}>Здравствуйте, {session?.name ?? home.clientName}</Text>
        <Text style={styles.headerTitle}>Запись к {master.name}</Text>
        <Text style={styles.headerSubtitle}>
          Все ключевые действия собраны здесь: ближайший визит, услуги и новости мастера.
        </Text>
      </LinearGradient>

      <View style={styles.section}>
        <SectionCard theme={theme}>
          {home.nextAppointment ? (
            <>
              <View style={styles.rowBetween}>
                <View style={styles.flexOne}>
                  <Text style={[styles.cardLabel, { color: theme.colors.textMuted }]}>Ближайшая запись</Text>
                  <Text style={[styles.cardTitle, { color: theme.colors.textPrimary }]}>
                    {home.nextAppointment.serviceLabel}
                  </Text>
                </View>
                <View
                  style={[
                    styles.statusPill,
                    {
                      backgroundColor: theme.colors.accentSoft,
                    },
                  ]}
                >
                  <Text style={[styles.statusText, { color: theme.colors.textPrimary }]}>
                    {home.nextAppointment.statusLabel}
                  </Text>
                </View>
              </View>

              <Text style={[styles.cardMeta, { color: theme.colors.textSecondary }]}>
                {home.nextAppointment.dateLabel} · {home.nextAppointment.timeLabel}
              </Text>

              <View style={styles.actionsRow}>
                <PrimaryButton
                  onPress={() => navigation.navigate('Booking', {})}
                  theme={theme}
                  title="Новая запись"
                  style={styles.flexButton}
                />
                <PrimaryButton
                  onPress={() => navigation.navigate('Appointments')}
                  theme={theme}
                  title="Все записи"
                  variant="secondary"
                  style={styles.flexButton}
                />
              </View>
            </>
          ) : (
            <>
              <Text style={[styles.cardLabel, { color: theme.colors.textMuted }]}>Ближайшая запись</Text>
              <Text style={[styles.cardTitle, { color: theme.colors.textPrimary }]}>Пока нет записей</Text>
              <Text style={[styles.cardMeta, { color: theme.colors.textSecondary }]}>
                Выберите услугу и удобное время — это займет меньше минуты.
              </Text>

              <PrimaryButton
                onPress={() => navigation.navigate('Booking', {})}
                theme={theme}
                title="Записаться"
                style={styles.primaryButtonFull}
              />
            </>
          )}
        </SectionCard>
      </View>

      <View style={styles.section}>
        <View style={styles.rowBetween}>
          <Text style={[styles.blockTitle, { color: theme.colors.textPrimary }]}>Популярные услуги</Text>
          <Pressable onPress={() => navigation.navigate('Booking', {})}>
            <Text style={[styles.linkLabel, { color: theme.colors.primary }]}>Все услуги</Text>
          </Pressable>
        </View>

        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalList}>
          {home.services.map((service) => (
            <View
              key={service.id}
              style={[
                styles.serviceCard,
                {
                  backgroundColor: theme.colors.surfaceElevated,
                  borderColor: theme.colors.borderSoft,
                  shadowColor: theme.colors.shadow,
                },
              ]}
            >
              {service.badge ? (
                <View
                  style={[
                    styles.smallChip,
                    {
                      backgroundColor: theme.colors.chipBackground,
                    },
                  ]}
                >
                  <Text style={[styles.smallChipText, { color: theme.colors.chipText }]}>{service.badge}</Text>
                </View>
              ) : null}

              <Text style={[styles.serviceTitle, { color: theme.colors.textPrimary }]}>{service.title}</Text>
              <Text style={[styles.serviceMeta, { color: theme.colors.textSecondary }]}>{service.duration}</Text>
              <Text style={[styles.servicePrice, { color: theme.colors.textPrimary }]}>{service.price}</Text>
              <PrimaryButton
                onPress={() => navigation.navigate('Booking', { serviceId: resolveNumericServiceId(service.id) })}
                theme={theme}
                title="Записаться"
                style={styles.serviceButton}
              />
            </View>
          ))}
        </ScrollView>
      </View>

      <View style={styles.section}>
        <View style={styles.rowBetween}>
          <Text style={[styles.blockTitle, { color: theme.colors.textPrimary }]}>Новости мастера</Text>
          <Pressable onPress={() => navigation.navigate('News')}>
            <Text style={[styles.linkLabel, { color: theme.colors.primary }]}>Смотреть все</Text>
          </Pressable>
        </View>

        {home.updates.slice(0, 3).map((item) => (
          <Pressable
            key={item.id}
            onPress={() => navigation.navigate('NewsDetail', {
              id: item.id,
              title: item.title,
              body: item.body,
              imageUrl: item.imageUrl,
              date: item.date,
            })}
          >
            <SectionCard theme={theme}>
              <View style={styles.rowBetween}>
                <Text style={[styles.newsTitle, { color: theme.colors.textPrimary }]}>{item.title}</Text>
                <Text style={[styles.newsDate, { color: theme.colors.textMuted }]}>{item.date}</Text>
              </View>
              <Text style={[styles.newsExcerpt, { color: theme.colors.textSecondary }]}>{item.excerpt}</Text>
            </SectionCard>
          </Pressable>
        ))}
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  brandRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 12,
    marginBottom: 4,
  },
  headerLinks: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
  },
  header: {
    margin: 16,
    marginBottom: 20,
    borderRadius: 32,
    padding: 22,
  },
  greeting: {
    color: '#d5e3d8',
    fontSize: 14,
    marginBottom: 12,
  },
  headerTitle: {
    color: '#fffaf2',
    fontSize: 30,
    lineHeight: 34,
    fontWeight: '800',
  },
  headerSubtitle: {
    color: '#d0ddd4',
    fontSize: 15,
    lineHeight: 22,
    marginTop: 12,
  },
  section: {
    paddingHorizontal: 16,
    gap: 12,
    marginBottom: 18,
  },
  rowBetween: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: 12,
  },
  flexOne: {
    flex: 1,
  },
  cardLabel: {
    fontSize: 13,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  cardTitle: {
    fontSize: 22,
    lineHeight: 26,
    fontWeight: '800',
    marginTop: 8,
  },
  cardMeta: {
    fontSize: 15,
    marginTop: 10,
    marginBottom: 18,
  },
  statusPill: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '700',
  },
  actionsRow: {
    flexDirection: 'row',
    gap: 10,
  },
  primaryButtonFull: {
    marginTop: 18,
  },
  flexButton: {
    flex: 1,
  },
  blockTitle: {
    fontSize: 24,
    fontWeight: '800',
  },
  linkLabel: {
    fontSize: 14,
    fontWeight: '700',
    marginTop: 6,
  },
  horizontalList: {
    gap: 12,
    paddingRight: 16,
  },
  serviceCard: {
    width: 244,
    borderWidth: 1,
    borderRadius: 28,
    padding: 18,
    shadowOffset: {
      width: 0,
      height: 12,
    },
    shadowOpacity: 0.14,
    shadowRadius: 18,
    elevation: 3,
  },
  smallChip: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    marginBottom: 18,
  },
  smallChipText: {
    fontSize: 12,
    fontWeight: '700',
  },
  serviceTitle: {
    fontSize: 20,
    lineHeight: 24,
    fontWeight: '800',
  },
  serviceMeta: {
    marginTop: 10,
    fontSize: 14,
  },
  servicePrice: {
    marginTop: 4,
    fontSize: 16,
    fontWeight: '700',
  },
  serviceButton: {
    marginTop: 18,
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
});
