export type ApiClient = {
  id: number;
  user_id: number;
  name: string | null;
  email: string | null;
  phone: string | null;
};

export type ApiEnvelope<T> = {
  data: T;
  message?: string;
};

export type OtpStartPayload = {
  verification_id: string;
  expires_in: number;
};

export type VerifyAuthPayload = {
  client: ApiClient;
  token: string;
};

export type ServiceCategoryDto = {
  id: number;
  name: string;
};

export type ClientServiceDto = {
  id: number;
  category_id: number | null;
  name: string;
  base_price: number | null;
  duration_min: number | null;
};

export type ClientSlotsDto = {
  date: string;
  service_id: number | null;
  duration_min?: number;
  slots: string[];
};

export type AppointmentDto = {
  id: number;
  user_id: number;
  client_id: number;
  service_ids: number[];
  starts_at: string;
  ends_at: string;
  status: string;
  meta?: Record<string, unknown>;
};

export type WaitlistEntryPayload = {
  waitlist_entry_id: number;
};

export type StartRegisterBody = {
  master_id: number;
  name: string;
  email: string;
  phone: string;
};

export type StartLoginBody = {
  master_id: number;
  email: string;
};

export type VerifyCodeBody = {
  verification_id: string;
  code: string;
};

export type GetServicesQuery = {
  category_id?: number;
  search?: string;
};

export type GetSlotsQuery = {
  date: string;
};

export type CreateAppointmentBody = {
  service_id?: number | null;
  date: string;
  time: string;
  note?: string;
};

export type PreferredTimeWindow = {
  start: string;
  end: string;
};

export type CreateWaitlistBody = {
  service_id: number;
  preferred_dates: string[];
  preferred_time_windows?: PreferredTimeWindow[];
  flexibility_days?: number;
  notes?: string;
};
