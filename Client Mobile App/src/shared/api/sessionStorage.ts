import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const CLIENT_TOKEN_KEY = 'veloria.client.token';
const CLIENT_MASTER_KEY = 'veloria.client.master';

type StoredMaster = {
  id: number;
  name: string;
};

function getWebStorage() {
  if (typeof window === 'undefined') {
    return null;
  }

  return window.localStorage;
}

async function getItem(key: string) {
  if (Platform.OS === 'web') {
    return getWebStorage()?.getItem(key) ?? null;
  }

  return SecureStore.getItemAsync(key);
}

async function setItem(key: string, value: string) {
  if (Platform.OS === 'web') {
    getWebStorage()?.setItem(key, value);
    return;
  }

  await SecureStore.setItemAsync(key, value);
}

async function removeItem(key: string) {
  if (Platform.OS === 'web') {
    getWebStorage()?.removeItem(key);
    return;
  }

  await SecureStore.deleteItemAsync(key);
}

export const sessionStorage = {
  async getClientToken() {
    return getItem(CLIENT_TOKEN_KEY);
  },

  async setClientToken(token: string) {
    await setItem(CLIENT_TOKEN_KEY, token);
  },

  async clearClientToken() {
    await removeItem(CLIENT_TOKEN_KEY);
  },

  async getClientMaster(): Promise<StoredMaster | null> {
    const raw = await getItem(CLIENT_MASTER_KEY);

    if (!raw) {
      return null;
    }

    try {
      return JSON.parse(raw) as StoredMaster;
    } catch {
      return null;
    }
  },

  async setClientMaster(master: StoredMaster) {
    await setItem(CLIENT_MASTER_KEY, JSON.stringify(master));
  },

  async clearClientMaster() {
    await removeItem(CLIENT_MASTER_KEY);
  },
};
