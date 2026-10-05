import { Ionicons } from '@expo/vector-icons';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { AppTheme } from '../../theme/theme';

export type BottomNavTab = 'Home' | 'Appointments' | 'Chat' | 'Profile';

type BottomNavProps = {
  theme: AppTheme;
  active: BottomNavTab;
  onNavigate: (tab: BottomNavTab) => void;
  showChat?: boolean;
  badges?: Partial<Record<BottomNavTab, number>>;
};

const TABS: { key: BottomNavTab; label: string; icon: keyof typeof Ionicons.glyphMap; iconActive: keyof typeof Ionicons.glyphMap }[] = [
  { key: 'Home', label: 'Главная', icon: 'home-outline', iconActive: 'home' },
  { key: 'Appointments', label: 'Записи', icon: 'calendar-outline', iconActive: 'calendar' },
  { key: 'Chat', label: 'Чат', icon: 'chatbubble-outline', iconActive: 'chatbubble' },
  { key: 'Profile', label: 'Профиль', icon: 'person-outline', iconActive: 'person' },
];

export function BottomNav({ theme, active, onNavigate, showChat = true, badges }: BottomNavProps) {
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
            <View>
              <Ionicons name={isActive ? tab.iconActive : tab.icon} size={24} color={color} />
              {badges?.[tab.key] ? (
                <View style={[styles.badge, { backgroundColor: theme.colors.primary, borderColor: theme.colors.surface }]}>
                  <Text style={styles.badgeText}>{badges[tab.key]! > 9 ? '9+' : badges[tab.key]}</Text>
                </View>
              ) : null}
            </View>
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
  badge: {
    position: 'absolute',
    top: -4,
    right: -10,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    paddingHorizontal: 4,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '700',
  },
});
