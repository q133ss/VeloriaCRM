import { AppNavigator } from './src/app/AppNavigator';
import { AppProviders } from './src/app/AppProviders';

export default function App() {
  return (
    <AppProviders>
      <AppNavigator />
    </AppProviders>
  );
}
