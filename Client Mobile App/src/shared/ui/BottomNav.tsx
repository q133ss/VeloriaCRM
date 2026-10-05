import { Ionicons } from '@expo/vector-icons';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { AppTheme } from '../../theme/theme';

export type BottomNavTab = 'Home' | 'Appointments' | 'Chat' | 'Profile';

type BottomNavProps = {
  theme: AppTheme;
  active: BottomNavTab;
  onNavigate: (tab: BottomNavTab) => void;
  showChat?: boolean;
};

const TABS: { key: BottomNavTab; label: string; icon: keyof typeof Ionicons.glyphMap; iconActive: keyof typeof Ionicons.glyphMap }[] = [
  { key: 'Home', label: 'Главная', icon: 'home-outline', iconActive: 'home' },
  { key: 'Appointments', label: 'Записи', icon: 'calendar-outline', iconActive: 'calendar' },
  { key: 'Chat', label: 'Чат', icon: 'chatbubble-outline', iconActive: 'chatbubble' },
  { key: 'Profile', label: 'Профиль', icon: 'person-outline', iconActive: 'person' },
];

export function BottomNav({ theme, active, onNavigate, showChat = true }: BottomNavProps) {
  const tabs = showChat ? TABS : TABS.filter((tab) => tab.key !== 'Chat');

  return (
    <View
      style={[
        styles.bar,
        { backgroundColor: theme.colors.surface, borderTopColor: theme.colors.borderSoft },
      ]}
    >
      {tabs.map((tab) => {
        const isActive = tab.key === active;
        const color = isActive ? theme.colors.primary : theme.colors.textMuted;

        return (
          <Pressable
            key={tab.key}
            accessibilityRole="tab"
            accessibilityState={{ selected: isActive }}
            onPress={() => (isActive ? undefined : onNavigate(tab.key))}
            style={styles.tab}
          >
            <Ionicons name={isActive ? tab.iconActive : tab.icon} size={24} color={color} />
            <Text style={[styles.label, { color, fontWeight: isActive ? '700' : '500' }]}>{tab.label}</Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    borderTopWidth: 1,
    paddingTop: 8,
    paddingBottom: 6,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    gap: 3,
    paddingVertical: 4,
  },
  label: {
    fontSize: 11,
  },
});
