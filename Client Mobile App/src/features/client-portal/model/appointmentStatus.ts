import { AppointmentListItemDto } from '../api/contracts';

/** 2500 -> "2 500 ₽". Done by hand: Intl is not guaranteed on every Android build. */
export function formatRubles(amount: number): string {
  return `${String(Math.round(amount)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} ₽`;
}

/** Whole minutes until the time is up (rounded up), or null when there is no deadline. */
export function minutesLeft(expiresAt: string | null | undefined, now: number = Date.now()): number | null {
  if (!expiresAt) {
    return null;
  }

  const deadline = Date.parse(expiresAt);

  if (Number.isNaN(deadline)) {
    return null;
  }

  return Math.max(0, Math.ceil((deadline - now) / 60000));
}

/** A booking the master is holding until the prepayment arrives. */
export function isAwaitingPayment(item: Pick<AppointmentListItemDto, 'payment'>): boolean {
  return item.payment?.state === 'awaiting';
}

/** Awaiting payment AND there is still a link worth opening (it disappears when the time is up). */
export function canPayNow(item: Pick<AppointmentListItemDto, 'payment'>, now: number = Date.now()): boolean {
  const payment = item.payment;

  return payment?.state === 'awaiting'
    && Boolean(payment.confirmation_url)
    && (minutesLeft(payment.expires_at, now) ?? 1) > 0;
}

export function appointmentStatusLabel(item: Pick<AppointmentListItemDto, 'status' | 'payment'>): string {
  if (isAwaitingPayment(item)) {
    return 'Ждёт оплаты';
  }

  switch (item.status) {
    case 'scheduled':
    case 'confirmed':
      return 'Подтверждено';
    case 'completed':
      return 'Завершена';
    case 'cancelled':
      return 'Отменена';
    default:
      return item.status;
  }
}
