export type AuthMode = 'login' | 'magic-link';

export type ClientServiceCard = {
  id: string;
  title: string;
  duration: string;
  price: string;
  badge?: string;
};

export type MasterBranding = {
  appDisplayName: string | null;
  primaryColor: string | null;
  secondaryColor: string | null;
  logoUrl: string | null;
};

export type AuthMaster = {
  id: number;
  name: string;
  avatarUrl: string | null;
  // Server-computed from the master's plan tier (App\Models\User::hasProAccess()) —
  // a Lite master's `branding` is always null here even if something is stored,
  // so this app never has to re-derive the plan gate on its own.
  hasCustomBranding: boolean;
  branding: MasterBranding | null;
};

export type UpcomingAppointmentSummary = {
  id: number;
  serviceLabel: string;
  dateLabel: string;
  timeLabel: string;
  statusLabel: string;
};

export type NewsPostSummary = {
  id: number;
  title: string;
  excerpt: string;
  body: string;
  imageUrl: string | null;
  date: string;
};

export type HomeFeed = {
  clientName: string;
  // null once a real appointment fetch comes back empty — there is no fake
  // placeholder appointment to fall back to, unlike `services` below.
  nextAppointment: UpcomingAppointmentSummary | null;
  services: ClientServiceCard[];
  updates: NewsPostSummary[];
};

export type BookingCategoryOption = {
  id: number;
  name: string;
};

export type BookingServiceOption = {
  id: number;
  categoryId: number | null;
  name: string;
  durationLabel: string;
  priceLabel: string;
  durationMin: number | null;
};

export type PendingAuth = {
  verificationId: string;
  email: string;
  mode: AuthMode;
};

export type MasterChoice = {
  masterId: number;
  masterName: string;
  clientId: number;
};

export type PendingSelection = {
  selectionToken: string;
  email: string;
  masters: MasterChoice[];
};

export type SessionUser = {
  id: number;
  masterId: number;
  name: string;
  email: string | null;
  phone: string | null;
};
