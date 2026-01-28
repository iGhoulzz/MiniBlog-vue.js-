// src/stores/chat.js
import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import api from '../services/api';
import { useAuthStore } from './auth';
import pusherClient, { setRealtimeAuthToken } from '../services/realtime';

const WINDOW_LIMIT = 3;
const READ_KEY_PREFIX = 'chat:lastRead:';
const emptyObject = () => Object.create(null);

const parseBooleanEnv = (value, fallback = false) => {
  if (value === undefined || value === null || value === '') return fallback;
  if (typeof value === 'boolean') return value;
  const normalized = String(value).trim().toLowerCase();
  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;
  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;
  return fallback;
};

function readPersistedMap(key) {
  if (typeof window === 'undefined' || !key) return emptyObject();
  try {
    return JSON.parse(window.localStorage.getItem(key) ?? '{}') ?? emptyObject();
  } catch {
    return emptyObject();
  }
}

function persistMap(key, value) {
  if (typeof window === 'undefined' || !key) return;
  window.localStorage.setItem(key, JSON.stringify(value));
}

const storageKeyForUser = (userId) => (userId ? `${READ_KEY_PREFIX}${userId}` : null);
const inboxChannelEnabled = parseBooleanEnv(import.meta.env.VITE_CHAT_INBOX_ENABLED, false);
const realtimeDebugEnabled = parseBooleanEnv(import.meta.env.VITE_CHAT_DEBUG ?? import.meta.env.DEV, import.meta.env.DEV);
const realtimeLog = (...args) => {
  if (realtimeDebugEnabled) {
    console.log('[chat:realtime]', ...args);
  }
};

let dmAudioContext = null;

function playIncomingDmChime() {
  if (typeof window === 'undefined') return;

  const AudioContextCtor = window.AudioContext ?? window.webkitAudioContext;
  if (!AudioContextCtor) return;

  try {
    if (!dmAudioContext) {
      dmAudioContext = new AudioContextCtor();
    }

    if (dmAudioContext.state === 'suspended') {
      dmAudioContext.resume().catch(() => {});
    }

    const oscillator = dmAudioContext.createOscillator();
    const gain = dmAudioContext.createGain();
    const now = dmAudioContext.currentTime;
    const duration = 0.35;

    oscillator.type = 'triangle';
    oscillator.frequency.value = 720;
    oscillator.connect(gain);
    gain.connect(dmAudioContext.destination);

    gain.gain.setValueAtTime(0.0001, now);
    gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + duration);

    oscillator.start(now);
    oscillator.stop(now + duration);
  } catch (error) {
    if (import.meta.env?.DEV) {
      console.warn('[chat] Unable to play DM notification sound', error);
    }
  }
}

export const useChatStore = defineStore('chat', () => {
  const authStore = useAuthStore();

  // state
  const conversations = ref([]);
  const openWindowIds = ref([]);
  const activeWindowId = ref(null);
  const drafts = ref(emptyObject());
  const pendingTargets = ref(emptyObject());
  const isLoading = ref(false);
  const error = ref('');
  const initializedForUser = ref(null);

  const realtimeClient = ref(null);
  const lastReadByConversation = ref(emptyObject());
  const unreadByConversation = ref(emptyObject());
  const readerSnapshots = ref(emptyObject());
  const readerSnapshotsVersion = ref(0);
  const userInboxChannel = ref(null);

  // runtime helpers to track subs/bindings
  const subscribedConversationIds = new Set(); // number ids we already subscribed to
  const realtimeBindings = new Map(); // convId -> { channel, messageHandler, readHandler }
  const inflightReadSyncs = new Set(); // convId currently syncing read receipts
  const pendingConversationSubscriptions = new Set();
  let pendingInboxSubscription = false;
  let bootstrapPromise = null;

  // ======= watchers =======

  // boot/reset on auth token changes
  watch(
    () => authStore.token,
    (token) => {
      setRealtimeAuthToken(token);
      if (!token) {
        realtimeLog('auth token cleared; tearing down realtime connection.');
        reset();
        return;
      }
      realtimeLog('auth token detected; bootstrapping chat.');
      bootstrap();
      // ensure Pusher picks up refreshed credentials without thrashing channels
      if (realtimeClient.value && realtimeClient.value.connection?.state === 'disconnected') {
        try {
          realtimeLog('reconnecting existing realtime client after token refresh.');
          realtimeClient.value.connect();
        } catch (error) {
          console.error('[chat] Failed to reconnect realtime client', error);
        }
      }
    },
    { immediate: true }
  );

  // persist read markers per user
  watch(
    lastReadByConversation,
    (map) => {
      if (!authStore.user?.id) return;
      persistMap(storageKeyForUser(authStore.user.id), map);
    },
    { deep: true }
  );

  // ======= derived =======

  const sortedConversations = computed(() =>
    [...conversations.value].sort((a, b) => getConversationSortValue(b) - getConversationSortValue(a))
  );

  const totalUnread = computed(() =>
    Object.values(unreadByConversation.value).reduce((sum, count) => sum + Number(count || 0), 0)
  );

  // ======= lifecycle =======

  async function bootstrap() {
    if (bootstrapPromise) {
      return bootstrapPromise;
    }

    bootstrapPromise = (async () => {
      if (!authStore.token) return;

      const bootToken = authStore.token;
      await authStore.fetchUser();
      if (!authStore.user?.id || bootToken !== authStore.token) {
        return;
      }

      if (initializedForUser.value && initializedForUser.value !== authStore.user.id) {
        realtimeLog('User context changed; resetting chat store.');
        reset();
      }

      if (initializedForUser.value === authStore.user?.id) {
        realtimeLog('Chat already initialized for user; refreshing subscriptions.');
        if (!realtimeClient.value) {
          connectRealtime('resume-existing-user');
        }
        ensureUserInboxSubscription();
        syncSubscriptions('bootstrap-existing-user');
        return;
      }

      initializedForUser.value = authStore.user.id;
      lastReadByConversation.value = readPersistedMap(storageKeyForUser(authStore.user.id));
      unreadByConversation.value = emptyObject();
      readerSnapshots.value = emptyObject();

      connectRealtime('initial-bootstrap');
      await fetchConversations();
    })().finally(() => {
      bootstrapPromise = null;
    });

    return bootstrapPromise;
  }

  function reset() {
    realtimeLog('Resetting chat store state.');
    bootstrapPromise = null;
    conversations.value = [];
    openWindowIds.value = [];
    activeWindowId.value = null;
    drafts.value = emptyObject();
    pendingTargets.value = emptyObject();
    initializedForUser.value = null;
    error.value = '';
    lastReadByConversation.value = emptyObject();
    unreadByConversation.value = emptyObject();
    readerSnapshots.value = emptyObject();
    readerSnapshotsVersion.value = 0;
    pendingConversationSubscriptions.clear();
    pendingInboxSubscription = false;

    disconnectRealtime();
  }

  // ======= data loading =======

  async function fetchConversations() {
    if (!authStore.token) return;
    isLoading.value = true;
    error.value = '';
    try {
      const { data } = await api.get('/conversations');
      const normalized = data.map(normalizeConversation);
      conversations.value = normalized;

      normalized.forEach((conversation) => {
        hydrateReaderSnapshot(conversation);
        refreshUnreadCount(conversation.id, conversation);
      });

      if (realtimeClient.value) {
        syncSubscriptions('fetchConversations');
      }
    } catch (err) {
      console.error('[chat] load conversations failed', err);
      error.value = 'Unable to load conversations right now.';
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchConversationById(conversationId) {
    if (!authStore.token) return null;
    try {
      const { data } = await api.get(`/conversations/${conversationId}`);
      const normalized = normalizeConversation(data);
      upsertConversation(normalized);
      hydrateReaderSnapshot(normalized);
      refreshUnreadCount(normalized.id, normalized);
      // On-demand subscription is fine (user action and usually after connected)
      ensureSubscription(normalized.id);
      return normalized;
    } catch (err) {
      console.error('[chat] load one conversation failed', err);
      throw err;
    }
  }

  function getConversationById(id) {
    return conversations.value.find((c) => c.id === Number(id));
  }

  // ======= normalization/helpers =======

  function normalizeConversation(raw) {
    const messages = (raw.messages ?? [])
      .map((m) => normalizeMessage(m, raw.users))
      .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

    return {
      ...raw,
      messages,
      updated_at: getConversationSortValue({ ...raw, messages }),
    };
  }

  function normalizeMessage(message, participants = []) {
    const m = { ...message, created_at: message.created_at ?? new Date().toISOString() };
    if (!m.user && participants?.length) {
      m.user = participants.find((p) => p.id === m.user_id) ?? null;
    }

    const participantsById = new Map((participants ?? []).map((p) => [p.id, p]));
    const rawReaders = Array.isArray(m.read_by) ? m.read_by : [];
    m.read_by = rawReaders
      .map((reader) => {
        const id = Number(reader.id ?? reader.user_id ?? reader.user?.id);
        if (!id) return null;

        const fallbackUser = participantsById.get(id);
        const name = reader.name ?? reader.user?.name ?? fallbackUser?.name ?? null;
        const avatarUrl =
          reader.avatar_url ??
          reader.avatar ??
          reader.user?.avatar_url ??
          fallbackUser?.avatar_url ??
          null;
        const readAt =
          reader.read_at ??
          reader.pivot?.read_at ??
          reader.updated_at ??
          reader.created_at ??
          null;

        return {
          id,
          name,
          avatar_url: avatarUrl,
          read_at: readAt,
        };
      })
      .filter(Boolean);

    return m;
  }

  function hydrateReaderSnapshot(conversation) {
    if (!conversation?.id) return;

    const key = String(conversation.id);
    const base = readerSnapshots.value[key] ?? emptyObject();
    const next = { ...base };
    let mutated = false;

    (conversation.messages ?? []).forEach((message) => {
      const readers = getMessageReaders(message);
      readers.forEach((reader) => {
        const entry = buildReaderEntry(reader, conversation.users, message);
        if (!entry) return;
        if (!next[entry.id] || shouldReplaceSnapshot(next[entry.id], entry)) {
          next[entry.id] = entry;
          mutated = true;
        }
      });
    });

    if (!readerSnapshots.value[key] || mutated) {
      readerSnapshots.value = { ...readerSnapshots.value, [key]: next };
      bumpReaderSnapshotsVersion();
    }
  }

  function buildReaderEntry(reader, participants = [], message = null) {
    const id = Number(reader?.id ?? reader?.user_id ?? reader?.user?.id);
    if (!id) return null;

    const participant = participants?.find((user) => Number(user.id) === id);
    const name = reader?.name ?? reader?.user?.name ?? participant?.name ?? 'Participant';
    const avatarUrl =
      reader?.avatar_url ??
      reader?.avatar ??
      reader?.user?.avatar_url ??
      participant?.avatar_url ??
      null;
    const readAt =
      reader?.read_at ??
      reader?.pivot?.read_at ??
      reader?.updated_at ??
      reader?.created_at ??
      message?.created_at ??
      null;
    const messageId = Number(message?.id ?? reader?.messageId ?? reader?.message_id ?? 0);

    return { id, name, avatar_url: avatarUrl, read_at: readAt, messageId };
  }

  function shouldReplaceSnapshot(prev, next) {
    if (!prev) return true;
    const prevId = Number(prev.messageId ?? 0);
    const nextId = Number(next.messageId ?? 0);
    if (nextId > prevId) return true;
    if (nextId < prevId) return false;
    return new Date(next.read_at ?? 0).getTime() > new Date(prev.read_at ?? 0).getTime();
  }

  function updateReaderSnapshot(conversationId, entry) {
    if (!entry?.id) return;
    const key = String(conversationId);
    const base = readerSnapshots.value[key] ?? emptyObject();
    const next = { ...base };
    const current = next[entry.id];
    if (!current || shouldReplaceSnapshot(current, entry)) {
      next[entry.id] = entry;
      readerSnapshots.value = { ...readerSnapshots.value, [key]: next };
      bumpReaderSnapshotsVersion();
    }
  }

  function clearReaderSnapshot(conversationId) {
    const key = String(conversationId);
    if (!(key in readerSnapshots.value)) return;
    const next = { ...readerSnapshots.value };
    delete next[key];
    readerSnapshots.value = next;
    bumpReaderSnapshotsVersion();
  }

  function bumpReaderSnapshotsVersion() {
    readerSnapshotsVersion.value = (readerSnapshotsVersion.value + 1) % Number.MAX_SAFE_INTEGER;
  }

  function upsertConversation(updated) {
    const idx = conversations.value.findIndex((c) => c.id === updated.id);
    if (idx === -1) conversations.value = [...conversations.value, updated];
    else conversations.value = conversations.value.map((c, i) => (i === idx ? updated : c));
    return updated;
  }

  function upsertMessage(conversationId, message) {
    const conv = getConversationById(conversationId);
    if (!conv) return;

    const exists = conv.messages.some((m) => m.id === message.id && message.id != null);
    if (!exists) {
      conv.messages = [...conv.messages, message].sort(
        (a, b) => new Date(a.created_at) - new Date(b.created_at)
      );
      conv.updated_at = message.created_at;
      conversations.value = conversations.value.map((c) => (c.id === conv.id ? conv : c));
    }

    refreshUnreadCount(conversationId);
  }

  function refreshUnreadCount(conversationId, sourceConversation = null) {
    const id = String(conversationId);
    const conv = sourceConversation ?? getConversationById(conversationId);

    if (!conv) {
      if (id in unreadByConversation.value) {
        const next = { ...unreadByConversation.value };
        delete next[id];
        unreadByConversation.value = next;
      }
      return;
    }

    const lastReadId = Number(lastReadByConversation.value[id] ?? 0);
    const currentUserId = authStore.user?.id;
    const nextCount = (conv.messages ?? []).reduce((sum, message) => {
      if (currentUserId && Number(message.user_id) === Number(currentUserId)) {
        return sum;
      }
      return Number(message.id ?? 0) > lastReadId ? sum + 1 : sum;
    }, 0);

    if (unreadByConversation.value[id] === nextCount) return;
    unreadByConversation.value = { ...unreadByConversation.value, [id]: nextCount };
  }

  // ======= realtime =======

  function connectRealtime(reason = 'manual') {
    if (!authStore.token || !pusherClient) {
      if (!pusherClient) {
        console.warn('[chat] Pusher client unavailable; realtime disabled.');
      }
      realtimeLog('connectRealtime skipped', { reason, hasToken: !!authStore.token, hasClient: !!pusherClient });
      return;
    }

    if (!realtimeClient.value) {
      realtimeLog('Initializing realtime client.', { reason });
      realtimeClient.value = pusherClient;
      bindConnectionHandlers();
    }

    // set auth header *first*
    setRealtimeAuthToken(authStore.token);

    const state = realtimeClient.value.connection?.state;
    realtimeLog('connectRealtime invoked', { reason, state });

    if (state === 'initialized' || state === 'disconnected') {
      try {
        realtimeLog('Opening websocket connection.', { reason });
        realtimeClient.value.connect();
      } catch (error) {
        console.error('[chat] Failed to start realtime connection', error);
      }
    }

    syncSubscriptions('connectRealtime');
  }

  const handleConnected = () => {
    realtimeLog('Pusher connected', {
      socketId: realtimeClient.value?.connection?.socket_id ?? null,
    });
    flushPendingSubscriptions();
  };

  const handleStateChange = (states) => {
    realtimeLog('Pusher state change', states);
    if (states.current === 'connected') {
      flushPendingSubscriptions();
    }
  };

  function bindConnectionHandlers() {
    const connection = realtimeClient.value?.connection;
    if (!connection) return;

    try {
      connection.unbind('connected', handleConnected);
      connection.unbind('state_change', handleStateChange);
    } catch {}

    connection.bind('connected', handleConnected);
    connection.bind('state_change', handleStateChange);
  }

  function isConnectionReady() {
    return realtimeClient.value?.connection?.state === 'connected';
  }

  function flushPendingSubscriptions() {
    if (!isConnectionReady()) return;

    pendingConversationSubscriptions.forEach((id) => {
      pendingConversationSubscriptions.delete(id);
      subscribeToConversation(id);
    });

    if (pendingInboxSubscription) {
      pendingInboxSubscription = false;
      subscribeToInboxChannel();
    }
  }

  function unsubscribeConversation(conversationId) {
    const id = Number(conversationId);
    const binding = realtimeBindings.get(id);
    if (!binding) return;

    const { channel, messageHandler, readHandler } = binding;
    try {
      channel.unbind('MessageSent', messageHandler);
      channel.unbind('App\\Events\\MessageSent', messageHandler);
    } catch {}
    try {
      channel.unbind('MessagesRead', readHandler);
      channel.unbind('App\\Events\\MessagesRead', readHandler);
    } catch {}
    try {
      if (channel?.name) realtimeClient.value?.unsubscribe(channel.name);
    } catch {}

    realtimeBindings.delete(id);
    subscribedConversationIds.delete(id);
    pendingConversationSubscriptions.delete(id);
    realtimeLog('Unsubscribed from conversation channel', { conversationId: id });
  }

  function syncSubscriptions(reason = 'sync') {
    if (!realtimeClient.value) {
      realtimeLog('syncSubscriptions skipped - no realtime client', { reason });
      return;
    }
    conversations.value.forEach((c) => ensureSubscription(c.id));
    if (inboxChannelEnabled) ensureUserInboxSubscription();
    if (!isConnectionReady()) {
      realtimeLog('syncSubscriptions queued (connection not ready)', {
        reason,
        state: realtimeClient.value.connection?.state,
        pendingCount: pendingConversationSubscriptions.size,
      });
    }
  }

  function disconnectRealtime() {
    // unbind + unsubscribe
    Array.from(realtimeBindings.keys()).forEach(unsubscribeConversation);
    subscribedConversationIds.clear();
    pendingConversationSubscriptions.clear();
    pendingInboxSubscription = false;

    teardownUserInboxSubscription();

    try {
      if (realtimeClient.value) {
        realtimeLog('Disconnecting realtime client.');
        realtimeClient.value.connection?.unbind('connected', handleConnected);
        realtimeClient.value.connection?.unbind('state_change', handleStateChange);
        realtimeClient.value.disconnect();
      }
    } catch {}
    realtimeClient.value = null;
  }

  function ensureSubscription(conversationId) {
    const id = Number(conversationId);
    if (!id || subscribedConversationIds.has(id)) return;

    if (!realtimeClient.value || !isConnectionReady()) {
      pendingConversationSubscriptions.add(id);
      realtimeLog('Queued conversation subscription', {
        conversationId: id,
        state: realtimeClient.value?.connection?.state ?? 'no-client',
      });
      return;
    }

    subscribeToConversation(id);
  }

  function subscribeToConversation(conversationId) {
    const id = Number(conversationId);
    if (!realtimeClient.value || !id || subscribedConversationIds.has(id)) return;

    const channelName = `private-conversation.${id}`;
    const channel = realtimeClient.value.subscribe(channelName);

    const messageHandler = (payload) => handleIncomingMessage(id, payload.message ?? payload);
    const readHandler = (payload) => handleMessagesRead(id, payload);

    // Bind both names: 'MessageSent' (when broadcastAs is set) and the default FQCN
    channel.bind('MessageSent', messageHandler);
    channel.bind('App\\Events\\MessageSent', messageHandler);
    channel.bind('MessagesRead', readHandler);
    channel.bind('App\\Events\\MessagesRead', readHandler);

    subscribedConversationIds.add(id);
    pendingConversationSubscriptions.delete(id);
    realtimeBindings.set(id, { channel, messageHandler, readHandler });
    realtimeLog('Subscribed to conversation channel', { conversationId: id, channel: channelName });
  }

  function ensureUserInboxSubscription() {
    if (!inboxChannelEnabled || !authStore.user?.id) return;
    if (!realtimeClient.value || !isConnectionReady()) {
      pendingInboxSubscription = true;
      realtimeLog('Queued inbox subscription', {
        state: realtimeClient.value?.connection?.state ?? 'no-client',
      });
      return;
    }

    subscribeToInboxChannel();
  }

  function subscribeToInboxChannel() {
    if (!inboxChannelEnabled || !realtimeClient.value || !authStore.user?.id) return;
    if (userInboxChannel.value?.userId === authStore.user.id) return;

    teardownUserInboxSubscription();

    const channelName = `private-user.${authStore.user.id}`;
    const channel = realtimeClient.value.subscribe(channelName);

    const createdHandler = (payload) => handleConversationCreated(payload);

    channel.bind('ConversationCreated', createdHandler);
    channel.bind('App\\Events\\ConversationCreated', createdHandler);

    userInboxChannel.value = { channel, createdHandler, userId: authStore.user.id };
    realtimeLog('Subscribed to inbox channel', { channel: channelName });
    pendingInboxSubscription = false;
  }

  function teardownUserInboxSubscription() {
    if (!inboxChannelEnabled) return;
    const binding = userInboxChannel.value;
    if (!binding) return;

    try {
      binding.channel.unbind('ConversationCreated', binding.createdHandler);
      binding.channel.unbind('App\\Events\\ConversationCreated', binding.createdHandler);
      if (binding.channel?.name) {
        realtimeClient.value?.unsubscribe(binding.channel.name);
      }
    } catch {}

    userInboxChannel.value = null;
    pendingInboxSubscription = false;
    realtimeLog('Inbox channel unsubscribed.');
  }

  function handleIncomingMessage(conversationId, payload) {
    let conv = getConversationById(conversationId);

    // If not present (rare race), fetch once
    if (!conv) {
      fetchConversationById(conversationId).catch(() => {});
      return;
    }

    const normalized = normalizeMessage(payload, conv?.users ?? []);
    if (!normalized.user && normalized.user_id && authStore.user?.id === normalized.user_id) {
      normalized.user = authStore.user;
    }
    upsertMessage(conversationId, normalized);
    refreshUnreadCount(conversationId);

    const isOwn = normalized.user_id === authStore.user?.id;
    realtimeLog('Incoming realtime message', {
      conversationId,
      messageId: normalized.id ?? null,
      isOwn,
    });
    if (!isOwn) {
      playIncomingDmChime();
    }
    if (isOwn || isWindowFocused(conversationId)) {
      markConversationRead(conversationId);
    }
  }

  function handleConversationCreated(payload) {
    if (!inboxChannelEnabled) return;
    const rawConversation = payload?.conversation ?? payload;
    if (!rawConversation?.id) return;

    const normalized = normalizeConversation(rawConversation);
    upsertConversation(normalized);
    hydrateReaderSnapshot(normalized);
    refreshUnreadCount(normalized.id, normalized);
    ensureSubscription(normalized.id);
    realtimeLog('ConversationCreated event received', { conversationId: normalized.id });
  }

  function handleMessagesRead(conversationId, payload) {
    let conv = getConversationById(conversationId);
    if (!conv) {
      fetchConversationById(conversationId).catch(() => {});
      return;
    }

    const messageIds = normalizeReadMessageIds(payload?.read_message_ids);
    if (!messageIds.length) {
      realtimeLog('MessagesRead payload ignored (no ids)', { payload });
      return;
    }

    const readerId = Number(payload?.user_id);
    const participant =
      conv.users?.find((user) => Number(user.id) === readerId) ?? null;

    const reader = readerId
      ? {
          id: readerId,
          name: payload?.user_name ?? participant?.name ?? null,
          avatar_url: participant?.avatar_url ?? null,
        }
      : null;

    applyReadReceipts(conversationId, messageIds, payload?.read_at, reader);
    refreshUnreadCount(conversationId);
    realtimeLog('Read receipts applied', {
      conversationId,
      messageIds,
      readerId,
    });
  }

  function normalizeReadMessageIds(raw) {
    if (Array.isArray(raw)) {
      return raw;
    }
    if (raw && typeof raw === 'object') {
      return Object.values(raw);
    }
    return [];
  }

  function removeConversation(conversationId) {
    const id = Number(conversationId);
    if (Number.isNaN(id)) return;

    unsubscribeConversation(id);
    conversations.value = conversations.value.filter((conversation) => conversation.id !== id);

    const key = String(id);
    if (key in lastReadByConversation.value) {
      const next = { ...lastReadByConversation.value };
      delete next[key];
      lastReadByConversation.value = next;
    }

    if (key in unreadByConversation.value) {
      const nextUnread = { ...unreadByConversation.value };
      delete nextUnread[key];
      unreadByConversation.value = nextUnread;
    }

    pendingConversationSubscriptions.delete(id);
    clearReaderSnapshot(id);

    openWindowIds.value = openWindowIds.value.filter((windowId) => windowId !== key);
    if (activeWindowId.value === key) {
      activeWindowId.value = openWindowIds.value.at(-1) ?? null;
    }

    removeDraft(key);
    removePendingTarget(key);
  }

  // ======= windows / composing =======

  async function openConversation(conversationId) {
    const numericId = Number(conversationId);
    const loaded = await ensureConversationLoaded(numericId);
    ensureWindow(String(loaded.id));
    markConversationRead(numericId);
    return getConversationById(numericId);
  }

  async function ensureConversationLoaded(conversationId) {
    let conv = getConversationById(conversationId);
    if (!conv) conv = await fetchConversationById(conversationId);
    return conv;
  }

  function ensureWindow(windowId) {
    const id = String(windowId);
    if (!openWindowIds.value.includes(id)) {
      const next = [...openWindowIds.value];
      if (next.length >= WINDOW_LIMIT) next.shift();
      openWindowIds.value = [...next, id];
    }
    focusWindow(id);
  }

  function openDmWith(user) {
    if (!user?.id) return null;

    const existing = conversations.value.find(
      (c) =>
        c.users?.length &&
        c.users.some((p) => p.id === user.id) &&
        c.users.some((p) => p.id === authStore.user?.id) &&
        c.users.length === 2
    );

    if (existing) {
      openConversation(existing.id);
      return existing;
    }

    const windowId = `pending-${user.id}`;
    setPendingTarget(windowId, user);
    ensureWindow(windowId);
    return null;
  }

  function closeWindow(windowId) {
    const id = String(windowId);
    openWindowIds.value = openWindowIds.value.filter((x) => x !== id);
    if (activeWindowId.value === id) activeWindowId.value = openWindowIds.value.at(-1) ?? null;
    removeDraft(id);
    removePendingTarget(id);
  }

  function focusWindow(windowId) {
    const id = String(windowId);
    activeWindowId.value = id;
    if (!isPendingWindow(id) && !Number.isNaN(Number(id))) {
      markConversationRead(Number(id));
    }
  }

  function setPendingTarget(windowId, user) {
    pendingTargets.value = { ...pendingTargets.value, [String(windowId)]: user };
  }
  function removePendingTarget(windowId) {
    const id = String(windowId);
    if (!(id in pendingTargets.value)) return;
    const next = { ...pendingTargets.value };
    delete next[id];
    pendingTargets.value = next;
  }

  function getDraft(windowId) {
    return drafts.value[String(windowId)] ?? '';
  }
  function setDraft(windowId, value) {
    drafts.value = { ...drafts.value, [String(windowId)]: value };
  }
  function removeDraft(windowId) {
    const id = String(windowId);
    if (!(id in drafts.value)) return;
    const next = { ...drafts.value };
    delete next[id];
    drafts.value = next;
  }

  function isPendingWindow(windowId) {
    return String(windowId).startsWith('pending-');
  }
  function replaceWindowId(oldId, newId) {
    openWindowIds.value = openWindowIds.value.map((id) => (id === oldId ? String(newId) : id));
    if (activeWindowId.value === oldId) activeWindowId.value = String(newId);
    if (drafts.value[oldId]) {
      const value = drafts.value[oldId];
      removeDraft(oldId);
      setDraft(newId, value);
    }
  }

  async function sendMessage(windowId, content) {
    const trimmed = content.trim();
    if (!trimmed) return;

    if (!authStore.user) throw new Error('You need to be logged in to send messages.');

    // pending -> create conversation
    if (isPendingWindow(windowId)) {
      const target = pendingTargets.value[windowId];
      if (!target) throw new Error('Select a recipient to start chatting.');

      const { data } = await api.post('/conversations', {
        user_ids: [target.id],
        content: trimmed,
      });

      const conv = normalizeConversation(data);
      upsertConversation(conv);
      hydrateReaderSnapshot(conv);
      refreshUnreadCount(conv.id, conv);
      ensureSubscription(conv.id); // safe on-demand
      replaceWindowId(windowId, String(conv.id));
      removePendingTarget(windowId);
      markConversationRead(conv.id);
      setDraft(conv.id, '');
      return;
    }

    const numericId = Number(windowId);
    if (Number.isNaN(numericId)) return;

    await ensureConversationLoaded(numericId);

    const { data } = await api.post(`/conversations/${numericId}/messages`, { content: trimmed });

    const normalized = normalizeMessage(
      { ...data, user: authStore.user },
      getConversationById(numericId)?.users ?? []
    );

    upsertMessage(numericId, normalized);
    markConversationRead(numericId);
    setDraft(numericId, '');
  }

  async function clearConversation(conversationId) {
    const id = Number(conversationId);
    if (Number.isNaN(id)) return;

    await api.delete(`/conversations/${id}`);
    removeConversation(id);
  }

  // ======= read/unread helpers =======

  function markConversationRead(conversationId, { sync = true } = {}) {
    const conv = getConversationById(conversationId);
    if (!conv || !conv.messages.length) return;

    const hadUnread = unreadCount(conversationId) > 0;
    const last = conv.messages[conv.messages.length - 1];
    if (!last?.id) return;

    const key = String(conversationId);
    if (lastReadByConversation.value[key] === last.id) {
      if (sync && hadUnread && authStore.user?.id) queueReadSync(conversationId);
      return;
    }

    lastReadByConversation.value = { ...lastReadByConversation.value, [key]: last.id };

    if (sync && hadUnread && authStore.user?.id) {
      queueReadSync(conversationId);
    }

    refreshUnreadCount(conversationId);
  }

  function queueReadSync(conversationId) {
    const id = Number(conversationId);
    if (Number.isNaN(id) || inflightReadSyncs.has(id)) return;

    inflightReadSyncs.add(id);
    syncConversationRead(id)
      .catch(() => {})
      .finally(() => inflightReadSyncs.delete(id));
  }

  async function syncConversationRead(conversationId) {
    const id = Number(conversationId);
    if (Number.isNaN(id)) return;

    try {
      const { data } = await api.patch(`/conversations/${id}/read`);
      const updatedMessageIds = Array.isArray(data?.message_ids) ? data.message_ids : [];
      if (updatedMessageIds.length) {
        applyReadReceipts(id, updatedMessageIds, data?.read_at, authStore.user);
        refreshUnreadCount(id);
      }
    } catch (err) {
      console.error('[chat] sync read receipts failed', err);
    }
  }

  function applyReadReceipts(conversationId, messageIds, readAt, reader = authStore.user) {
    const conv = getConversationById(conversationId);
    if (!conv || !Array.isArray(messageIds) || !messageIds.length) return;

    const normalizedIds = messageIds
      .map((messageId) => Number(messageId))
      .filter((messageId) => Number.isFinite(messageId));
    if (!normalizedIds.length) return;

    const idSet = new Set(normalizedIds);
    const latestMessageId = Math.max(...normalizedIds);

    const targetReader = reader?.id ? reader : authStore.user;
    if (!targetReader?.id) return;

    const participant = conv.users?.find((user) => Number(user.id) === Number(targetReader.id));
    const normalizedReader = {
      id: Number(targetReader.id),
      name: targetReader.name ?? participant?.name ?? 'Participant',
      avatar_url: targetReader.avatar_url ?? participant?.avatar_url ?? null,
    };

    const iso = readAt ?? new Date().toISOString();
    let mutated = false;

    const messages = conv.messages.map((message) => {
      if (!idSet.has(Number(message.id))) return message;

      const readers = getMessageReaders(message);
      const updatedReader = { ...normalizedReader, read_at: iso };
      const existingIndex = readers.findIndex((entry) => entry.id === normalizedReader.id);

      if (existingIndex >= 0) {
        const current = readers[existingIndex];
        if (current.read_at === updatedReader.read_at) {
          return message;
        }
      }

      mutated = true;
      const nextReaders =
        existingIndex >= 0
          ? readers.map((entry, idx) => (idx === existingIndex ? updatedReader : entry))
          : [...readers, updatedReader];

      return { ...message, read_by: nextReaders };
    });

    if (mutated) {
      upsertConversation({ ...conv, messages });
    }

    updateReaderSnapshot(conversationId, {
      ...normalizedReader,
      messageId: latestMessageId,
      read_at: iso,
    });
  }

  function unreadCount(conversationId) {
    return unreadByConversation.value[String(conversationId)] ?? 0;
  }

  function isWindowFocused(conversationId) {
    return (
      activeWindowId.value === String(conversationId) &&
      !isPendingWindow(conversationId) &&
      openWindowIds.value.includes(String(conversationId))
    );
  }

  function getConversationSortValue(conversation) {
    const last = conversation.messages?.[conversation.messages.length - 1];
    return last ? new Date(last.created_at).getTime() : new Date(conversation.updated_at ?? 0).getTime();
  }

  function conversationTitle(conversation) {
    if (!conversation?.users?.length) return 'Conversation';
    return conversation.users
      .filter((u) => u.id !== authStore.user?.id)
      .map((u) => u.name)
      .join(', ');
  }

  function getMessageReaders(message) {
    return Array.isArray(message?.read_by) ? message.read_by : [];
  }

  function readersForMessage(conversationId, messageId, { includeSelf = false } = {}) {
    if (!conversationId || !messageId) return [];
    const snapshot = readerSnapshots.value[String(conversationId)];
    if (!snapshot) return [];

    return Object.values(snapshot)
      .filter((entry) => Number(entry.messageId) === Number(messageId))
      .filter((entry) => includeSelf || entry.id !== authStore.user?.id)
      .sort(
        (left, right) => new Date(left.read_at ?? 0).getTime() - new Date(right.read_at ?? 0).getTime()
      );
  }

  function messageStatus(conversationId, message) {
    const currentUserId = authStore.user?.id;
    if (!currentUserId || message?.user_id !== currentUserId) return '';

    if (!message?.id || !conversationId) {
      return 'Sent';
    }

    const readers = readersForMessage(conversationId, message.id, { includeSelf: false });

    if (!readers.length) return 'Sent';
    if (readers.length === 1) {
      return `Read by ${readers[0].name ?? 'participant'}`;
    }
    return `Read by ${readers[0].name ?? 'participant'} +${readers.length - 1}`;
  }

  // expose
  return {
    conversations,
    sortedConversations,
    openWindowIds,
    activeWindowId,
    isLoading,
    error,
    totalUnread,
    drafts,
    pendingTargets,

    readerSnapshotsVersion,

    bootstrap,
    reset,
    fetchConversations,

    openConversation,
    openDmWith,
    closeWindow,
    focusWindow,

    getConversationById,
    getDraft,
    setDraft,

    markConversationRead,
    unreadCount,
    conversationTitle,
    readersForMessage,
    messageStatus,

    sendMessage,
    clearConversation,
  };
});
