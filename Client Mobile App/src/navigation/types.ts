export type BookingConfirmationParams = {
  serviceId: number | null;
  serviceLabel: string;
  date: string;
  time: string;
};

export type RootStackParamList = {
  Auth: undefined;
  Home: undefined;
  Booking: { serviceId?: number } | undefined;
  BookingConfirmation: BookingConfirmationParams;
  Appointments: undefined;
  News: undefined;
  NewsDetail: undefined;
  Chat: undefined;
  Reviews: undefined;
  Deposit: undefined;
  Referral: undefined;
  Notifications: undefined;
};

export type GuestStackParamList = Pick<RootStackParamList, 'Auth'>;

export type AuthedStackParamList = Omit<RootStackParamList, 'Auth'>;
