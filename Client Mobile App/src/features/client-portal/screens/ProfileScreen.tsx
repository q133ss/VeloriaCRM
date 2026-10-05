import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { BottomNav } from '../../../shared/ui/BottomNav';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { useAppTheme } from '../../../theme/theme';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Profile'>;

export function ProfileScreen({ navigation }: Props) {
  const { master, session, signOut, unreadChatCount } = useClientPortal();
  const theme = useAppTheme(master?.branding);

  const name = session?.name ?? 'Клиент';
  const initial = name.trim().charAt(0).toUpperCase() || 'К';
  const masterName = master?.branding?.appDisplayName?.trim() || master?.name;

  const rows = [
    { label: 'Email', value: session?.email },
    { label: 'Телефон', value: session?.phone },
    { label: 'Мастер', value: masterName },
  ].filter((row) => Boolean(row.value));

  return (
    <ScreenContainer
      theme={theme}
      footer={
        <BottomNav
          theme={theme}
          active="Profile"
          showChat={master?.hasChat ?? false}
          badges={{ Chat: unreadChatCount }}
          onNavigate={(tab) => navigation.navigate(tab)}
        />
      }
    >
      <View style={styles.root}>
        <View style={styles.identity}>
          <View style={[styles.avatar, { backgroundColor: theme.colors.accentSoft }]}>
            <Text style={[styles.avatarText, { color: theme.colors.primary }]}>{initial}</Text>
          </View>
          <Text style={[styles.name, { color: theme.colors.textPrimary }]}>{name}</Text>
        </View>

        <View style={[styles.group, { backgroundColor: theme.colors.surface, borderColor: theme.colors.borderSoft }]}>
          {rows.map((row, index) => (
            <View
              key={row.label}
              style={[
                styles.row,
                index > 0 ? { borderTopWidth: 1, borderTopColor: theme.colors.borderSoft } : null,
              ]}
            >
              <Text style={[styles.rowLabel, { color: theme.colors.textSecondary }]}>{row.label}</Text>
              <Text style={[styles.rowValue, { color: theme.colors.textPrimary }]}>{row.value}</Text>
            </View>
          ))}
        </View>

        <Text style={[styles.hint, { color: theme.colors.textSecondary }]}>
          Данные профиля хранит ваш мастер. Чтобы что-то изменить, напишите ему в чат.
        </Text>

        <Pressable accessibilityRole="button" onPress={() => void signOut()} style={styles.signOut}>
          <Text style={[styles.signOutLabel, { color: theme.colors.danger }]}>Выйти из аккаунта</Text>
        </Pressable>
      </View>
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  root: {
    paddingHorizontal: 20,
    paddingTop: 32,
  },
  identity: {
    alignItems: 'center',
    gap: 14,
    marginBottom: 32,
  },
  avatar: {
    width: 88,
    height: 88,
    borderRadius: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: 36,
    fontWeight: '700',
  },
  name: {
    fontSize: 24,
    fontWeight: '700',
    letterSpacing: -0.4,
  },
  group: {
    borderWidth: 1,
    borderRadius: 16,
    paddingHorizontal: 16,
  },
  row: {
    paddingVertical: 14,
    gap: 2,
  },
  rowLabel: {
    fontSize: 13,
  },
  rowValue: {
    fontSize: 16,
    fontWeight: '600',
  },
  hint: {
    fontSize: 13,
    lineHeight: 19,
    marginTop: 14,
    paddingHorizontal: 4,
  },
  signOut: {
    alignItems: 'center',
    paddingVertical: 16,
    marginTop: 28,
  },
  signOutLabel: {
    fontSize: 16,
    fontWeight: '600',
  },
});
