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
          backgroundColor: theme.colors.surfaceMuted,
          borderColor: theme.colors.borderSoft,
        },
      ]}
    >
      {options.map((option) => {
        const active = option.value === value;

        return (
          <Pressable
            key={option.value}
            onPress={() => onChange(option.value)}
            style={[
              styles.item,
              {
                backgroundColor: active ? theme.colors.surfaceElevated : 'transparent',
              },
            ]}
          >
            <Text
              style={[
                styles.label,
                {
                  color: active ? theme.colors.textPrimary : theme.colors.textSecondary,
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
    borderRadius: 22,
    borderWidth: 1,
    padding: 6,
    flexDirection: 'row',
    gap: 6,
  },
  item: {
    flex: 1,
    minHeight: 46,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 10,
  },
  label: {
    fontSize: 14,
    fontWeight: '700',
    textAlign: 'center',
  },
});
