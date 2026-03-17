import {
  ReactNode,
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';

import { sessionStorage } from '../../../shared/api/sessionStorage';
import { ClientServiceDto } from '../api/contracts';
import { clientPortalApi } from '../api/clientPortalApi';
import { buildMockHomeFeed } from '../mocks/mockClientPortal';
import {
  HomeFeed,
  MasterLanding,
  PendingAuth,
  SessionUser,
} from './types';

type ClientPortalContextValue = {
  bootstrapping: boolean;
  authBusy: boolean;
  master: MasterLanding | null;
  home: HomeFeed | null;
  session: SessionUser | null;
  pendingAuth: PendingAuth | null;
  requestLoginCode: (email: string) => Promise<void>;
  confirmLoginCode: (code: string) => Promise<void>;
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
  const [master, setMaster] = useState<MasterLanding | null>(null);
  const [home, setHome] = useState<HomeFeed | null>(null);
  const [session, setSession] = useState<SessionUser | null>(null);
  const [pendingAuth, setPendingAuth] = useState<PendingAuth | null>(null);

  const hydrateAuthorizedState = useCallback(async (token: string) => {
    const meResponse = await clientPortalApi.getMe(token);
    const client = meResponse.data.client;
    const nextSession = mapClientToSessionUser(client);

    setSession(nextSession);

    try {
      const servicesResponse = await clientPortalApi.getServices(token);
      const services = mapServicesToHomeCards(servicesResponse.data.services);

      setHome(buildMockHomeFeed(nextSession.name, services.length > 0 ? services : undefined));
    } catch {
      setHome(buildMockHomeFeed(nextSession.name));
    }
  }, []);

  useEffect(() => {
    let mounted = true;

    async function bootstrap() {
      try {
        const landing = await clientPortalApi.getMasterLanding();

        if (!mounted) {
          return;
        }

        setMaster(landing);

        const token = await sessionStorage.getClientToken();

        if (!token || !mounted) {
          return;
        }

        try {
          await hydrateAuthorizedState(token);
        } catch {
          await sessionStorage.clearClientToken();
          if (mounted) {
            setSession(null);
            setHome(null);
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
  }, [hydrateAuthorizedState]);

  const requestLoginCode = useCallback(async (email: string) => {
    if (!master) {
      throw new Error('Профиль мастера еще не загружен.');
    }

    setAuthBusy(true);

    try {
      const response = await clientPortalApi.startLogin({
        master_id: master.id,
        email,
      });

      setPendingAuth({
        verificationId: response.data.verification_id,
        email,
        mode: 'login',
      });
    } finally {
      setAuthBusy(false);
    }
  }, [master]);

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

      const { token, client } = response.data;
      await sessionStorage.setClientToken(token);
      await hydrateAuthorizedState(token);
      setSession(mapClientToSessionUser(client));
      setPendingAuth(null);
    } finally {
      setAuthBusy(false);
    }
  }, [hydrateAuthorizedState, pendingAuth]);

  const resetPendingAuth = useCallback(() => {
    setPendingAuth(null);
  }, []);

  const value = useMemo<ClientPortalContextValue>(
    () => ({
      bootstrapping,
      authBusy,
      master,
      home,
      session,
      pendingAuth,
      requestLoginCode,
      confirmLoginCode,
      resetPendingAuth,
    }),
    [authBusy, bootstrapping, confirmLoginCode, home, master, pendingAuth, requestLoginCode, resetPendingAuth, session],
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
