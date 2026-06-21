<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import {
    formatGregorianAsJalali,
    formatGregorianDate,
    parseGregorianDate,
    gregorianToJalali,
    jalaliToGregorian,
    jalaliMonthLength,
    getTodayGregorian,
    getTodayJalali,
    getJalaliFirstWeekday,
    toPersianDigits,
    JALALI_WEEK_DAYS,
    JALALI_MONTH_NAMES,
} from '@/Utils/jalaliDate';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'انتخاب تاریخ' },
    inputClass: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const PANEL_WIDTH = 288;
const PANEL_HEIGHT = 360;

const containerRef = ref(null);
const panelRef = ref(null);
const triggerRef = ref(null);
const isOpen = ref(false);
const viewYear = ref(1403);
const viewMonth = ref(1);
const panelStyle = ref({
    top: '0px',
    left: '0px',
});

const displayText = computed(() => {
    if (!props.modelValue) {
        return '';
    }

    return formatGregorianAsJalali(props.modelValue);
});

const yearOptions = computed(() => {
    const today = getTodayJalali();
    const years = [];

    for (let year = today.jy - 25; year <= today.jy + 5; year++) {
        years.push(year);
    }

    return years;
});

const selectedJalali = computed(() => {
    const parsed = parseGregorianDate(props.modelValue);
    if (!parsed) {
        return null;
    }

    const [jy, jm, jd] = gregorianToJalali(parsed.y, parsed.m, parsed.d);
    return { jy, jm, jd };
});

const calendarDays = computed(() => {
    const monthLength = jalaliMonthLength(viewYear.value, viewMonth.value);
    const firstWeekday = getJalaliFirstWeekday(viewYear.value, viewMonth.value);
    const cells = [];

    for (let i = 0; i < firstWeekday; i++) {
        cells.push(null);
    }

    for (let day = 1; day <= monthLength; day++) {
        cells.push(day);
    }

    return cells;
});

function syncViewFromValue() {
    if (props.modelValue) {
        const parsed = parseGregorianDate(props.modelValue);
        if (parsed) {
            const [jy, jm] = gregorianToJalali(parsed.y, parsed.m, parsed.d);
            viewYear.value = jy;
            viewMonth.value = jm;
            return;
        }
    }

    const today = getTodayJalali();
    viewYear.value = today.jy;
    viewMonth.value = today.jm;
}

watch(() => props.modelValue, syncViewFromValue, { immediate: true });

function updatePanelPosition() {
    if (!triggerRef.value) {
        return;
    }

    const rect = triggerRef.value.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const openUpward = spaceBelow < PANEL_HEIGHT && rect.top > PANEL_HEIGHT;

    let left = rect.right - PANEL_WIDTH;
    left = Math.max(8, Math.min(left, window.innerWidth - PANEL_WIDTH - 8));

    panelStyle.value = {
        top: openUpward
            ? `${rect.top - PANEL_HEIGHT - 8}px`
            : `${rect.bottom + 8}px`,
        left: `${left}px`,
    };
}

async function openCalendar() {
    syncViewFromValue();
    isOpen.value = true;
    await nextTick();
    updatePanelPosition();
}

function toggleCalendar(event) {
    event.stopPropagation();

    if (isOpen.value) {
        closeCalendar();
        return;
    }

    openCalendar();
}

function closeCalendar() {
    isOpen.value = false;
}

function selectDay(day) {
    const [gy, gm, gd] = jalaliToGregorian(viewYear.value, viewMonth.value, day);
    emit('update:modelValue', formatGregorianDate(gy, gm, gd));
    closeCalendar();
}

function selectToday() {
    emit('update:modelValue', getTodayGregorian());
    closeCalendar();
}

function clearDate(event) {
    event.stopPropagation();
    emit('update:modelValue', '');
    closeCalendar();
}

function prevMonth() {
    if (viewMonth.value === 1) {
        viewMonth.value = 12;
        viewYear.value -= 1;
        return;
    }

    viewMonth.value -= 1;
}

function nextMonth() {
    if (viewMonth.value === 12) {
        viewMonth.value = 1;
        viewYear.value += 1;
        return;
    }

    viewMonth.value += 1;
}

function isSelected(day) {
    if (!day || !selectedJalali.value) {
        return false;
    }

    const selected = selectedJalali.value;
    return selected.jy === viewYear.value && selected.jm === viewMonth.value && selected.jd === day;
}

function isToday(day) {
    if (!day) {
        return false;
    }

    const today = getTodayJalali();
    return today.jy === viewYear.value && today.jm === viewMonth.value && today.jd === day;
}

function onDocumentClick(event) {
    const inTrigger = containerRef.value?.contains(event.target);
    const inPanel = panelRef.value?.contains(event.target);

    if (!inTrigger && !inPanel) {
        closeCalendar();
    }
}

function onViewportChange() {
    if (isOpen.value) {
        updatePanelPosition();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('scroll', onViewportChange, true);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
});
</script>

<template>
    <div ref="containerRef" class="relative">
        <div ref="triggerRef" class="flex items-center gap-2">
            <button
                type="button"
                @click="toggleCalendar"
                class="flex-1 flex items-center justify-between text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2 bg-white text-right hover:border-green-500 focus:outline-none focus:border-green-500"
                :class="inputClass"
            >
                <span :class="displayText ? 'text-gray-800' : 'text-gray-400'">
                    {{ displayText || placeholder }}
                </span>
                <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </button>

            <button
                v-if="modelValue"
                type="button"
                @click="clearDate"
                class="px-2 py-2 text-sm text-gray-500 border border-gray-300 rounded-md hover:bg-gray-50"
                title="پاک کردن تاریخ"
            >
                ×
            </button>
        </div>

        <Teleport to="body">
            <div
                v-if="isOpen"
                ref="panelRef"
                class="fixed z-[9999] w-72 rounded-lg border border-gray-200 bg-white shadow-2xl p-3"
                dir="rtl"
                :style="panelStyle"
                @click.stop
            >
                <div class="flex items-center justify-between gap-2 mb-3">
                    <button
                        type="button"
                        @click="nextMonth"
                        class="p-1.5 rounded hover:bg-gray-100 text-gray-600 shrink-0"
                        aria-label="ماه بعد"
                    >
                        ‹
                    </button>

                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <select
                            v-model.number="viewMonth"
                            class="flex-1 min-w-0 text-xs sm:text-sm border border-gray-300 rounded-md px-2 py-1.5 bg-white focus:outline-none focus:border-green-500"
                        >
                            <option
                                v-for="(monthName, index) in JALALI_MONTH_NAMES"
                                :key="monthName"
                                :value="index + 1"
                            >
                                {{ monthName }}
                            </option>
                        </select>

                        <select
                            v-model.number="viewYear"
                            class="w-24 text-xs sm:text-sm border border-gray-300 rounded-md px-2 py-1.5 bg-white focus:outline-none focus:border-green-500"
                        >
                            <option v-for="year in yearOptions" :key="year" :value="year">
                                {{ toPersianDigits(year) }}
                            </option>
                        </select>
                    </div>

                    <button
                        type="button"
                        @click="prevMonth"
                        class="p-1.5 rounded hover:bg-gray-100 text-gray-600 shrink-0"
                        aria-label="ماه قبل"
                    >
                        ›
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-1 mb-2">
                    <div
                        v-for="weekDay in JALALI_WEEK_DAYS"
                        :key="weekDay"
                        class="text-center text-xs font-medium text-gray-500 py-1"
                    >
                        {{ weekDay }}
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="(day, index) in calendarDays"
                        :key="`${viewYear}-${viewMonth}-${index}`"
                        type="button"
                        :disabled="!day"
                        @click="day && selectDay(day)"
                        class="h-8 text-sm rounded-md transition-colors"
                        :class="[
                            !day ? 'invisible' : '',
                            isSelected(day)
                                ? 'bg-green-600 text-white font-semibold'
                                : isToday(day)
                                    ? 'bg-green-50 text-green-700 font-medium hover:bg-green-100'
                                    : 'text-gray-700 hover:bg-gray-100',
                        ]"
                    >
                        {{ day ? toPersianDigits(day) : '' }}
                    </button>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between">
                    <button
                        type="button"
                        @click="selectToday"
                        class="text-xs text-green-700 hover:text-green-800 font-medium"
                    >
                        امروز
                    </button>
                    <button
                        type="button"
                        @click="clearDate"
                        class="text-xs text-gray-500 hover:text-gray-700"
                    >
                        پاک کردن
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
