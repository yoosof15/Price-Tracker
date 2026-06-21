<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ShamsiDateInput from '@/Components/ShamsiDateInput.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { formatGregorianAsJalali } from '@/Utils/jalaliDate';

const props = defineProps({
    prices: { type: Array, default: () => [] },
    pricesMeta: {
        type: Object,
        default: () => ({
            current_page: 1,
            per_page: 5,
            total: 0,
            has_more: false,
        }),
    },
    selectedDate: { type: String, default: () => new Date().toISOString().split('T')[0] },
    products: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] },
});

const products = computed(() => props.products);
const locations = computed(() => props.locations);

const priceList = ref([...props.prices]);
const pricesMeta = ref({ ...props.pricesMeta });
const currentPage = ref(props.pricesMeta?.current_page ?? 1);
const isLoadingPrices = ref(false);
const isLoadingMore = ref(false);

const newEntryDate = ref(props.selectedDate);
const statusMessage = ref({ type: '', text: '' });
const formStatus = ref({ type: '', text: '' });

const newForm = ref({
    product_id: '',
    location_id: '',
    min_price: '',
    max_price: '',
});

const editingPrice = ref(null);
const editForm = ref({
    id: null,
    product_id: '',
    location_id: '',
    min_price: '',
    max_price: '',
    date: '',
});
const editStatus = ref({ type: '', text: '' });

const filters = ref({
    search: '',
    fromDate: '',
    toDate: '',
    minPrice: null,
    maxPrice: null,
});

const resetFilters = () => {
    filters.value = {
        search: '',
        fromDate: '',
        toDate: '',
        minPrice: null,
        maxPrice: null,
    };

    fetchPrices(1, false);
};

const formatDate = (dateStr) => formatGregorianAsJalali(dateStr);

const dateRangeError = computed(() => {
    const { fromDate, toDate } = filters.value;

    if (!fromDate || !toDate) {
        return '';
    }

    if (fromDate > toDate) {
        return `بازه تاریخ نامعتبر است: «${formatDate(fromDate)}» نمی‌تواند بعد از «${formatDate(toDate)}» باشد. تاریخ پایان باید جلوتر از تاریخ شروع باشد.`;
    }

    return '';
});

const priceRangeError = computed(() => {
    const { minPrice, maxPrice } = filters.value;

    if (minPrice === null || maxPrice === null || minPrice === '' || maxPrice === '') {
        return '';
    }

    if (Number(maxPrice) < Number(minPrice)) {
        return 'حداکثر قیمت فیلتر نمی‌تواند کمتر از حداقل قیمت باشد.';
    }

    return '';
});

const formatNumber = (num) => {
    if (num === null || num === undefined || num === '') return '0';
    return Number(num).toLocaleString('fa-IR');
};

const hasMorePrices = computed(() => pricesMeta.value.has_more);

const hasActiveFilters = computed(() =>
    !!filters.value.search ||
    !!filters.value.fromDate ||
    !!filters.value.toDate ||
    filters.value.minPrice !== null ||
    filters.value.maxPrice !== null
);

const buildFetchParams = (page) => ({
    page,
    search: filters.value.search || undefined,
    from_date: filters.value.fromDate || undefined,
    to_date: filters.value.toDate || undefined,
    min_price: filters.value.minPrice ?? undefined,
    max_price: filters.value.maxPrice ?? undefined,
});

const fetchPrices = async (page = 1, append = false) => {
    if (dateRangeError.value || priceRangeError.value) {
        return;
    }

    if (append) {
        isLoadingMore.value = true;
    } else {
        isLoadingPrices.value = true;
    }

    try {
        const { data } = await axios.get('/admin/price-manager/prices', {
            params: buildFetchParams(page),
        });

        priceList.value = append
            ? [...priceList.value, ...data.data]
            : data.data;

        pricesMeta.value = data.meta;
        currentPage.value = page;
    } catch (error) {
        console.error('خطا در بارگذاری قیمت‌ها:', error);
        statusMessage.value = {
            type: 'error',
            text: error.response?.data?.message || 'خطا در بارگذاری لیست قیمت‌ها.',
        };
    } finally {
        isLoadingPrices.value = false;
        isLoadingMore.value = false;
    }
};

const loadMorePrices = () => {
    if (!hasMorePrices.value || isLoadingMore.value || isLoadingPrices.value) {
        return;
    }

    fetchPrices(currentPage.value + 1, true);
};

const refreshPriceList = () => fetchPrices(1, false);

let filterDebounceTimer = null;

watch(
    filters,
    () => {
        if (dateRangeError.value || priceRangeError.value) {
            return;
        }

        clearTimeout(filterDebounceTimer);
        filterDebounceTimer = setTimeout(() => {
            fetchPrices(1, false);
        }, 400);
    },
    { deep: true }
);

const validatePrices = (minPrice, maxPrice) => {
    if (!minPrice && minPrice !== 0) return 'حداقل قیمت را وارد کنید.';
    if (!maxPrice && maxPrice !== 0) return 'حداکثر قیمت را وارد کنید.';
    if (Number(maxPrice) < Number(minPrice)) return 'حداکثر قیمت باید بزرگتر از حداقل قیمت باشد.';
    return null;
};

const resetNewForm = () => {
    newForm.value = {
        product_id: '',
        location_id: '',
        min_price: '',
        max_price: '',
    };
};

const submitNewPrice = async () => {
    if (!newForm.value.product_id || !newForm.value.location_id) {
        formStatus.value = { type: 'error', text: 'لطفاً محصول و مکان را انتخاب کنید.' };
        return;
    }

    const validationError = validatePrices(newForm.value.min_price, newForm.value.max_price);
    if (validationError) {
        formStatus.value = { type: 'error', text: validationError };
        return;
    }

    formStatus.value = { type: 'loading', text: 'در حال ثبت قیمت...' };

    try {
        await axios.post('/api/prices/store-or-update', {
            product_id: newForm.value.product_id,
            location_id: newForm.value.location_id,
            min_price: newForm.value.min_price,
            max_price: newForm.value.max_price,
            date: newEntryDate.value,
        });

        formStatus.value = { type: 'success', text: 'قیمت با موفقیت ثبت شد.' };
        resetNewForm();
        await refreshPriceList();
    } catch (error) {
        const errorMessage = error.response?.data?.message || 'خطا در ثبت قیمت.';
        formStatus.value = { type: 'error', text: errorMessage };
    }
};

const openEdit = (price) => {
    editingPrice.value = price;
    editForm.value = {
        id: price.id,
        product_id: price.product_id,
        location_id: price.location_id,
        min_price: price.min_price,
        max_price: price.max_price,
        date: price.date,
    };
    editStatus.value = { type: '', text: '' };
};

const cancelEdit = () => {
    editingPrice.value = null;
    editForm.value = {
        id: null,
        product_id: '',
        location_id: '',
        min_price: '',
        max_price: '',
        date: '',
    };
    editStatus.value = { type: '', text: '' };
};

const submitEdit = async () => {
    if (!editForm.value.product_id || !editForm.value.location_id) {
        editStatus.value = { type: 'error', text: 'لطفاً محصول و مکان را انتخاب کنید.' };
        return;
    }

    const validationError = validatePrices(editForm.value.min_price, editForm.value.max_price);
    if (validationError) {
        editStatus.value = { type: 'error', text: validationError };
        return;
    }

    editStatus.value = { type: 'loading', text: 'در حال ذخیره تغییرات...' };

    try {
        await axios.post('/api/prices/store-or-update', {
            id: editForm.value.id,
            product_id: editForm.value.product_id,
            location_id: editForm.value.location_id,
            min_price: editForm.value.min_price,
            max_price: editForm.value.max_price,
            date: editForm.value.date,
        });

        editStatus.value = { type: 'success', text: 'قیمت با موفقیت بروزرسانی شد.' };
        cancelEdit();
        await refreshPriceList();
    } catch (error) {
        const errorMessage = error.response?.data?.message || 'خطا در ذخیره تغییرات.';
        editStatus.value = { type: 'error', text: errorMessage };
    }
};

const deletePrice = async (price) => {
    if (!confirm(`آیا از حذف قیمت «${price.product_name} - ${price.location_name}» مطمئن هستید؟`)) {
        return;
    }

    statusMessage.value = { type: 'loading', text: 'در حال حذف قیمت...' };

    try {
        await axios.delete(`/api/prices/${price.id}`);
        statusMessage.value = { type: 'success', text: 'قیمت با موفقیت حذف شد.' };
        await refreshPriceList();
    } catch (error) {
        const errorMessage = error.response?.data?.message || 'خطا در حذف قیمت.';
        statusMessage.value = { type: 'error', text: errorMessage };
    }
};

const isBusy = computed(() =>
    formStatus.value.type === 'loading' ||
    editStatus.value.type === 'loading' ||
    statusMessage.value.type === 'loading' ||
    isLoadingPrices.value
);
</script>

<template>
    <Head title="مدیریت قیمت‌ها" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                مدیریت قیمت‌ها
            </h2>
        </template>

        <div class="py-12" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- ثبت قیمت جدید -->
                <div class="bg-white overflow-hidden shadow-xl rounded-lg p-4 sm:p-6">
                    <h3 class="font-bold text-lg mb-6">ثبت قیمت جدید</h3>

                    <form @submit.prevent="submitNewPrice" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ</label>
                                <ShamsiDateInput v-model="newEntryDate" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">محصول</label>
                                <select
                                    v-model="newForm.product_id"
                                    class="block w-full text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2 bg-white focus:outline-none focus:border-green-500"
                                >
                                    <option value="" disabled>انتخاب محصول</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">
                                        {{ product.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">مکان</label>
                                <select
                                    v-model="newForm.location_id"
                                    class="block w-full text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2 bg-white focus:outline-none focus:border-green-500"
                                >
                                    <option value="" disabled>انتخاب مکان</option>
                                    <option v-for="location in locations" :key="location.id" :value="location.id">
                                        {{ location.name }} ({{ location.currency }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">حداقل قیمت</label>
                                <input
                                    v-model="newForm.min_price"
                                    type="number"
                                    placeholder="حداقل قیمت"
                                    class="block w-full text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:border-green-500"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">حداکثر قیمت</label>
                                <input
                                    v-model="newForm.max_price"
                                    type="number"
                                    placeholder="حداکثر قیمت"
                                    class="block w-full text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:border-green-500"
                                />
                            </div>
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                class="w-full sm:w-auto px-6 py-2.5 bg-green-600 text-white text-sm sm:text-base font-medium rounded-md hover:bg-green-700 transition-colors"
                                :disabled="isBusy"
                                :class="{ 'opacity-50 cursor-not-allowed': isBusy }"
                            >
                                <span v-if="formStatus.type === 'loading'" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    در حال ثبت...
                                </span>
                                <span v-else>ثبت قیمت جدید</span>
                            </button>
                        </div>
                    </form>

                    <div
                        v-if="formStatus.text && formStatus.type !== 'loading'"
                        class="mt-4 p-3 rounded-md text-center text-xs sm:text-sm font-medium"
                        :class="{
                            'bg-green-100 text-green-800 border border-green-300': formStatus.type === 'success',
                            'bg-red-100 text-red-800 border border-red-300': formStatus.type === 'error',
                        }"
                    >
                        {{ formStatus.text }}
                    </div>
                </div>

                <!-- فیلتر و جستجو -->
                <div class="bg-white shadow-xl rounded-lg p-4 sm:p-6 overflow-visible">
                    <h3 class="font-bold text-lg mb-4">فیلتر و جستجو</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="محصول یا مکان..."
                                class="block w-full text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">از تاریخ</label>
                            <ShamsiDateInput v-model="filters.fromDate" placeholder="از تاریخ" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">تا تاریخ</label>
                            <ShamsiDateInput v-model="filters.toDate" placeholder="تا تاریخ" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">حداقل قیمت</label>
                            <input
                                v-model.number="filters.minPrice"
                                type="number"
                                class="block w-full text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">حداکثر قیمت</label>
                            <input
                                v-model.number="filters.maxPrice"
                                type="number"
                                class="block w-full text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2"
                            />
                        </div>
                    </div>

                    <div
                        v-if="dateRangeError"
                        class="mt-4 p-3 rounded-md text-sm text-center bg-red-50 text-red-700 border border-red-200"
                    >
                        {{ dateRangeError }}
                    </div>

                    <div
                        v-if="priceRangeError"
                        class="mt-4 p-3 rounded-md text-sm text-center bg-red-50 text-red-700 border border-red-200"
                    >
                        {{ priceRangeError }}
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            پاک‌سازی فیلترها
                        </button>
                    </div>
                </div>

                <!-- لیست قیمت‌ها -->
                <div class="bg-white overflow-hidden shadow-xl rounded-lg p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                        <h3 class="font-bold text-lg">لیست قیمت‌ها</h3>
                        <p v-if="pricesMeta.total > 0" class="text-sm text-gray-500">
                            نمایش {{ priceList.length.toLocaleString('fa-IR') }} از {{ pricesMeta.total.toLocaleString('fa-IR') }} قیمت
                        </p>
                    </div>

                    <div
                        v-if="statusMessage.text && statusMessage.type !== 'loading'"
                        class="mb-4 text-center p-2 text-sm rounded-md"
                        :class="{
                            'bg-green-100 text-green-700': statusMessage.type === 'success',
                            'bg-red-100 text-red-700': statusMessage.type === 'error',
                        }"
                    >
                        {{ statusMessage.text }}
                    </div>

                    <!-- Desktop Table -->
                    <div v-if="isLoadingPrices" class="py-10 text-center text-gray-500">
                        <svg class="animate-spin h-8 w-8 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-3 text-sm">در حال بارگذاری قیمت‌ها...</p>
                    </div>

                    <div v-else class="hidden sm:block relative overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm text-right text-gray-700">
                            <thead class="bg-gray-100 uppercase text-gray-600 tracking-wider">
                                <tr>
                                    <th class="py-3 px-3 sm:px-4">#</th>
                                    <th class="py-3 px-3 sm:px-4">تاریخ</th>
                                    <th class="py-3 px-3 sm:px-4">محصول</th>
                                    <th class="py-3 px-3 sm:px-4">مکان</th>
                                    <th class="py-3 px-3 sm:px-4">حداقل</th>
                                    <th class="py-3 px-3 sm:px-4">حداکثر</th>
                                    <th class="py-3 px-3 sm:px-4 text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="priceList.length === 0">
                                    <td colspan="7" class="text-center py-6 text-gray-500">
                                        {{ hasActiveFilters ? 'نتیجه‌ای مطابق فیلترها وجود ندارد.' : 'قیمتی یافت نشد.' }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="(price, index) in priceList"
                                    :key="price.id"
                                    class="bg-white border-b last:border-0 hover:bg-gray-50"
                                >
                                    <td class="py-4 px-3 sm:px-4">{{ index + 1 }}</td>
                                    <td class="py-4 px-3 sm:px-4">{{ formatDate(price.date) }}</td>
                                    <td class="py-4 px-3 sm:px-4 font-medium">{{ price.product_name }}</td>
                                    <td class="py-4 px-3 sm:px-4">{{ price.location_name }}</td>
                                    <td class="py-4 px-3 sm:px-4">{{ formatNumber(price.min_price) }}</td>
                                    <td class="py-4 px-3 sm:px-4">{{ formatNumber(price.max_price) }}</td>
                                    <td class="py-4 px-3 sm:px-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                type="button"
                                                @click="openEdit(price)"
                                                class="text-blue-600 hover:text-blue-800 text-lg p-1 transition-colors"
                                                :disabled="isBusy"
                                                :class="{ 'opacity-50 cursor-not-allowed': isBusy }"
                                                title="ویرایش"
                                            >
                                                ✎
                                            </button>
                                            <button
                                                type="button"
                                                @click="deletePrice(price)"
                                                class="text-red-500 hover:text-red-700 text-lg p-1 transition-colors"
                                                :disabled="isBusy"
                                                :class="{ 'opacity-50 cursor-not-allowed': isBusy }"
                                                title="حذف"
                                            >
                                                &#x2716;
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div v-if="!isLoadingPrices" class="sm:hidden space-y-3">
                        <div v-if="priceList.length === 0" class="text-center py-8 text-gray-500">
                            {{ hasActiveFilters ? 'نتیجه‌ای مطابق فیلترها وجود ندارد.' : 'قیمتی یافت نشد.' }}
                        </div>
                        <div
                            v-for="(price, index) in priceList"
                            :key="price.id"
                            class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">
                                        {{ index + 1 }}. {{ price.product_name }}
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1">{{ price.location_name }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ formatDate(price.date) }}</div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-700 mb-3">
                                <span class="font-medium">حداقل:</span> {{ formatNumber(price.min_price) }}
                                <span class="mx-2">|</span>
                                <span class="font-medium">حداکثر:</span> {{ formatNumber(price.max_price) }}
                            </div>
                            <div class="flex gap-2 justify-end text-xs">
                                <button
                                    type="button"
                                    @click="openEdit(price)"
                                    class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700"
                                    :disabled="isBusy"
                                >
                                    ویرایش
                                </button>
                                <button
                                    type="button"
                                    @click="deletePrice(price)"
                                    class="px-3 py-1.5 bg-red-500 text-white rounded hover:bg-red-600"
                                    :disabled="isBusy"
                                >
                                    حذف
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="!isLoadingPrices && hasMorePrices" class="mt-6 text-center">
                        <button
                            type="button"
                            @click="loadMorePrices"
                            class="px-6 py-2.5 text-sm font-medium border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            :disabled="isLoadingMore"
                            :class="{ 'opacity-50 cursor-not-allowed': isLoadingMore }"
                        >
                            <span v-if="isLoadingMore" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                در حال بارگذاری...
                            </span>
                            <span v-else>نمایش بیشتر ({{ pricesMeta.per_page.toLocaleString('fa-IR') }} مورد بعدی)</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="editingPrice"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
        >
            <div
                class="bg-white rounded-lg shadow-xl w-full max-w-lg p-4 sm:p-6 max-h-[90vh] overflow-y-auto"
                dir="rtl"
            >
                <h3 class="text-base sm:text-lg font-bold mb-4">
                    ویرایش قیمت: {{ editingPrice.product_name }} - {{ editingPrice.location_name }}
                </h3>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ</label>
                        <ShamsiDateInput v-model="editForm.date" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">محصول</label>
                        <select v-model="editForm.product_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md bg-white">
                            <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مکان</label>
                        <select v-model="editForm.location_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md bg-white">
                            <option v-for="location in locations" :key="location.id" :value="location.id">
                                {{ location.name }} ({{ location.currency }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">حداقل قیمت</label>
                        <input
                            v-model="editForm.min_price"
                            type="number"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">حداکثر قیمت</label>
                        <input
                            v-model="editForm.max_price"
                            type="number"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md"
                        />
                    </div>

                    <div
                        v-if="editStatus.text && editStatus.type !== 'loading'"
                        class="p-2 rounded-md text-center text-sm"
                        :class="{
                            'bg-green-100 text-green-700': editStatus.type === 'success',
                            'bg-red-100 text-red-700': editStatus.type === 'error',
                        }"
                    >
                        {{ editStatus.text }}
                    </div>

                    <div class="flex gap-3 justify-end pt-2">
                        <button
                            type="button"
                            @click="cancelEdit"
                            class="px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        >
                            انصراف
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700"
                            :disabled="editStatus.type === 'loading'"
                            :class="{ 'opacity-50 cursor-not-allowed': editStatus.type === 'loading' }"
                        >
                            {{ editStatus.type === 'loading' ? 'در حال ذخیره...' : 'ذخیره تغییرات' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
