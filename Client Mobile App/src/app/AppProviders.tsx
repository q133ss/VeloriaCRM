import { ReactNode } from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { ClientPortalProvider } from '../features/client-portal/model/clientPortalContext';

type AppProvidersProps = {
  children: ReactNode;
};

export function AppProviders({ children }: AppProvidersProps) {
  return (
    <SafeAreaProvider>
      <ClientPortalProvider>{children}</ClientPortalProvider>
    </SafeAreaProvider>
  );
}
