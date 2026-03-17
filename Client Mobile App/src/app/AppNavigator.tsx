import { NavigationContainer, Theme as NavigationTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { StatusBar } from 'expo-status-bar';

import { AuthScreen } from '../features/client-portal/screens/AuthScreen';
import { HomeScreen } from '../features/client-portal/screens/HomeScreen';
import { SplashScreen } from '../features/client-portal/screens/SplashScreen';
import { useClientPortal } from '../features/client-portal/model/clientPortalContext';
import { RootStackParamList } from '../navigation/types';
import { AppTheme, useAppTheme } from '../theme/theme';

const Stack = createNativeStackNavigator<RootStackParamList>();

function buildNavigationTheme(theme: AppTheme): NavigationTheme {
  return {
    dark: theme.isDark,
    colors: {
      primary: theme.colors.primary,
      background: theme.colors.appBackground,
      card: theme.colors.surface,
      text: theme.colors.textPrimary,
      border: theme.colors.borderSoft,
      notification: theme.colors.accent,
    },
    fonts: {
      regular: {
        fontFamily: 'System',
        fontWeight: '400',
      },
      medium: {
        fontFamily: 'System',
        fontWeight: '500',
      },
      bold: {
        fontFamily: 'System',
        fontWeight: '700',
      },
      heavy: {
        fontFamily: 'System',
        fontWeight: '800',
      },
    },
  };
}

export function AppNavigator() {
  const theme = useAppTheme();
  const { bootstrapping, master, session } = useClientPortal();

  if (bootstrapping || !master) {
    return (
      <>
        <StatusBar style={theme.isDark ? 'light' : 'dark'} />
        <SplashScreen />
      </>
    );
  }

  return (
    <NavigationContainer theme={buildNavigationTheme(theme)}>
      <StatusBar style={theme.isDark ? 'light' : 'dark'} />
      <Stack.Navigator
        key={session ? 'authorized' : 'guest'}
        initialRouteName={session ? 'Home' : 'Auth'}
        screenOptions={{
          headerShown: false,
          contentStyle: {
            backgroundColor: theme.colors.appBackground,
          },
          animation: 'slide_from_right',
        }}
      >
        <Stack.Screen name="Auth" component={AuthScreen} />
        <Stack.Screen name="Home" component={HomeScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
