export const JALALI_MONTH_NAMES = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
];

export const JALALI_WEEK_DAYS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';

export function toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => PERSIAN_DIGITS[Number(digit)]);
}

export function isJalaliLeap(jy) {
    const r = jy % 33;
    return [1, 5, 9, 13, 17, 22, 26, 30].includes(r);
}

export function jalaliMonthLength(jy, jm) {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    return isJalaliLeap(jy) ? 30 : 29;
}

export function gregorianToJalali(gy, gm, gd) {
    const gDaysInMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    const gy2 = gm > 2 ? gy + 1 : gy;
    let days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4)
        - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + gDaysInMonth[gm - 1];

    let jy = -1595 + (33 * Math.floor(days / 12053));
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;

    if (days > 365) {
        jy += Math.floor((days - 1) / 365);
        days = (days - 1) % 365;
    }

    let jm;
    let jd;

    if (days < 186) {
        jm = 1 + Math.floor(days / 31);
        jd = 1 + (days % 31);
    } else {
        jm = 7 + Math.floor((days - 186) / 30);
        jd = 1 + ((days - 186) % 30);
    }

    return [jy, jm, jd];
}

export function jalaliToGregorian(jy, jm, jd) {
    let gy;
    jy += 1595;
    let days = -355668 + (365 * jy) + Math.floor(jy / 33) * 8
        + Math.floor(((jy % 33) + 3) / 4) + jd
        + (jm < 7 ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);

    gy = 400 * Math.floor(days / 146097);
    days %= 146097;

    if (days > 36524) {
        gy += 100 * Math.floor(--days / 36524);
        days %= 36524;
        if (days >= 365) {
            days++;
        }
    }

    gy += 4 * Math.floor(days / 1461);
    days %= 1461;

    if (days > 365) {
        gy += Math.floor((days - 1) / 365);
        days = (days - 1) % 365;
    }

    let gd = days + 1;
    const monthLengths = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    if ((gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0) {
        monthLengths[2] = 29;
    }

    let gm = 0;
    while (gm < 13 && gd > monthLengths[gm]) {
        gd -= monthLengths[gm];
        gm++;
    }

    return [gy, gm, gd];
}

export function parseGregorianDate(dateStr) {
    if (!dateStr) {
        return null;
    }

    const match = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!match) {
        return null;
    }

    return {
        y: Number(match[1]),
        m: Number(match[2]),
        d: Number(match[3]),
    };
}

export function formatGregorianDate(y, m, d) {
    return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
}

export function formatGregorianAsJalali(dateStr) {
    const parsed = parseGregorianDate(dateStr);
    if (!parsed) {
        return '';
    }

    const [jy, jm, jd] = gregorianToJalali(parsed.y, parsed.m, parsed.d);
    const formatted = `${jy}/${String(jm).padStart(2, '0')}/${String(jd).padStart(2, '0')}`;

    return toPersianDigits(formatted);
}

export function getTodayGregorian() {
    const now = new Date();
    return formatGregorianDate(now.getFullYear(), now.getMonth() + 1, now.getDate());
}

export function getTodayJalali() {
    const now = new Date();
    const [jy, jm, jd] = gregorianToJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
    return { jy, jm, jd };
}

export function jalaliMonthName(jm) {
    return JALALI_MONTH_NAMES[jm - 1] || '';
}

export function getJalaliFirstWeekday(jy, jm) {
    const [gy, gm, gd] = jalaliToGregorian(jy, jm, 1);
    return (new Date(gy, gm - 1, gd).getDay() + 1) % 7;
}
