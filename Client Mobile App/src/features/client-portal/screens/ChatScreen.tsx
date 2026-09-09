import { useFocusEffect } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, Linking, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { RootStackParamList } from '../../../navigation/types';
import { subscribeToChatThread } from '../../../shared/realtime/chatSocket';
import { ScreenContainer } from '../../../shared/ui/ScreenContainer';
import { useAppTheme } from '../../../theme/theme';
import { ChatMessageDto } from '../api/contracts';
import { clientPortalApi } from '../api/clientPortalApi';
import { useClientPortal } from '../model/clientPortalContext';

type Props = NativeStackScreenProps<RootStackParamList, 'Chat'>;

// Live delivery via Pusher (when EXPO_PUBLIC_PUSHER_APP_KEY is set) does the
// heavy lifting; this poll is just the fallback/reconciliation net, same
// spirit as the CRM dashboard's own 30s thread-list poll (chat/index.blade.php).
const POLL_INTERVAL_MS = 5000;

export function ChatScreen({ navigation }: Props) {
  const { token, master } = useClientPortal();
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

  return (
    <ScreenContainer theme={theme} scrollable={false}>
      <View style={styles.root}>
        <View style={styles.topBar}>
          <Pressable onPress={() => navigation.goBack()} style={styles.backButton}>
            <Text style={[styles.backText, { color: theme.colors.textSecondary }]}>Назад</Text>
          </Pressable>
          <Text style={[styles.title, { color: theme.colors.textPrimary }]}>Чат с мастером</Text>
          <View style={styles.backSpacer} />
        </View>

        {messages === null && !error ? (
          <ActivityIndicator color={theme.colors.primary} style={styles.loader} />
        ) : (
          <FlatList
            ref={listRef}
            data={messages ?? []}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={styles.list}
            onContentSizeChange={() => listRef.current?.scrollToEnd({ animated: false })}
            renderItem={({ item }) => <MessageBubble message={item} theme={theme} />}
            ListEmptyComponent={
              <Text style={[styles.emptyText, { color: theme.colors.textSecondary }]}>
                {error || 'Напишите мастеру — здесь начнётся ваш разговор.'}
              </Text>
            }
          />
        )}

        {error && messages && messages.length > 0 ? (
          <Text style={[styles.inlineError, { color: theme.colors.textSecondary }]}>{error}</Text>
        ) : null}

        <View style={[styles.composer, { borderColor: theme.colors.borderSoft }]}>
          <TextInput
            value={draft}
            onChangeText={setDraft}
            placeholder="Сообщение…"
            placeholderTextColor={theme.colors.textMuted}
            multiline
            style={[
              styles.input,
              {
                backgroundColor: theme.colors.inputBackground,
                borderColor: theme.colors.borderSoft,
                color: theme.colors.textPrimary,
              },
            ]}
          />
          <Pressable
            accessibilityRole="button"
            onPress={handleSend}
            disabled={!canSend}
            style={[
              styles.sendButton,
              {
                backgroundColor: theme.colors.primary,
                opacity: canSend ? 1 : 0.5,
              },
            ]}
          >
            <Text style={styles.sendButtonText}>Отправить</Text>
          </Pressable>
        </View>
      </View>
    </ScreenContainer>
  );
}

function MessageBubble({ message, theme }: { message: ChatMessageDto; theme: ReturnType<typeof useAppTheme> }) {
  const isMine = message.from_me;

  return (
    <View style={[styles.bubbleRow, isMine ? styles.bubbleRowMine : styles.bubbleRowTheirs]}>
      <View
        style={[
          styles.bubble,
          {
            backgroundColor: isMine ? theme.colors.primary : theme.colors.surfaceMuted,
          },
        ]}
      >
        {message.body ? (
          <Text style={[styles.bubbleText, { color: isMine ? '#fffaf2' : theme.colors.textPrimary }]}>
            {message.body}
          </Text>
        ) : null}
        {message.attachment_url ? (
          <Pressable onPress={() => Linking.openURL(message.attachment_url as string)}>
            <Text
              style={[
                styles.attachmentLabel,
                { color: isMine ? '#fffaf2' : theme.colors.primary },
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
    paddingHorizontal: 18,
    paddingTop: 8,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  backButton: {
    paddingVertical: 10,
    paddingRight: 8,
    minWidth: 52,
  },
  backSpacer: {
    minWidth: 52,
  },
  backText: {
    fontSize: 15,
    fontWeight: '600',
  },
  title: {
    fontSize: 18,
    fontWeight: '800',
  },
  loader: {
    marginTop: 24,
  },
  list: {
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
    maxWidth: '78%',
    borderRadius: 18,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  bubbleText: {
    fontSize: 15,
    lineHeight: 21,
  },
  attachmentLabel: {
    fontSize: 14,
    fontWeight: '700',
    marginTop: 4,
    textDecorationLine: 'underline',
  },
  emptyText: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    paddingTop: 32,
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
    paddingVertical: 10,
    borderTopWidth: 1,
  },
  input: {
    flex: 1,
    minHeight: 44,
    maxHeight: 120,
    borderWidth: 1,
    borderRadius: 16,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontSize: 15,
  },
  sendButton: {
    minHeight: 44,
    borderRadius: 16,
    paddingHorizontal: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sendButtonText: {
    color: '#fffaf2',
    fontSize: 14,
    fontWeight: '700',
  },
});
