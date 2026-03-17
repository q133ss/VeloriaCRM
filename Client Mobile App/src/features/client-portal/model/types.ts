export type AuthMode = 'login';

export type ServicePreviewCard = {
  id: string;
  title: string;
  duration: string;
  price: string;
  badge?: string;
};

export type ClientServiceCard = {
  id: string;
  title: string;
  duration: string;
  price: string;
  badge?: string;
};

export type MasterLanding = {
  id: number;
  slug: string;
  studioName: string;
  masterName: string;
  cityLabel: string;
  focusLabel: string;
  heroNote: string;
  trustNote: string;
  accentTag: string;
  servicePreview: ServicePreviewCard[];
};

export type HomeFeed = {
  clientName: string;
  nextAppointment: {
    service: string;
    dateLabel: string;
    timeLabel: string;
    status: string;
  };
  services: ClientServiceCard[];
  updates: Array<{
    id: string;
    title: string;
    excerpt: string;
    date: string;
  }>;
};

export type AuthRequest = {
  mode: AuthMode;
  email: string;
};

export type PendingAuth = {
  verificationId: string;
  email: string;
  mode: AuthMode;
};

export type SessionUser = {
  id: number;
  masterId: number;
  name: string;
  email: string | null;
  phone: string | null;
};
