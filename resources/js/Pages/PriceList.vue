<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import ChartModal from '@/Components/ChartModal.vue';
import SeoHead from '@/Components/SeoHead.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// --- State Variables for Price List ---
const prices = ref([]);
const locations = ref([]); // <--- این ref فقط مکان های فعال را نگه میدارد
const allAvailableLocations = ref([]);
const shamsiDate = ref('');
let priceInterval = null;
const isUpdating = ref(false);

// --- State Variables for Chart Modal ---
const showChartModal = ref(false);
const selectedProductId = ref(null);
const selectedProductName = ref('');
const selectedProductColor = ref(null);
const selectedLocationId = ref(null);
const selectedLocationName = ref('');
const selectedLocationCurrency = ref('');
const chartCategories = ref([]); // محور X (تاریخ ها)
const chartSeries = ref([]);     // داده های نمودار (min/max برای هر مکان)
const selectedPeriod = ref('daily'); // بازه زمانی پیش فرض
const selectedChartType = ref('line'); // نوع نمودار پیش فرض
const isLoadingChartData = ref(false);


// --- Functions ---

// این تابع هم محصولات و هم مکان‌ها را از سرور می‌گیرد
async function fetchData() {
    isUpdating.value = true;
    try {
        // <--- تغییر: Promis ها را به صورت جداگانه اجرا میکنیم تا خطاها را بهتر ببینیم
        const pricesResponse = await axios.get('/api/prices/today');
        const allLocationsResponse = await axios.get('/api/locations');

        prices.value = pricesResponse.data;
        allAvailableLocations.value = allLocationsResponse.data; // <--- استفاده از allAvailableLocations ref
        
        const activeLocationIdsFromPrices = new Set();
        prices.value.forEach(product => {
            if (product.prices) { 
                Object.keys(product.prices).forEach(locId => {
                    activeLocationIdsFromPrices.add(parseInt(locId));
                });
            }
        });
        
        locations.value = allAvailableLocations.value.filter(loc => activeLocationIdsFromPrices.has(loc.id));


    } catch (e) {
        console.error('خطا در دریافت اطلاعات از سرور:', e);
        // <--- برای نمایش ارور در UI
        // statusMessage.value = {type: 'error', text: 'خطا در بارگذاری اطلاعات'}; 
    } finally {
        isUpdating.value = false;
    }
}


function getShamsiDate() {
    shamsiDate.value = new Intl.DateTimeFormat('fa-IR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        // weekday: 'long' // حذف شد
    }).format(new Date());
}

// <--- تابع جدید: دریافت داده‌های تاریخچه برای نمودار
async function fetchChartData() {
  if (!selectedProductId.value) return;

  isLoadingChartData.value = true;
  chartSeries.value = [];
  chartCategories.value = [];

  try {
    const response = await axios.get(`/api/prices/history/${selectedProductId.value}`, {
      params: { period: selectedPeriod.value }
    });

    const rawData = response.data;

    if (!rawData.length) {
        return;
    }

    // استخراج تاریخ‌ها برای محور X
    chartCategories.value = rawData.map(item => item.date);

    // اگر location مشخص است، فقط آن location را نمایش بده
    if (selectedLocationId.value) {
        const location = locations.value.find(loc => loc.id === selectedLocationId.value);
        if (location) {
            // سری برای حداقل قیمت
            chartSeries.value.push({
                name: `کمینه ${location.name}`,
                data: rawData.map(item => item[`min_${location.name}`] || null)
            });
            // سری برای حداکست قیمت
            chartSeries.value.push({
                name: `بیشینه ${location.name}`,
                data: rawData.map(item => item[`max_${location.name}`] || null)
            });
        }
    } else {
        // آماده‌سازی series برای ApexCharts (min و max برای هر location)
        // برای هر location (مثلا Sorting), دو سری (min و max) می سازیم
        locations.value.forEach(loc => {
            // سری برای حداقل قیمت
            chartSeries.value.push({
                name: `کمینه ${loc.name}`,
                data: rawData.map(item => item[`min_${loc.name}`] || null)
            });
            // سری برای حداکست قیمت
            chartSeries.value.push({
                name: `بیشینه ${loc.name}`,
                data: rawData.map(item => item[`max_${loc.name}`] || null)
            });
        });
    }

  } catch (error) {
    console.error("خطا در دریافت داده‌های نمودار:", error);
  } finally {
    isLoadingChartData.value = false; // <--- پایان لودینگ نمودار
  }
}

// <--- تابع جدید: باز کردن مودال نمودار
function openChartModal(productId, productName, locationId, locationName, productColor, locationCurrency) {
  selectedProductId.value = productId;
  selectedProductName.value = productName;
  selectedProductColor.value = productColor || null;
  selectedLocationId.value = locationId || null;
  selectedLocationName.value = locationName || '';
    selectedLocationCurrency.value = locationCurrency || '';
  selectedPeriod.value = 'daily'; // ریست به حالت پیش فرض
  selectedChartType.value = 'line'; // ریست به حالت پیش فرض
  showChartModal.value = true;
}

// <--- تابع جدید: بستن مودال نمودار
function closeChartModal() {
  showChartModal.value = false;
  selectedProductId.value = null;
  selectedProductName.value = '';
  selectedProductColor.value = null;
  selectedLocationId.value = null;
  selectedLocationName.value = '';
    selectedLocationCurrency.value = '';
  chartCategories.value = [];
  chartSeries.value = [];
}

// <--- Watcher برای تغییر selectedProductId یا selectedPeriod
// هر زمان که اینها تغییر کنند، داده های نمودار دوباره لود می شوند
watch([selectedProductId, selectedPeriod, selectedChartType, selectedLocationId], ([newId, newPeriod, newChartType, newLocationId]) => { // <--- selectedChartType را هم اضافه کردیم
  if (newId) { // فقط اگر محصولی انتخاب شده باشد
    fetchChartData();
  }
});


// --- Lifecycle Hooks ---
onMounted(() => {
    fetchData();
    getShamsiDate();
    priceInterval = setInterval(fetchData, 10000);
});

function hexToRgba(hex, alpha) {
    if (!hex) return null;
    const h = hex.replace('#','');
    const bigint = parseInt(h, 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

onUnmounted(() => {
    clearInterval(priceInterval);
});


// --- Utility Functions ---
const formatNumberToPersian = (num) => {
  if (num === null || num === undefined || num === '') return '-';
  // استفاده از Intl.NumberFormat برای فرمت دهی صحیح اعداد فارسی و جداکننده هزارگان
  return new Intl.NumberFormat('fa-IR').format(num);
};

// <--- تابع calculateAverage اصلاح شد
const calculateAverage = (min, max) => {
  if (min === null || max === null || min === undefined || max === undefined || min === '' || max === '') return null; // اگر هر کدام خالی بود، null برگردان
  const parsedMin = parseFloat(min);
  const parsedMax = parseFloat(max);

  if (isNaN(parsedMin) || isNaN(parsedMax)) return null; // اگر عدد نبود، null برگردان

  return (parsedMin + parsedMax) / 2;
};
const formatPriceDisplay = (min, max, change) => { // <--- change را به عنوان ورودی اضافه کن
    const parsedMin = min !== null && min !== undefined && min !== '' ? parseFloat(min) : null;
    const parsedMax = max !== null && max !== undefined && max !== '' ? parseFloat(max) : null;

    let displayString = '';
    let averageValue = null;

    if (parsedMin !== null && parsedMax === null) {
        displayString = formatNumberToPersian(parsedMin);
    } else if (parsedMin === null && parsedMax !== null) {
        displayString = formatNumberToPersian(parsedMax);
    }
    else if (parsedMin !== null && parsedMax !== null && parsedMin === parsedMax) {
        displayString = formatNumberToPersian(parsedMin);
    }
    else if (parsedMin !== null && parsedMax !== null && parsedMin !== parsedMax) {
        displayString = `${formatNumberToPersian(parsedMin)} - ${formatNumberToPersian(parsedMax)}`;
        averageValue = calculateAverage(parsedMin, parsedMax);
    }
    else {
        displayString = '-';
    }

    return { display: displayString, average: averageValue, change: change }; // <--- change را هم برمیگردانیم
};

const priceIcon = 'ریال';

// <--- تابع برای دریافت کلاس رنگ بر اساس تغییر قیمت
const getChangeColorClass = (change) => {
    if (change === 1) return 'text-red-500'; // گرانتر
    if (change === -1) return 'text-primaryGreen'; // ارزانتر (سبز)
    return 'text-gray-700'; // بدون تغییر یا نامعلوم
};

// <--- تابع برای دریافت آیکون فلش بر اساس تغییر قیمت
const getChangeArrow = (change) => {
    if (change === 1) return '▲'; // فلش رو به بالا (یونیکد)
    if (change === -1) return '▼'; // فلش رو به پایین (یونیکد)
    return ''; // بدون فلش
};


</script>

<template>
    <SeoHead
        title="لیست قیمت محصولات"
        description="قیمت روز میوه و سبزیجات (خیار، گوجه‌فرنگی، فلفل، پیاز) در بازارهای مختلف. به‌روزرسانی لحظه‌ای قیمت‌ها با نمودار تغییرات"
        keywords="قیمت میوه, قیمت سبزیجات, نرخ‌نامه, بازار، خیار، گوجه‌فرنگی، فلفل، پیاز، قیمت روز"
    />

    <!-- هدر ... (بدون تغییر) -->
    <header class="bg-gradient-to-br from-primaryPurple via-primaryRed to-primaryGreen p-4 sm:p-6 md:p-10 text-center text-white rounded-b-3xl shadow-xl relative">
                <!-- <--- لوگوی سفارشی -->
        <div class="absolute top-2 right-2 sm:top-4 sm:right-4 md:top-6 md:right-6 bg-white p-1.5 sm:p-2 md:p-3 rounded-lg sm:rounded-xl shadow-2xl flex items-center justify-center transform hover:scale-105 transition-transform">
            <img src="/images/my-logo2.png" alt="لوگوی من" class="h-10 sm:h-16 md:h-24 w-auto"> <!-- <--- ارتفاع کوچک‌تر برای موبایل -->
        </div>

        <!-- دکمه ورود/حساب کاربری -->
        <div class="absolute top-2 left-2 sm:top-4 sm:left-4 md:top-6 md:left-6">
            <Link 
                v-if="!user"
                :href="route('login')"
                class="inline-flex items-center gap-1 sm:gap-2 px-2 sm:px-4 py-1.5 sm:py-2 bg-white/20 hover:bg-white/30 text-white text-xs sm:text-sm rounded-lg sm:rounded-xl transition-colors duration-300 backdrop-blur-sm border border-white/30"
            >
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                <span class="hidden sm:inline">ورود</span>
                <span class="sm:hidden">ورود</span>
            </Link>
            <Link 
                v-else
                :href="route('dashboard')"
                class="inline-flex items-center gap-1 sm:gap-2 px-2 sm:px-4 py-1.5 sm:py-2 bg-white/20 hover:bg-white/30 text-white text-xs sm:text-sm rounded-lg sm:rounded-xl transition-colors duration-300 backdrop-blur-sm border border-white/30"
            >
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="hidden sm:inline">{{ user.name }}</span>
                <span class="sm:hidden">حساب</span>
            </Link>
        </div>

      <div class="flex items-center justify-center text-xs sm:text-base md:text-lg mb-3 sm:mb-2 mt-16 sm:mt-0">
            <!-- <--- این div جدید برای پس‌زمینه اضافه شد -->
            <div class="bg-white/20 backdrop-blur-sm px-2 sm:px-4 py-1 sm:py-2 rounded-full flex items-center gap-1 sm:gap-2">
                <span class="relative flex h-2 w-2 sm:h-3 sm:w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 sm:h-3 sm:w-3 bg-red-500"></span>
                </span>
                <span class="text-xs sm:text-base">بروزرسانی لحظه‌ای</span>
                <svg v-if="isUpdating" class="animate-spin -ml-0.5 sm:-ml-1 w-3 h-3 sm:w-5 sm:h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        <div class="text-xs sm:text-sm md:text-base mb-2 sm:mb-4">
            تاریخ امروز: {{ shamsiDate }} | قیمت‌ها معتبر از ۷ صبح تا ۱۲ شب
        </div>
        <h1 class="text-lg sm:text-2xl md:text-4xl font-extrabold">لیست قیمت محصولات</h1>
    </header>

    <div class="w-full mx-auto p-4 sm:p-6 md:p-8">
        <!-- جدول برای دسکتاپ و تبلت -->
        <div class="hidden md:block bg-white overflow-hidden shadow-xl rounded-lg mb-8">
            <div class="p-4 md:p-6 text-gray-900">
                <div class="relative overflow-x-auto rounded-lg border border-gray-200" dir="rtl">
                    <table class="w-full text-sm text-right text-gray-700 price-table"> <!-- text-right برای RTL -->
                        <thead class="bg-gray-100 uppercase text-gray-600 tracking-wider">
                            <tr>
                                <th scope="col" class="py-2 sm:py-3 px-2 sm:px-4 text-center align-middle">LIVE</th> <!-- Center for icon -->
                                <th scope="col" class="py-2 sm:py-3 px-2 sm:px-4 text-right align-middle">محصول</th> <!-- Right for text -->
                                <th v-for="loc in locations" :key="loc.id" scope="col" class="py-2 sm:py-3 px-1.5 sm:px-3 text-center align-middle"> <!-- Center for location names, so price ranges can be centered under them -->
                                    <div class="text-xs sm:text-sm lg:text-base break-words">{{ loc.name }}</div>
                                    <div class="text-xs font-normal text-gray-500 mt-0.5 sm:mt-1">{{ loc.currency }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- <--- لودر اصلی برای بارگذاری اولیه -->
                            <tr v-if="prices.length === 0 && isUpdating">
                                <td :colspan="2 + locations.length" class="text-center py-8 text-gray-500 align-middle">
                                    <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="mt-2">در حال بارگذاری اطلاعات...</p>
                                </td>
                            </tr>
                            <tr v-if="prices.length === 0" class="border-b last:border-0">
                                <td :colspan="2 + locations.length" class="text-center py-8 text-gray-500 align-middle">
                                    در حال بارگذاری یا قیمتی برای امروز ثبت نشده...
                                </td>
                            </tr>
                            <tr v-for="item in prices" :key="item.id" :style="{ backgroundColor: item.color ? hexToRgba(item.color, 0.04) : '' }" class="bg-white border-b last:border-0 hover:bg-gray-50 transition-colors duration-200">
                                <td class="py-3 sm:py-4 px-2 sm:px-4 text-center align-middle"> <!-- Center for icon -->
                                    <span class="relative flex h-3 w-3 mx-auto">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                    </span>
                                </td>
                                <td class="py-3 sm:py-4 px-2 sm:px-4 font-medium text-gray-900 whitespace-nowrap text-right align-middle"> <!-- Right for text -->
                                    <div class="flex items-center gap-2 sm:gap-4">
                                        <div v-if="item.image" class="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-lg overflow-hidden border-2 flex-shrink-0" :style="{ borderColor: item.color || '#ddd' }">
                                            <img :src="`/storage/${item.image}`" :alt="`تصویر ${item.product_name}`" :title="item.product_name" class="w-full h-full object-cover">
                                        </div>
                                        <div class="flex-1 text-right min-w-0">
                                            <div class="text-xs sm:text-base md:text-lg font-medium truncate">{{ item.product_name }}</div>
                                            <div v-if="item.color" class="mt-1 sm:mt-2 flex items-center justify-end gap-2">
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td v-for="loc in locations" :key="loc.id" class="py-3 sm:py-4 px-1.5 sm:px-3 text-center align-middle">
                                    <div v-if="item.prices[loc.id] && item.prices[loc.id].min_price !== null" class="flex flex-col items-center justify-center gap-1 sm:gap-2">
                                        <div class="text-xs sm:text-sm font-bold flex flex-col sm:flex-row items-center justify-center gap-1" :class="getChangeColorClass(item.prices[loc.id].change)"> <!-- <--- کلاس رنگ -->
                                             {{ formatPriceDisplay(item.prices[loc.id].min_price, item.prices[loc.id].max_price, item.prices[loc.id].change).display }}   <span class="text-xs ml-0 sm:ml-1" :class="getChangeColorClass(item.prices[loc.id].change)">{{ loc.currency }}</span>
                                            <span class="text-base"> {{ getChangeArrow(item.prices[loc.id].change) }} </span> <!-- <--- فلش -->
                                        </div>
                                        <div v-if="formatPriceDisplay(item.prices[loc.id].min_price, item.prices[loc.id].max_price, item.prices[loc.id].change).average !== null" class="text-xs mt-0.5 sm:mt-1" :class="getChangeColorClass(item.prices[loc.id].change)">
                                            میانگین:  {{ formatNumberToPersian(formatPriceDisplay(item.prices[loc.id].min_price, item.prices[loc.id].max_price, item.prices[loc.id].change).average) }} {{ loc.currency }}
                                        </div>
                                        <button @click="openChartModal(item.id, item.product_name, loc.id, loc.name, item.color, loc.currency)" class="chart-btn bg-primaryPurple text-white px-2 py-1 rounded text-xs hover:bg-primaryRed transition-colors duration-300 mt-1">
                                            نمایش نمودار
                                        </button>
                                    </div>
                                    <span v-else>-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- نمای کارت برای موبایل -->
        <div v-if="prices.length === 0 && isUpdating" class="md:hidden text-center py-8 text-gray-500">
            <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2">در حال بارگذاری اطلاعات...</p>
        </div>
        <div v-else-if="prices.length === 0" class="md:hidden text-center py-8 text-gray-500">
            در حال بارگذاری یا قیمتی برای امروز ثبت نشده...
        </div>
        <div v-else class="md:hidden space-y-4">
            <!-- کارت برای هر محصول در موبایل -->
            <div v-for="item in prices" :key="item.id" class="bg-white rounded-lg shadow-lg p-4 border-r-4" :style="{ borderColor: item.color || '#ddd', backgroundColor: item.color ? hexToRgba(item.color, 0.02) : '' }">
                <!-- هدر کارت -->
                <div class="flex items-start gap-3 mb-4 pb-4 border-b">
                    <div v-if="item.image" class="w-14 h-14 rounded-lg overflow-hidden border-2 flex-shrink-0" :style="{ borderColor: item.color || '#ddd' }">
                        <img :src="`/storage/${item.image}`" :alt="`تصویر ${item.product_name}`" :title="item.product_name" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-gray-900">{{ item.product_name }}</h3>
                        <div v-if="item.color" class="text-xs text-gray-600 mt-1">
                            <span :style="{ background: item.color }" class="inline-block w-3 h-3 rounded border mr-1"></span>
                            {{ item.color }}
                        </div>
                    </div>
                    <span class="relative flex h-3 w-3 flex-shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                </div>

                <!-- قیمت‌های هر مکان -->
                <div class="space-y-3">
                    <div v-for="loc in locations" :key="loc.id" class="bg-gray-50 p-3 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-sm text-gray-800">{{ loc.name }}</span>
                            <span class="text-xs bg-gray-200 px-2 py-1 rounded">{{ loc.currency }}</span>
                        </div>
                        
                        <div v-if="item.prices[loc.id] && item.prices[loc.id].min_price !== null" class="space-y-2">
                            <div class="text-sm font-bold" :class="getChangeColorClass(item.prices[loc.id].change)">
                                <span>{{ formatPriceDisplay(item.prices[loc.id].min_price, item.prices[loc.id].max_price, item.prices[loc.id].change).display }}</span>
                                <span class="text-xs ml-1">{{ loc.currency }}</span>
                                <span class="text-base ml-1">{{ getChangeArrow(item.prices[loc.id].change) }}</span>
                            </div>
                            <div v-if="formatPriceDisplay(item.prices[loc.id].min_price, item.prices[loc.id].max_price, item.prices[loc.id].change).average !== null" class="text-xs text-gray-600">
                                میانگین: {{ formatNumberToPersian(formatPriceDisplay(item.prices[loc.id].min_price, item.prices[loc.id].max_price, item.prices[loc.id].change).average) }}
                            </div>
                            <button @click="openChartModal(item.id, item.product_name, loc.id, loc.name, item.color, loc.currency)" class="w-full chart-btn bg-primaryPurple text-white px-3 py-2 rounded text-xs hover:bg-primaryRed transition-colors duration-300 mt-2">
                                نمایش نمودار
                            </button>
                        </div>
                        <div v-else class="text-sm text-gray-500 text-center py-2">
                            قیمت موجود نیست
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-center mt-12 mb-8">
            <img src="/images/my-logo.png" alt="لوگوی بزرگ" class="h-48 md:h-64 w-auto opacity-15"> <!-- <--- opacity برای حالت واترمارک -->
        </div>
    </div>
        
    <!-- کامپوننت مودال نمودار ... (بدون تغییر) -->
    <ChartModal
      :show="showChartModal"
      :productName="selectedProductName"
      :productColor="selectedProductColor"
      :locationName="selectedLocationName"
    :locationCurrency="selectedLocationCurrency"
      :chartCategories="chartCategories"
      :chartSeries="chartSeries"
      :locations="locations"
      :selectedPeriod="selectedPeriod"
      :selectedChartType="selectedChartType"
      :isLoadingChartData="isLoadingChartData"
      @close="closeChartModal"
      @update:period="period => selectedPeriod = period"
      @update:chartType="type => selectedChartType = type"
    />

</template>



<style>
/* فونت Vazirmatn در app.css تعریف شده است */
/* استایل‌های سفارشی برای دکمه نمودار */
.chart-btn:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(125, 0, 252, 0.5); /* رنگ primaryPurple */
}

/* larger product images */
.price-table img { border-radius: 6px; }

/* subtle row transition */
table tr td { transition: background-color .2s ease; }
</style>
