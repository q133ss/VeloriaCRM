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
import { ClientServiceDto, VerifyAuthPayload, VerifyLoginResponseData, isMasterSelectionRequired } from '../api/contracts';
import { clientPortalApi } from '../api/clientPortalApi';
import { buildMockHomeFeed } from '../mocks/mockClientPortal';
import {
  AuthMaster,
  HomeFeed,
  PendingAuth,
  PendingSelection,
  SessionUser,
} from './types';

type ClientPortalContextValue = {
  bootstrapping: boolean;
  authBusy: boolean;
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

function mapMaster(master: { id: number; name: string | null }): AuthMaster {
  return {
    id: master.id,
    name: master.name?.trim() || 'Мастер',
  };
}

function mapServicesToHomeCards(services: ClientServiceDto[]) {
  return services.slice(0, 4).map((service) => ({
    id: String(service.id),
    title: service.name,
    duration: service.duration_min ? `${service.duration_min} мин` : 'Длительность уточняется',
    price: service.base_price !== null ? `от ${service.base_price} ₽` : 'Цена уточняется',
  }));
}

export function ClientPortalProvider({ children }: ClientPortalProviderProps) {
  const [bootstrapping, setBootstrapping] = useState(true);
  const [authBusy, setAuthBusy] = useState(false);
  const [master, setMaster] = useState<AuthMaster | null>(null);
  const [home, setHome] = useState<HomeFeed | null>(null);
  const [session, setSession] = useState<SessionUser | null>(null);
  const [pendingAuth, setPendingAuth] = useState<PendingAuth | null>(null);
  const [pendingSelection, setPendingSelection] = useState<PendingSelection | null>(null);

  const loadHomeFeed = useCallback(async (token: string, clientName: string) => {
    try {
      const servicesResponse = await clientPortalApi.getServices(token);
      const services = mapServicesToHomeCards(servicesResponse.data.services);

      setHome(buildMockHomeFeed(clientName, services.length > 0 ? services : undefined));
    } catch {
      setHome(buildMockHomeFeed(clientName));
    }
  }, []);

  const hydrateFromToken = useCallback(async (token: string) => {
    const meResponse = await clientPortalApi.getMe(token);
    const nextSession = mapClientToSessionUser(meResponse.data.client);

    setSession(nextSession);

    const storedMaster = await sessionStorage.getClientMaster();
    if (storedMaster) {
      setMaster(storedMaster);
    }

    await loadHomeFeed(token, nextSession.name);
  }, [loadHomeFeed]);

  const applyAuthPayload = useCallback(async (payload: VerifyAuthPayload) => {
    const nextSession = mapClientToSessionUser(payload.client);
    const nextMaster = mapMaster(payload.master);

    await sessionStorage.setClientToken(payload.token);
    await sessionStorage.setClientMaster(nextMaster);

    setMaster(nextMaster);
    setSession(nextSession);
    setPendingAuth(null);
    setPendingSelection(null);

    await loadHomeFeed(payload.token, nextSession.name);
  }, [loadHomeFeed]);

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
        const token = await sessionStorage.getClientToken();

        if (!token || !mounted) {
          return;
        }

        try {
          await hydrateFromToken(token);
        } catch {
          await sessionStorage.clearClientToken();
          await sessionStorage.clearClientMaster();
          if (mounted) {
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

      // A custom-scheme URL like `veloriaclient://auth/verify` is parsed via WHATWG URL
      // rules: "auth" lands in `hostname` (authority), and "verify" is what's left of `path`.
      if (parsed.hostname !== 'auth' || parsed.path !== 'verify') {
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
    }),
    [
      authBusy,
      bootstrapping,
      confirmLoginCode,
      home,
      master,
      pendingAuth,
      pendingSelection,
      requestLoginCode,
      requestMagicLink,
      resetPendingAuth,
      selectMaster,
      session,
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
