import Constants from 'expo-constants';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';

/**
 * Best-effort: returns null (never throws) whenever push isn't available —
 * web, permission denied, or no EAS project id configured yet. Android
 * remote push through Expo now requires a real EAS project (and, for
 * production, Firebase's google-services.json) — until app.json's
 * `extra.eas.projectId` is filled in, this always resolves null, the same
 * "works once real credentials exist" state Phase 1 left the App Link
 * fingerprints in.
 */
export async function registerForPushNotificationsAsync(): Promise<string | null> {
  if (Platform.OS === 'web') {
    return null;
  }

  try {
    if (Platform.OS === 'android') {
      await Notifications.setNotificationChannelAsync('default', {
        name: 'default',
        importance: Notifications.AndroidImportance.DEFAULT,
      });
    }

    const existing = await Notifications.getPermissionsAsync();
    let status = existing.status;

    if (status !== 'granted') {
      const requested = await Notifications.requestPermissionsAsync();
      status = requested.status;
    }

    if (status !== 'granted') {
      return null;
    }

    const projectId = Constants.expoConfig?.extra?.eas?.projectId ?? Constants.easConfig?.projectId;

    if (!projectId) {
      return null;
    }

    const token = await Notifications.getExpoPushTokenAsync({ projectId });

    return token.data;
  } catch {
    return null;
  }
}
