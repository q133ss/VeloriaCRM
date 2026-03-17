import { useColorScheme } from 'react-native';

export type AppTheme = {
  isDark: boolean;
  colors: {
    appBackground: string;
    heroBackground: string;
    heroSecondary: string;
    surface: string;
    surfaceMuted: string;
    surfaceElevated: string;
    primary: string;
    primaryPressed: string;
    accent: string;
    accentSoft: string;
    textPrimary: string;
    textSecondary: string;
    textMuted: string;
    borderSoft: string;
    success: string;
    shadow: string;
    overlay: string;
    inputBackground: string;
    chipBackground: string;
    chipText: string;
  };
};

export const lightTheme: AppTheme = {
  isDark: false,
  colors: {
    appBackground: '#ffffff',
    heroBackground: '#ff00fc',
    heroSecondary: '#ff62fb',
    surface: '#ffffff',
    surfaceMuted: '#fff0fe',
    surfaceElevated: '#ffffff',
    primary: '#ff00fc',
    primaryPressed: '#d700d4',
    accent: '#ff00fc',
    accentSoft: '#ffe5ff',
    textPrimary: '#16111d',
    textSecondary: '#655d73',
    textMuted: '#9a90a8',
    borderSoft: '#f0d7f7',
    success: '#1ea672',
    shadow: 'rgba(255, 0, 252, 0.12)',
    overlay: 'rgba(255, 255, 255, 0.18)',
    inputBackground: '#fcf7fd',
    chipBackground: '#fff1ff',
    chipText: '#7b2d87',
  },
};

export const darkTheme: AppTheme = {
  isDark: true,
  colors: {
    appBackground: '#101614',
    heroBackground: '#860084',
    heroSecondary: '#450044',
    surface: '#151d1a',
    surfaceMuted: '#1d2723',
    surfaceElevated: '#1d2622',
    primary: '#ff4dfd',
    primaryPressed: '#db19d8',
    accent: '#ff4dfd',
    accentSoft: '#3b173b',
    textPrimary: '#f4efe7',
    textSecondary: '#c1c9bf',
    textMuted: '#95a096',
    borderSoft: '#2d3833',
    success: '#80d8a6',
    shadow: 'rgba(0, 0, 0, 0.32)',
    overlay: 'rgba(255, 255, 255, 0.06)',
    inputBackground: '#111915',
    chipBackground: '#22302b',
    chipText: '#d7d0bf',
  },
};

export function useAppTheme() {
  const scheme = useColorScheme();

  return scheme === 'dark' ? darkTheme : lightTheme;
}
