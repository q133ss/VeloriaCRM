import Pusher, { Channel } from 'pusher-js';

import { API_BASE_URL } from '../config/api';
import { PUSHER_APP_CLUSTER, PUSHER_APP_KEY } from '../config/broadcasting';

let sharedClient: Pusher | null = null;

/**
 * One socket connection per app run, reused across whichever chat thread is
 * open — mirrors the web dashboard's `ensureEchoInstance()` (layouts/app.blade.php),
 * just without Laravel Echo (pusher-js alone is enough for a single channel).
 */
function ensureClient(token: string): Pusher | null {
  if (!PUSHER_APP_KEY) {
    // Not configured yet — same "works once credentials exist" state Phase 1/5
    // left the App Link fingerprints and EAS project id in. ChatScreen's own
    // polling covers delivery until then.
    return null;
  }

  if (sharedClient) {
    return sharedClient;
  }

  sharedClient = new Pusher(PUSHER_APP_KEY, {
    cluster: PUSHER_APP_CLUSTER,
    forceTLS: true,
    authorizer: (channel) => ({
      authorize: (socketId, callback) => {
        fetch(`${API_BASE_URL}/broadcasting/auth`, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
        })
          .then((response) => response.json())
          .then((data) => callback(null, data))
          .catch((error) => callback(error, null));
      },
    }),
  });

  return sharedClient;
}

export function subscribeToChatThread(
  token: string,
  threadId: number,
  onMessage: () => void,
): () => void {
  let channel: Channel | null = null;

  try {
    const client = ensureClient(token);

    if (!client) {
      return () => undefined;
    }

    channel = client.subscribe(`private-chat-thread.${threadId}`);
    channel.bind('ChatMessageCreated', onMessage);
  } catch {
    // Live delivery is a nice-to-have; ChatScreen's polling is the fallback.
    return () => undefined;
  }

  return () => {
    try {
      channel?.unbind('ChatMessageCreated', onMessage);
      sharedClient?.unsubscribe(`private-chat-thread.${threadId}`);
    } catch {
      // Best-effort cleanup.
    }
  };
}
