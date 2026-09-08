import { NavigationContainer, Theme as NavigationTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { StatusBar } from 'expo-status-bar';

import { AppointmentsScreen } from '../features/client-portal/screens/AppointmentsScreen';
import { AuthScreen } from '../features/client-portal/screens/AuthScreen';
import { BookingConfirmationScreen } from '../features/client-portal/screens/BookingConfirmationScreen';
import { BookingScreen } from '../features/client-portal/screens/BookingScreen';
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

function screenOptions(theme: AppTheme) {
  return {
    headerShown: false,
    contentStyle: {
      backgroundColor: theme.colors.appBackground,
    },
    animation: 'slide_from_right' as const,
  };
}

function GuestNavigator({ theme }: { theme: AppTheme }) {
  return (
    <Stack.Navigator initialRouteName="Auth" screenOptions={screenOptions(theme)}>
      <Stack.Screen name="Auth" component={AuthScreen} />
    </Stack.Navigator>
  );
}

function AuthedNavigator({ theme }: { theme: AppTheme }) {
  return (
    <Stack.Navigator initialRouteName="Home" screenOptions={screenOptions(theme)}>
      <Stack.Screen name="Home" component={HomeScreen} />
      <Stack.Screen name="Booking" component={BookingScreen} />
      <Stack.Screen name="BookingConfirmation" component={BookingConfirmationScreen} />
      <Stack.Screen name="Appointments" component={AppointmentsScreen} />
    </Stack.Navigator>
  );
}

export function AppNavigator() {
  const theme = useAppTheme();
  const { bootstrapping, session } = useClientPortal();

  if (bootstrapping) {
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
      {session ? <AuthedNavigator key="authorized" theme={theme} /> : <GuestNavigator key="guest" theme={theme} />}
    </NavigationContainer>
  );
}
