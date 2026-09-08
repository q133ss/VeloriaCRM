const RU_MONTHS_GENITIVE = [
  'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
  'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
];

const RU_WEEKDAYS_SHORT = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];

// All local dates in this app are already the master's own calendar day
// (BookingController quotes slots/appointments in the master's timezone), so
// parsing "YYYY-MM-DD" as calendar fields — never through `new Date(iso)`,
// which reads it as UTC midnight and can roll to the wrong day locally — is
// what keeps the label matching the date the client actually picked.
function parseIsoDateParts(isoDate: string): { year: number; month: number; day: number } | null {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(isoDate);

  if (!match) {
    return null;
  }

  return { year: Number(match[1]), month: Number(match[2]), day: Number(match[3]) };
}

export function formatDateLabel(isoDate: string): string {
  const parts = parseIsoDateParts(isoDate);

  if (!parts) {
    return isoDate;
  }

  return `${parts.day} ${RU_MONTHS_GENITIVE[parts.month - 1]}`;
}

export function formatWeekdayShort(isoDate: string): string {
  const parts = parseIsoDateParts(isoDate);

  if (!parts) {
    return '';
  }

  return RU_WEEKDAYS_SHORT[new Date(parts.year, parts.month - 1, parts.day).getDay()];
}

export function toIsoDate(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

export function addDays(date: Date, days: number): Date {
  const copy = new Date(date);
  copy.setDate(copy.getDate() + days);

  return copy;
}
