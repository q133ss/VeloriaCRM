import { StyleSheet, Text, TextInput, TextInputProps, View } from 'react-native';

import { AppTheme } from '../../theme/theme';

type TextFieldProps = TextInputProps & {
  label: string;
  theme: AppTheme;
};

export function TextField({ label, theme, style, ...props }: TextFieldProps) {
  return (
    <View style={styles.group}>
      <Text style={[styles.label, { color: theme.colors.textPrimary }]}>{label}</Text>
      <TextInput
        placeholderTextColor={theme.colors.textMuted}
        style={[
          styles.input,
          {
            backgroundColor: theme.colors.inputBackground,
            borderColor: theme.colors.borderSoft,
            color: theme.colors.textPrimary,
          },
          style,
        ]}
        {...props}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  group: {
    gap: 8,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
  },
  input: {
    minHeight: 52,
    borderWidth: 1.5,
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 16,
  },
});
