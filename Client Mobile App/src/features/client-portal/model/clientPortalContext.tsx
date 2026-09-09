import * as Linking from 'expo-linking';
import {
  ReactNode,
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import { Alert } from 'react-native';

import { sessionStorage } from '../../../shared/api/sessionStorage';
import { formatDateLabel } from '../../../shared/format/ruDate';
import { registerForPushNotificationsAsync } from '../../../shared/notifications/pushRegistration';
import { ApiMaster, AppointmentListItemDto, ClientServiceDto, MasterPostDto, VerifyAuthPayload, VerifyLoginResponseData, isMasterSelectionRequired } from '../api/contracts';
import { clientPortalApi } from '../api/clientPortalApi';
import { buildMockHomeFeed } from '../mocks/mockClientPortal';
import {
  AuthMaster,
  HomeFeed,
  NewsPostSummary,
  PendingAuth,
  PendingSelection,
  SessionUser,
  UpcomingAppointmentSummary,
} from './types';

type ClientPortalContextValue = {
  bootstrapping: boolean;
  authBusy: boolean;
  // Raw token for screens that call clientPortalApi themselves (booking,
  // appointments) instead of going through a provider action — everything
  // login-related stays encapsulated above, this is just for authed reads/writes
  // that are screen-local state, not shared app state.
  token: string | null;
  master: AuthMaster | null;
  home: HomeFeed | null;
  session: SessionUser | null;
  pendingAuth: PendingAuth | null;
  pendingSelection: PendingSelection | null;
  requestLoginCode: (email: string) => Promise<void>;
  requestMagicLink: (email: string) => Promise<void>;
  confirmLoginCode: (code: string) => Promise<void>;
  selectMaster: (masterId: number) => Promise<void>;
  resetPendingAuth: () => void;
  refreshHomeFeed: () => Promise<void>;
};

const ClientPortalContext = createContext<ClientPortalContextValue | null>(null);

type ClientPortalProviderProps = {
  children: ReactNode;
};

function mapClientToSessionUser(client: {
  id: number;
  user_id: number;
  name: string | null;
  email: string | null;
  phone: string | null;
}): SessionUser {
  return {
    id: client.id,
    masterId: client.user_id,
    name: client.name?.trim() || 'Клиент',
    email: client.email,
    phone: client.phone,
  };
}

function mapMaster(master: ApiMaster): AuthMaster {
  const hasCustomBranding = Boolean(master.has_custom_branding);
  const branding = hasCustomBranding && master.branding
    ? {
        appDisplayName: master.branding.app_display_name,
        primaryColor: master.branding.primary_color,
        secondaryColor: master.branding.secondary_color,
        logoUrl: master.branding.logo_url,
      }
    : null;

  return {
    id: master.id,
    name: master.name?.trim() || 'Мастер',
    avatarUrl: master.avatar_url ?? null,
    hasCustomBranding,
    hasChat: Boolean(master.has_chat),
    branding,
  };
}

function mapPostsToUpdates(posts: MasterPostDto[]): NewsPostSummary[] {
  return posts.map((post) => ({
    id: post.id,
    title: post.title,
    excerpt: post.body.length > 140 ? `${post.body.slice(0, 140).trimEnd()}…` : post.body,
    body: post.body,
    imageUrl: post.image_url,
    date: post.published_at ? formatDateLabel(post.published_at.slice(0, 10)) : '',
  }));
}

function mapServicesToHomeCards(services: ClientServiceDto[]) {
  return services.slice(0, 4).map((service) => ({
    id: String(service.id),
    title: service.name,
    duration: service.duration_min ? `${service.duration_min} мин` : 'Длительность уточняется',
    price: service.base_price !== null ? `от ${service.base_price} ₽` : 'Цена уточняется',
  }));
}

function pickNextUpcomingAppointment(appointments: AppointmentListItemDto[]): UpcomingAppointmentSummary | null {
  const upcoming = appointments.filter(
    (item): item is AppointmentListItemDto & { date: string; time: string } =>
      item.is_upcoming && item.date !== null && item.time !== null,
  );

  if (upcoming.length === 0) {
    return null;
  }

  // The list comes back newest-scheduled-first (furthest in the future first),
  // so the soonest upcoming one is whichever sorts lowest by date+time, not
  // simply the last upcoming entry — comparing as strings works because both
  // fields are already zero-padded ISO (YYYY-MM-DD / HH:MM).
  const soonest = upcoming.reduce((closest, item) =>
    `${item.date}T${item.time}` < `${closest.date}T${closest.time}` ? item : closest,
  );

  return {
    id: soonest.id,
    serviceLabel: soonest.service_label,
    dateLabel: formatDateLabel(soonest.date),
    timeLabel: soonest.time,
    statusLabel: soonest.status === 'scheduled' ? 'Подтверждено' : soonest.status,
  };
}

export function ClientPortalProvider({ children }: ClientPortalProviderProps) {
  const [bootstrapping, setBootstrapping] = useState(true);
  const [authBusy, setAuthBusy] = useState(false);
  const [token, setToken] = useState<string | null>(null);
  const [master, setMaster] = useState<AuthMaster | null>(null);
  const [home, setHome] = useState<HomeFeed | null>(null);
  const [session, setSession] = useState<SessionUser | null>(null);
  const [pendingAuth, setPendingAuth] = useState<PendingAuth | null>(null);
  const [pendingSelection, setPendingSelection] = useState<PendingSelection | null>(null);

  const loadHomeFeed = useCallback(async (feedToken: string, clientName: string) => {
    let services;
    try {
      const servicesResponse = await clientPortalApi.getServices(feedToken);
      const mapped = mapServicesToHomeCards(servicesResponse.data.services);
      services = mapped.length > 0 ? mapped : undefined;
    } catch {
      services = undefined;
    }

    let nextAppointment: UpcomingAppointmentSummary | null = null;
    try {
      const appointmentsResponse = await clientPortalApi.getAppointments(feedToken);
      nextAppointment = pickNextUpcomingAppointment(appointmentsResponse.data.appointments);
    } catch {
      nextAppointment = null;
    }

    let updates;
    try {
      const postsResponse = await clientPortalApi.getPosts(feedToken);
      const mapped = mapPostsToUpdates(postsResponse.data.posts);
      updates = mapped.length > 0 ? mapped : undefined;
    } catch {
      updates = undefined;
    }

    setHome({ ...buildMockHomeFeed(clientName, services, updates), nextAppointment });
  }, []);

  const hydrateFromToken = useCallback(async (nextToken: string) => {
    const meResponse = await clientPortalApi.getMe(nextToken);
    const nextSession = mapClientToSessionUser(meResponse.data.client);
    const nextMaster = mapMaster(meResponse.data.master);

    setToken(nextToken);
    setSession(nextSession);
    setMaster(nextMaster);

    await loadHomeFeed(nextToken, nextSession.name);

    // Fire-and-forget: push is a nice-to-have, never something login/home
    // should wait on or fail over (registerForPushNotificationsAsync already
    // never throws, resolving null wherever push isn't available yet).
    registerForPushNotificationsAsync()
      .then((expoPushToken) => {
        if (!expoPushToken) {
          return undefined;
        }

        return clientPortalApi.registerDeviceToken(nextToken, { expo_push_token: expoPushToken });
      })
      .catch(() => undefined);
  }, [loadHomeFeed]);

  const refreshHomeFeed = useCallback(async () => {
    if (!token || !session) {
      return;
    }

    await loadHomeFeed(token, session.name);
  }, [loadHomeFeed, token, session]);

  const applyAuthPayload = useCallback(async (payload: VerifyAuthPayload) => {
    await sessionStorage.setClientToken(payload.token);
    setPendingAuth(null);
    setPendingSelection(null);

    // Sourced from `/client/me`, not `payload.client`/`payload.master`: branding
    // (and anything else resolved server-side) then comes from the same single
    // place on both a fresh login and a session restored from a stored token.
    await hydrateFromToken(payload.token);
  }, [hydrateFromToken]);

  const applyVerifyResult = useCallback(async (data: VerifyLoginResponseData, email: string) => {
    if (isMasterSelectionRequired(data)) {
      setPendingAuth(null);
      setPendingSelection({
        selectionToken: data.selection_token,
        email,
        masters: data.masters.map((choice) => ({
          masterId: choice.master_id,
          masterName: choice.master_name,
          clientId: choice.client_id,
        })),
      });
      return;
    }

    await applyAuthPayload(data);
  }, [applyAuthPayload]);

  const confirmMagicLink = useCallback(async (verificationId: string, code: string) => {
    setAuthBusy(true);

    try {
      const response = await clientPortalApi.verifyLogin({ verification_id: verificationId, code });
      await applyVerifyResult(response.data, pendingAuth?.email ?? '');
    } finally {
      setAuthBusy(false);
    }
  }, [applyVerifyResult, pendingAuth]);

  useEffect(() => {
    let mounted = true;

    async function bootstrap() {
      try {
        const storedToken = await sessionStorage.getClientToken();

        if (!storedToken || !mounted) {
          return;
        }

        try {
          await hydrateFromToken(storedToken);
        } catch {
          await sessionStorage.clearClientToken();
          await sessionStorage.clearClientMaster();
          if (mounted) {
            setToken(null);
            setSession(null);
            setHome(null);
            setMaster(null);
          }
        }
      } finally {
        if (mounted) {
          setBootstrapping(false);
        }
      }
    }

    void bootstrap();

    return () => {
      mounted = false;
    };
  }, [hydrateFromToken]);

  useEffect(() => {
    function handleUrl(url: string) {
      const parsed = Linking.parse(url);

      // Two URL shapes reach here: the Android App Link the magic-link email actually
      // sends (`https://<host>/auth/verify`, "auth/verify" lands whole in `path`) and the
      // `veloriaclient://auth/verify` custom scheme the App Link's browser fallback page
      // (and a cold Android-App-Link open) redirects to — there "auth" is the WHATWG
      // authority, so it lands in `hostname` and only "verify" is left in `path`.
      const isAppLinkShape = parsed.path === 'auth/verify';
      const isCustomSchemeShape = parsed.hostname === 'auth' && parsed.path === 'verify';

      if (!isAppLinkShape && !isCustomSchemeShape) {
        return;
      }

      const vid = typeof parsed.queryParams?.vid === 'string' ? parsed.queryParams.vid : null;
      const code = typeof parsed.queryParams?.code === 'string' ? parsed.queryParams.code : null;

      if (!vid || !code) {
        return;
      }

      confirmMagicLink(vid, code).catch((error: unknown) => {
        const message = error instanceof Error ? error.message : 'Не удалось подтвердить вход по ссылке.';
        Alert.alert('Ошибка', message);
      });
    }

    Linking.getInitialURL().then((url) => {
      if (url) {
        handleUrl(url);
      }
    });

    const subscription = Linking.addEventListener('url', ({ url }) => handleUrl(url));

    return () => {
      subscription.remove();
    };
  }, [confirmMagicLink]);

  const requestLoginCode = useCallback(async (email: string) => {
    setAuthBusy(true);

    try {
      const response = await clientPortalApi.startLogin({ email });

      setPendingAuth({
        verificationId: response.data.verification_id,
        email,
        mode: 'login',
      });
    } finally {
      setAuthBusy(false);
    }
  }, []);

  const requestMagicLink = useCallback(async (email: string) => {
    setAuthBusy(true);

    try {
      const response = await clientPortalApi.startMagicLink({ email });

      setPendingAuth({
        verificationId: response.data.verification_id,
        email,
        mode: 'magic-link',
      });
    } finally {
      setAuthBusy(false);
    }
  }, []);

  const confirmLoginCode = useCallback(async (code: string) => {
    if (!pendingAuth) {
      throw new Error('Сначала запросите код.');
    }

    setAuthBusy(true);

    try {
      const response = await clientPortalApi.verifyLogin({
        verification_id: pendingAuth.verificationId,
        code,
      });

      await applyVerifyResult(response.data, pendingAuth.email);
    } finally {
      setAuthBusy(false);
    }
  }, [applyVerifyResult, pendingAuth]);

  const selectMaster = useCallback(async (masterId: number) => {
    if (!pendingSelection) {
      throw new Error('Нет доступных данных для выбора мастера.');
    }

    setAuthBusy(true);

    try {
      const response = await clientPortalApi.verifyLogin({
        selection_token: pendingSelection.selectionToken,
        master_id: masterId,
      });

      await applyVerifyResult(response.data, pendingSelection.email);
    } finally {
      setAuthBusy(false);
    }
  }, [applyVerifyResult, pendingSelection]);

  const resetPendingAuth = useCallback(() => {
    setPendingAuth(null);
    setPendingSelection(null);
  }, []);

  const value = useMemo<ClientPortalContextValue>(
    () => ({
      bootstrapping,
      authBusy,
      token,
      master,
      home,
      session,
      pendingAuth,
      pendingSelection,
      requestLoginCode,
      requestMagicLink,
      confirmLoginCode,
      selectMaster,
      resetPendingAuth,
      refreshHomeFeed,
    }),
    [
      authBusy,
      bootstrapping,
      confirmLoginCode,
      home,
      master,
      pendingAuth,
      pendingSelection,
      refreshHomeFeed,
      requestLoginCode,
      requestMagicLink,
      resetPendingAuth,
      selectMaster,
      session,
      token,
    ],
  );

  return <ClientPortalContext.Provider value={value}>{children}</ClientPortalContext.Provider>;
}

export function useClientPortal() {
  const context = useContext(ClientPortalContext);

  if (!context) {
    throw new Error('useClientPortal must be used within ClientPortalProvider');
  }

  return context;
}
