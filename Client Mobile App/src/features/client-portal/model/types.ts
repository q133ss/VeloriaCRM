export type AuthMode = 'login' | 'magic-link';

export type ClientServiceCard = {
  id: string;
  title: string;
  duration: string;
  price: string;
  badge?: string;
};

export type AuthMaster = {
  id: number;
  name: string;
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
