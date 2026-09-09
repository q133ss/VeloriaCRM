import { httpRequest } from '../../../shared/api/http';
import {
  ApiClient,
  ApiEnvelope,
  ApiMaster,
  AppointmentDto,
  AppointmentListItemDto,
  ClientNotificationDto,
  ClientServiceDto,
  ClientSlotsDto,
  CreateAppointmentBody,
  CreateWaitlistBody,
  MasterPostDto,
  OtpStartPayload,
  PromotionDto,
  RegisterDeviceTokenBody,
  ServiceCategoryDto,
  StartLoginBody,
  VerifyLoginBody,
  VerifyLoginResponseData,
  WaitlistEntryPayload,
  GetServicesQuery,
  GetSlotsQuery,
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
    return httpRequest<ApiEnvelope<{ client: ApiClient; master: ApiMaster }>>({
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

  getAppointments(token: string) {
    return httpRequest<ApiEnvelope<{ appointments: AppointmentListItemDto[] }>>({
      path: '/client/appointments',
      token,
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

  getPosts(token: string) {
    return httpRequest<ApiEnvelope<{ posts: MasterPostDto[] }>>({
      path: '/client/posts',
      token,
    });
  },

  getPromotions(token: string) {
    return httpRequest<ApiEnvelope<{ promotions: PromotionDto[] }>>({
      path: '/client/promotions',
      token,
    });
  },

  registerDeviceToken(token: string, body: RegisterDeviceTokenBody) {
    return httpRequest<{ message: string }>({
      method: 'POST',
      path: '/client/device-token',
      token,
      body,
    });
  },

  getNotifications(token: string) {
    return httpRequest<ApiEnvelope<{ notifications: ClientNotificationDto[] }>>({
      path: '/client/notifications',
      token,
    });
  },

  markNotificationsRead(token: string, ids?: number[]) {
    return httpRequest<{ updated: number }>({
      method: 'POST',
      path: '/client/notifications/mark-as-read',
      token,
      body: ids ? { ids } : undefined,
    });
  },
};
