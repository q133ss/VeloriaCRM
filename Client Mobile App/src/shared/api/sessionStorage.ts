import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const CLIENT_TOKEN_KEY = 'veloria.client.token';

function getWebStorage() {
  if (typeof window === 'undefined') {
    return null;
  }

  return window.localStorage;
}

export const sessionStorage = {
  async getClientToken() {
    if (Platform.OS === 'web') {
      return getWebStorage()?.getItem(CLIENT_TOKEN_KEY) ?? null;
    }

    return SecureStore.getItemAsync(CLIENT_TOKEN_KEY);
  },

  async setClientToken(token: string) {
    if (Platform.OS === 'web') {
      getWebStorage()?.setItem(CLIENT_TOKEN_KEY, token);
      return;
    }

    await SecureStore.setItemAsync(CLIENT_TOKEN_KEY, token);
  },

  async clearClientToken() {
    if (Platform.OS === 'web') {
      getWebStorage()?.removeItem(CLIENT_TOKEN_KEY);
      return;
    }

    await SecureStore.deleteItemAsync(CLIENT_TOKEN_KEY);
  },
};
