import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { LinearGradient } from 'expo-linear-gradient';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { PrimaryButton } from '../../../shared/ui/PrimaryButton';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { SectionCard } from '../../../shared/ui/SectionCard';
import { useAppTheme } from '../../../theme/theme';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Home'>;

export function HomeScreen({}: Props) {
  const theme = useAppTheme();
  const { home, master, session } = useClientPortal();

  if (!home || !master) {
    return null;
  }

  return (
    <ScreenContainer theme={theme}>
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
          <View style={styles.rowBetween}>
            <View style={styles.flexOne}>
              <Text style={[styles.cardLabel, { color: theme.colors.textMuted }]}>Ближайшая запись</Text>
              <Text style={[styles.cardTitle, { color: theme.colors.textPrimary }]}>
                {home.nextAppointment.service}
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
                {home.nextAppointment.status}
              </Text>
            </View>
          </View>

          <Text style={[styles.cardMeta, { color: theme.colors.textSecondary }]}>
            {home.nextAppointment.dateLabel} · {home.nextAppointment.timeLabel}
          </Text>

          <View style={styles.actionsRow}>
            <PrimaryButton
              onPress={() => {}}
              theme={theme}
              title="Выбрать другое время"
              style={styles.flexButton}
            />
            <PrimaryButton
              onPress={() => {}}
              theme={theme}
              title="Перенести позже"
              variant="secondary"
              style={styles.flexButton}
            />
          </View>
        </SectionCard>
      </View>

      <View style={styles.section}>
        <View style={styles.rowBetween}>
          <Text style={[styles.blockTitle, { color: theme.colors.textPrimary }]}>Популярные услуги</Text>
          <Text style={[styles.linkLabel, { color: theme.colors.primary }]}>Все услуги</Text>
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
              <PrimaryButton onPress={() => {}} theme={theme} title="Записаться" style={styles.serviceButton} />
            </View>
          ))}
        </ScrollView>
      </View>

      <View style={styles.section}>
        <View style={styles.rowBetween}>
          <Text style={[styles.blockTitle, { color: theme.colors.textPrimary }]}>Новости мастера</Text>
          <Text style={[styles.linkLabel, { color: theme.colors.primary }]}>Смотреть все</Text>
        </View>

        {home.updates.map((item) => (
          <SectionCard key={item.id} theme={theme}>
            <View style={styles.rowBetween}>
              <Text style={[styles.newsTitle, { color: theme.colors.textPrimary }]}>{item.title}</Text>
              <Text style={[styles.newsDate, { color: theme.colors.textMuted }]}>{item.date}</Text>
            </View>
            <Text style={[styles.newsExcerpt, { color: theme.colors.textSecondary }]}>{item.excerpt}</Text>
          </SectionCard>
        ))}
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
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
