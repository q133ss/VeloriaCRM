import { Platform } from 'react-native';

function resolveDefaultApiBaseUrl() {
  if (Platform.OS === 'android') {
    return 'http://10.0.2.2:8080';
  }

  return 'http://localhost:8080';
}

function trimTrailingSlash(value: string) {
  return value.replace(/\/+$/, '');
}

export const API_BASE_URL = trimTrailingSlash(
  process.env.EXPO_PUBLIC_API_BASE_URL?.trim() || resolveDefaultApiBaseUrl(),
);

export const API_V1_BASE_URL = `${API_BASE_URL}/api/v1`;
