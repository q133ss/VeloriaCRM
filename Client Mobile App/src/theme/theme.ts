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
    danger: string;
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
    appBackground: '#f6f6f8',
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
    borderSoft: '#e6e6ee',
    success: '#1ea672',
    danger: '#d92d4b',
    shadow: 'rgba(255, 0, 252, 0.12)',
    overlay: 'rgba(255, 255, 255, 0.18)',
    inputBackground: '#ffffff',
    chipBackground: '#fff1ff',
    chipText: '#7b2d87',
  },
};

export const darkTheme: AppTheme = {
  isDark: true,
  colors: {
    appBackground: '#232333',
    heroBackground: '#860084',
    heroSecondary: '#450044',
    surface: '#2b2c40',
    surfaceMuted: '#33344b',
    surfaceElevated: '#33344b',
    primary: '#ff3fc0',
    primaryPressed: '#e400a5',
    accent: '#ff3fc0',
    accentSoft: '#432548',
    textPrimary: '#e4e4ee',
    textSecondary: '#a5a5bd',
    textMuted: '#8a8aa3',
    borderSoft: '#3b3c53',
    success: '#80d8a6',
    danger: '#ff8a9b',
    shadow: 'rgba(0, 0, 0, 0.32)',
    overlay: 'rgba(255, 255, 255, 0.06)',
    inputBackground: '#232333',
    chipBackground: '#33344b',
    chipText: '#d7d7e6',
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
