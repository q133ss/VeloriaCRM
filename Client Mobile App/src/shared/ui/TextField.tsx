import { StyleSheet, Text, TextInput, TextInputProps, View } from 'react-native';

import { AppTheme } from '../../theme/theme';

type TextFieldProps = TextInputProps & {
  label: string;
  theme: AppTheme;
};

export function TextField({ label, theme, ...props }: TextFieldProps) {
  return (
    <View style={styles.group}>
      <Text style={[styles.label, { color: theme.colors.textSecondary }]}>{label}</Text>
      <TextInput
        placeholderTextColor={theme.colors.textMuted}
        style={[
          styles.input,
          {
            backgroundColor: theme.colors.inputBackground,
            borderColor: theme.colors.borderSoft,
            color: theme.colors.textPrimary,
          },
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
    fontSize: 13,
    fontWeight: '700',
  },
  input: {
    minHeight: 54,
    borderWidth: 1,
    borderRadius: 18,
    paddingHorizontal: 16,
    fontSize: 16,
  },
});
