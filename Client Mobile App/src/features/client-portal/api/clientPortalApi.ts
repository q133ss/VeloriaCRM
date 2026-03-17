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
  StartRegisterBody,
  VerifyAuthPayload,
  VerifyCodeBody,
  WaitlistEntryPayload,
} from './contracts';
import { masterLandingMock } from '../mocks/mockClientPortal';

export const clientPortalApi = {
  async getMasterLanding() {
    return masterLandingMock;
  },

  startRegister(body: StartRegisterBody) {
    return httpRequest<ApiEnvelope<OtpStartPayload>>({
      method: 'POST',
      path: '/client/register',
      body,
    });
  },

  verifyRegister(body: VerifyCodeBody) {
    return httpRequest<ApiEnvelope<VerifyAuthPayload>>({
      method: 'POST',
      path: '/client/register/verify',
      body,
    });
  },

  startLogin(body: StartLoginBody) {
    return httpRequest<ApiEnvelope<OtpStartPayload>>({
      method: 'POST',
      path: '/client/login',
      body,
    });
  },

  verifyLogin(body: VerifyCodeBody) {
    return httpRequest<ApiEnvelope<VerifyAuthPayload>>({
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
