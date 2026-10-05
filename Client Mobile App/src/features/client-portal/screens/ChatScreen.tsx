import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, Linking, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { subscribeToChatThread } from '../../../shared/realtime/chatSocket';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { ScreenHeader } from '../../../shared/ui/ScreenHeader';
import { useAppTheme } from '../../../theme/theme';
import { ChatMessageDto } from '../api/contracts';
import { clientPortalApi } from '../api/clientPortalApi';
import { CHAT_ACTION_URL, useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Chat'>;

// Live delivery via Pusher (when EXPO_PUBLIC_PUSHER_APP_KEY is set) does the
// heavy lifting; this poll is just the fallback/reconciliation net, same
// spirit as the CRM dashboard's own 30s thread-list poll (chat/index.blade.php).
const POLL_INTERVAL_MS = 5000;

export function ChatScreen({ navigation }: Props) {
  const { token, master, notifications, markNotificationsRead } = useClientPortal();
  const theme = useAppTheme(master?.branding);

  const [threadId, setThreadId] = useState<number | null>(null);
  const [messages, setMessages] = useState<ChatMessageDto[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [draft, setDraft] = useState('');
  const [sending, setSending] = useState(false);
  const listRef = useRef<FlatList<ChatMessageDto>>(null);

  const load = useCallback(async () => {
    if (!token) {
      return;
    }

    try {
      const response = await clientPortalApi.getChat(token);
      setThreadId(response.data.id);
      setMessages(response.data.messages);
      setError(null);
    } catch (fetchError) {
      const message = fetchError instanceof Error ? fetchError.message : 'Не удалось загрузить чат.';
      setError(message);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      void load();

      const interval = setInterval(() => {
        void load();
      }, POLL_INTERVAL_MS);

      return () => clearInterval(interval);
    }, [load]),
  );

  // Opening the dialog is what "reads" the master's message notifications.
  useEffect(() => {
    const unreadChatIds = (notifications ?? [])
      .filter((item) => !item.is_read && item.action_url === CHAT_ACTION_URL)
      .map((item) => item.id);

    if (unreadChatIds.length > 0) {
      void markNotificationsRead(unreadChatIds);
    }
  }, [notifications, markNotificationsRead]);

  useEffect(() => {
    if (!token || !threadId) {
      return undefined;
    }

    return subscribeToChatThread(token, threadId, () => {
      void load();
    });
  }, [token, threadId, load]);

  useEffect(() => {
    if (messages && messages.length > 0) {
      requestAnimationFrame(() => listRef.current?.scrollToEnd({ animated: true }));
    }
  }, [messages]);

  async function handleSend() {
    const body = draft.trim();

    if (!body || !token || sending) {
      return;
    }

    setSending(true);
    setDraft('');

    try {
      const response = await clientPortalApi.sendChatMessage(token, { body });
      setThreadId(response.data.id);
      setMessages(response.data.messages);
      setError(null);
    } catch (sendError) {
      setDraft(body);
      const message = sendError instanceof Error ? sendError.message : 'Не удалось отправить сообщение.';
      setError(message);
    } finally {
      setSending(false);
    }
  }

  const canSend = draft.trim().length > 0 && !sending;
  const colors = theme.colors;
  const masterName = master?.branding?.appDisplayName?.trim() || master?.name || 'Мастер';

  return (
    <ScreenContainer theme={theme} scrollable={false}>
      <View style={styles.root}>
        <View style={styles.header}>
          <ScreenHeader
            theme={theme}
            title={masterName}
            subtitle="Чат с мастером"
            onBack={() => navigation.goBack()}
          />
        </View>

        {messages === null && !error ? (
          <ActivityIndicator color={colors.primary} style={styles.loader} />
        ) : (
          <FlatList
            ref={listRef}
            data={messages ?? []}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={styles.list}
            onContentSizeChange={() => listRef.current?.scrollToEnd({ animated: false })}
            renderItem={({ item }) => <MessageBubble message={item} theme={theme} />}
            ListEmptyComponent={
              <View style={styles.empty}>
                <View style={[styles.emptyIcon, { backgroundColor: colors.accentSoft }]}>
                  <Ionicons name="chatbubble-ellipses-outline" size={28} color={colors.primary} />
                </View>
                <Text style={[styles.emptyText, { color: colors.textSecondary }]}>
                  {error || 'Напишите мастеру — здесь начнётся ваш разговор.'}
                </Text>
              </View>
            }
          />
        )}

        {error && messages && messages.length > 0 ? (
          <Text style={[styles.inlineError, { color: colors.danger }]}>{error}</Text>
        ) : null}

        <View style={[styles.composer, { borderTopColor: colors.borderSoft, backgroundColor: colors.surface }]}>
          <TextInput
            value={draft}
            onChangeText={setDraft}
            placeholder="Сообщение…"
            placeholderTextColor={colors.textMuted}
            multiline
            numberOfLines={1}
            style={[
              styles.input,
              {
                backgroundColor: colors.inputBackground,
                borderColor: colors.borderSoft,
                color: colors.textPrimary,
              },
            ]}
          />
          <Pressable
            accessibilityLabel="Отправить"
            accessibilityRole="button"
            onPress={handleSend}
            disabled={!canSend}
            style={[
              styles.sendButton,
              {
                backgroundColor: colors.primary,
                opacity: canSend ? 1 : 0.4,
              },
            ]}
          >
            <Ionicons name="arrow-up" size={22} color="#ffffff" />
          </Pressable>
        </View>
      </View>
    </ScreenContainer>
  );
}

function MessageBubble({ message, theme }: { message: ChatMessageDto; theme: ReturnType<typeof useAppTheme> }) {
  const isMine = message.from_me;
  const colors = theme.colors;

  return (
    <View style={[styles.bubbleRow, isMine ? styles.bubbleRowMine : styles.bubbleRowTheirs]}>
      <View
        style={[
          styles.bubble,
          isMine
            ? { backgroundColor: colors.primary, borderBottomRightRadius: 6 }
            : {
                backgroundColor: colors.surface,
                borderColor: colors.borderSoft,
                borderWidth: 1,
                borderBottomLeftRadius: 6,
              },
        ]}
      >
        {message.body ? (
          <Text style={[styles.bubbleText, { color: isMine ? '#ffffff' : colors.textPrimary }]}>
            {message.body}
          </Text>
        ) : null}
        {message.attachment_url ? (
          <Pressable onPress={() => Linking.openURL(message.attachment_url as string)}>
            <Text
              style={[
                styles.attachmentLabel,
                { color: isMine ? '#ffffff' : colors.primary },
              ]}
            >
              {message.attachment_name || 'Вложение'}
            </Text>
          </Pressable>
        ) : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  header: {
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 8,
  },
  loader: {
    marginTop: 24,
  },
  list: {
    paddingHorizontal: 20,
    paddingVertical: 12,
    gap: 8,
    flexGrow: 1,
  },
  bubbleRow: {
    flexDirection: 'row',
  },
  bubbleRowMine: {
    justifyContent: 'flex-end',
  },
  bubbleRowTheirs: {
    justifyContent: 'flex-start',
  },
  bubble: {
    maxWidth: '80%',
    borderRadius: 20,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  bubbleText: {
    fontSize: 16,
    lineHeight: 22,
  },
  attachmentLabel: {
    fontSize: 14,
    fontWeight: '600',
    marginTop: 4,
    textDecorationLine: 'underline',
  },
  empty: {
    alignItems: 'center',
    paddingTop: 56,
    gap: 12,
  },
  emptyIcon: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyText: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    paddingHorizontal: 32,
  },
  inlineError: {
    fontSize: 13,
    textAlign: 'center',
    paddingBottom: 4,
  },
  composer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 10,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderTopWidth: 1,
  },
  input: {
    flex: 1,
    minHeight: 44,
    maxHeight: 120,
    borderWidth: 1,
    borderRadius: 22,
    paddingHorizontal: 16,
    paddingVertical: 10,
    fontSize: 16,
  },
  sendButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
