import { Pressable, StyleSheet, Text, View } from 'react-native';

import { AppTheme } from '../../theme/theme';

type SegmentOption<T extends string> = {
  label: string;
  value: T;
};

type SegmentedControlProps<T extends string> = {
  options: SegmentOption<T>[];
  value: T;
  onChange: (value: T) => void;
  theme: AppTheme;
};

export function SegmentedControl<T extends string>({
  options,
  value,
  onChange,
  theme,
}: SegmentedControlProps<T>) {
  return (
    <View
      style={[
        styles.root,
        {
          backgroundColor: theme.isDark ? theme.colors.inputBackground : '#ececf1',
        },
      ]}
    >
      {options.map((option) => {
        const active = option.value === value;

        return (
          <Pressable
            key={option.value}
            accessibilityRole="tab"
            accessibilityState={{ selected: active }}
            onPress={() => onChange(option.value)}
            style={[
              styles.item,
              {
                backgroundColor: active ? theme.colors.surface : 'transparent',
                borderColor: active ? theme.colors.borderSoft : 'transparent',
              },
            ]}
          >
            <Text
              style={[
                styles.label,
                {
                  color: active ? theme.colors.textPrimary : theme.colors.textSecondary,
                  fontWeight: active ? '700' : '500',
                },
              ]}
            >
              {option.label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    borderRadius: 14,
    padding: 4,
    flexDirection: 'row',
  },
  item: {
    flex: 1,
    minHeight: 40,
    borderRadius: 11,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 10,
  },
  label: {
    fontSize: 14,
    textAlign: 'center',
  },
});
