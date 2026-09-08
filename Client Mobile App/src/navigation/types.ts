export type RootStackParamList = {
  Auth: undefined;
  Home: undefined;
  Booking: undefined;
  BookingConfirmation: undefined;
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
