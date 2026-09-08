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

// Deliberately just the two colors a master can set (Setting.branding's
// `primary_color`/`secondary_color`) — kept separate from the client-portal
// feature's MasterBranding DTO so this shared UI primitive doesn't depend on
// a feature's API shape; the two are structurally compatible by design.
export type ThemeBrandingOverride = {
  primaryColor?: string | null;
  secondaryColor?: string | null;
};

const HEX_COLOR_PATTERN = /^#[0-9a-fA-F]{6}$/;

function isValidHexColor(value: unknown): value is string {
  return typeof value === 'string' && HEX_COLOR_PATTERN.test(value);
}

function shade(hex: string, amount: number): string {
  const clamp = (value: number) => Math.min(255, Math.max(0, value));
  const num = parseInt(hex.slice(1), 16);
  const r = clamp(((num >> 16) & 0xff) + amount);
  const g = clamp(((num >> 8) & 0xff) + amount);
  const b = clamp((num & 0xff) + amount);

  return `#${(0x1000000 + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
}

function applyBranding(base: AppTheme, branding?: ThemeBrandingOverride | null): AppTheme {
  const primary = isValidHexColor(branding?.primaryColor) ? branding.primaryColor : null;
  const secondary = isValidHexColor(branding?.secondaryColor) ? branding.secondaryColor : null;

  if (!primary && !secondary) {
    return base;
  }

  return {
    ...base,
    colors: {
      ...base.colors,
      ...(primary
        ? {
            primary,
            // Darken by the same amount in both modes — the hand-picked default
            // palette's own pressed state is darker than its base color in light
            // AND in dark mode (e.g. dark's #ff4dfd -> #db19d8), not lighter.
            primaryPressed: shade(primary, -40),
            accent: primary,
            heroBackground: primary,
          }
        : null),
      ...(secondary ? { heroSecondary: secondary } : null),
    },
  };
}

export function useAppTheme(branding?: ThemeBrandingOverride | null): AppTheme {
  const scheme = useColorScheme();
  const base = scheme === 'dark' ? darkTheme : lightTheme;

  return applyBranding(base, branding);
}
