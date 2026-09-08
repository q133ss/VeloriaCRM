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

export type ApiMasterBranding = {
  app_display_name: string | null;
  primary_color: string | null;
  secondary_color: string | null;
  logo_url: string | null;
};

export type ApiMaster = {
  id: number;
  name: string | null;
  avatar_url?: string | null;
  has_custom_branding?: boolean;
  branding?: ApiMasterBranding | null;
};

export type VerifyAuthPayload = {
  client: ApiClient;
  master: ApiMaster;
  token: string;
};

export type MasterSelectionChoiceDto = {
  master_id: number;
  master_name: string;
  client_id: number;
};

export type MasterSelectionRequiredPayload = {
  requires_master_selection: true;
  selection_token: string;
  expires_in: number;
  masters: MasterSelectionChoiceDto[];
};

export type VerifyLoginResponseData = VerifyAuthPayload | MasterSelectionRequiredPayload;

export function isMasterSelectionRequired(
  data: VerifyLoginResponseData,
): data is MasterSelectionRequiredPayload {
  return 'requires_master_selection' in data && data.requires_master_selection === true;
}

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

export type AppointmentListItemDto = {
  id: number;
  status: string;
  service_label: string;
  date: string | null;
  time: string | null;
  is_upcoming: boolean;
};

export type StartLoginBody = {
  email: string;
};

export type VerifyCodeBody = {
  verification_id: string;
  code: string;
};

export type SelectMasterBody = {
  selection_token: string;
  master_id: number;
};

export type VerifyLoginBody = VerifyCodeBody | SelectMasterBody;

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
