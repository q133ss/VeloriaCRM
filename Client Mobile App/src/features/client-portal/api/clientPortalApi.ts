import { httpRequest } from '../../../shared/api/http';
import {
  ApiClient,
  ApiEnvelope,
  AppointmentDto,
  ClientServiceDto,
  ClientSlotsDto,
  CreateAppointmentBody,
  CreateWaitlistBody,
  GetServicesQuery,
  GetSlotsQuery,
  OtpStartPayload,
  ServiceCategoryDto,
  StartLoginBody,
  VerifyLoginBody,
  VerifyLoginResponseData,
  WaitlistEntryPayload,
} from './contracts';

export const clientPortalApi = {
  startLogin(body: StartLoginBody) {
    return httpRequest<ApiEnvelope<OtpStartPayload>>({
      method: 'POST',
      path: '/client/login',
      body,
    });
  },

  startMagicLink(body: StartLoginBody) {
    return httpRequest<ApiEnvelope<OtpStartPayload>>({
      method: 'POST',
      path: '/client/login/magic-link',
      body,
    });
  },

  verifyLogin(body: VerifyLoginBody) {
    return httpRequest<ApiEnvelope<VerifyLoginResponseData>>({
      method: 'POST',
      path: '/client/login/verify',
      body,
    });
  },

  getMe(token: string) {
    return httpRequest<ApiEnvelope<{ client: ApiClient }>>({
      path: '/client/me',
      token,
    });
  },

  getServiceCategories(token: string) {
    return httpRequest<ApiEnvelope<{ categories: ServiceCategoryDto[] }>>({
      path: '/client/service-categories',
      token,
    });
  },

  getServices(token: string, query?: GetServicesQuery) {
    return httpRequest<ApiEnvelope<{ services: ClientServiceDto[] }>>({
      path: '/client/services',
      token,
      query,
    });
  },

  getSlots(token: string, query: GetSlotsQuery) {
    return httpRequest<ApiEnvelope<ClientSlotsDto>>({
      path: '/client/slots',
      token,
      query,
    });
  },

  getServiceSlots(token: string, serviceId: number, query: GetSlotsQuery) {
    return httpRequest<ApiEnvelope<ClientSlotsDto>>({
      path: `/client/services/${serviceId}/slots`,
      token,
      query,
    });
  },

  createAppointment(token: string, body: CreateAppointmentBody) {
    return httpRequest<ApiEnvelope<{ appointment: AppointmentDto }>>({
      method: 'POST',
      path: '/client/appointments',
      token,
      body,
    });
  },

  createWaitlist(token: string, body: CreateWaitlistBody) {
    return httpRequest<ApiEnvelope<WaitlistEntryPayload>>({
      method: 'POST',
      path: '/client/waitlist',
      token,
      body,
    });
  },
};
