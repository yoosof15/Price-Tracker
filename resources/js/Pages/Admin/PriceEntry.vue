<script setup>
import { ref, onMounted, computed, reactive, watch  } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';


// --- State Variables ---
const allProducts = ref([]); // لیست کامل محصولات
const allLocations = ref([]); // لیست کامل مکان ها

const activeProductSettings = ref([]); // لیست تنظیمات محصول-مکان فعال برای امروز (از DailyPriceSetting)
const activeLocationSettings = ref([]); // لیست تنظیمات محصول-مکان فعال برای امروز (از DailyPriceSetting)

const pricesMap = reactive(new Map()); // Map: key = "productId_locationId", value = {min_price: ref(null), max_price: ref(null)}


const statusMessage = ref('');
const shamsiDate = ref('');
const isLoading = ref(true); // لودینگ کلی صفحه

// --- متغیرهای فرم افزودن جدید ---
const newProductName = ref('');
const addProductStatus = ref('');
const newLocationName = ref('');
const newLocationCurrency = ref('ریال');
const addLocationStatus = ref('');

// --- متغیرهای مودال تایید حذف دائمی ---
const showPermanentDeleteConfirmModal = ref(false);
const itemToDeletePermanently = ref(null);
const permanentDeleteType = ref(''); // 'product' یا 'location'

// --- Computed Properties ---


const productsForPricing = computed(() => {
    const activeProductIds = new Set(activeProductSettings.value.map(s => s.product_id));
    return allProducts.value.filter(p => activeProductIds.has(p.id));
});

const locationsForPricing = computed(() => {
    const activeLocationIds = new Set(activeLocationSettings.value.map(s => s.location_id));
    return allLocations.value.filter(l => activeLocationIds.has(l.id));
});

// --- توابع ---

function initializePrices() {
  const newPricesState = new Map(); // یک Map جدید می سازیم

  productsForPricing.value.forEach(product => {
    const todaysPricesForProduct = product.prices ? Object.fromEntries(
        Object.entries(product.prices).map(([locIdStr, priceObj]) => [parseInt(locIdStr), priceObj])
    ) : {};
    
    locationsForPricing.value.forEach(location => {
      const key = `${product.id}_${location.id}`;
      const todaysPrice = todaysPricesForProduct[location.id];

      // <--- حالا داده های موجود در pricesMap را با داده های جدید ادغام میکنیم
      const existingEntry = pricesMap.get(key);

      newPricesState.set(key, reactive({
        min_price: existingEntry?.min_price ?? todaysPrice?.min_price ?? null, // حفظ ورودی کاربر
        max_price: existingEntry?.max_price ?? todaysPrice?.max_price ?? null  // حفظ ورودی کاربر
      }));
    });
  });

  // <--- حذف entries هایی که دیگر فعال نیستند
  for (const key of pricesMap.keys()) {
      if (!newPricesState.has(key)) {
          pricesMap.delete(key);
      }
  }

  // <--- بروزرسانی pricesMap با entries های جدید و ادغام شده
  newPricesState.forEach((value, key) => {
    // فقط Property ها را کپی میکنیم تا Reactive Object اصلی حفظ شود
    if (pricesMap.has(key)) {
        const existingPriceEntry = pricesMap.get(key);
        existingPriceEntry.min_price = value.min_price;
        existingPriceEntry.max_price = value.max_price;
    } else {
        pricesMap.set(key, value); // اضافه کردن entry های کاملا جدید
    }
  });


}

// واکشی تمام داده های مورد نیاز (محصولات، مکان ها، تنظیمات روزانه، قیمت های امروز)
async function fetchData() {
  isLoading.value = true;
  try {
    const [
      allProductsResponse,
      allLocationsResponse,
      dailySettingsResponse,
      pricesTodayResponse
    ] = await Promise.all([
      axios.get('/admin/api/products'),
      axios.get('/admin/api/locations'),
      axios.get('/admin/daily-settings'),
      axios.get('/api/prices/today')
    ]);
    allProducts.value = allProductsResponse.data;
    allLocations.value = allLocationsResponse.data;
    
    // <--- اصلاح مهم: جدا کردن تنظیمات محصول و مکان از DailyPriceSetting
    // dailySettingsResponse.data شامل product_id و location_id با هم یا یکی null است
    activeProductSettings.value = dailySettingsResponse.data.filter(s => s.product_id !== null);
    activeLocationSettings.value = dailySettingsResponse.data.filter(s => s.location_id !== null);
    


    // برای هر محصول، قیمت های امروز را به صورت یک property جدید به آن اضافه میکنیم
    // تا initializePrices از آن استفاده کند.
    const pricesMapApi = new Map();
    pricesTodayResponse.data.forEach(item => {
        pricesMapApi.set(item.id, item.prices);
    });
    allProducts.value.forEach(product => {
        product.prices = pricesMapApi.get(product.id) || {};
    });

    initializePrices();
  } catch (error) {
    console.error("خطا در دریافت اطلاعات اولیه:", error);
    statusMessage.value = { type: 'error', text: 'خطا در دریافت اطلاعات اولیه از سرور.' };
  } finally {
    isLoading.value = false;
  }
}


function getShamsiDate() {
    shamsiDate.value = new Intl.DateTimeFormat('fa-IR', {
        year: 'numeric', month: 'long', day: 'numeric'
    }).format(new Date());
}

// --- توابع مدیریت DailyPriceSetting ---

// افزودن محصول به لیست فعال امروز
async function addProductToToday(product) {
    try {
        await axios.post('/admin/daily-settings', { product_id: product.id, location_id: null }); // location_id = null
        await fetchData(); // بروزرسانی
    } catch (error) {
        console.error("خطا در افزودن محصول به فعال امروز:", error);
        statusMessage.value = { type: 'error', text: 'خطا در افزودن محصول به فعال امروز. (شاید قبلا اضافه شده باشد؟)' };
    }
}

// حذف محصول از لیست فعال امروز
async function removeProductFromToday(productId) {
    try {
        await axios.delete(`/admin/daily-settings/product/${productId}`); // <--- type و id
        await fetchData(); // بروزرسانی
    } catch (error) {
        console.error("خطا در حذف محصول از فعال امروز:", error);
        statusMessage.value = { type: 'error', text: 'خطا در حذف محصول از فعال امروز.' };
    }
}

// افزودن مکان به لیست فعال امروز
async function addLocationToToday(location) {
    try {
        await axios.post('/admin/daily-settings', { product_id: null, location_id: location.id }); // product_id = null
        await fetchData(); // بروزرسانی
    } catch (error) {
        console.error("خطا در افزودن مکان به فعال امروز:", error);
        statusMessage.value = { type: 'error', text: 'خطا در افزودن مکان به فعال امروز. (شاید قبلا اضافه شده باشد؟)' };
    }
}

// حذف مکان از لیست فعال امروز
async function removeLocationFromToday(locationId) {
    try {
        await axios.delete(`/admin/daily-settings/location/${locationId}`); // <--- type و id
        await fetchData(); // بروزرسانی
    } catch (error) {
        console.error("خطا در حذف مکان از فعال امروز:", error);
        statusMessage.value = { type: 'error', text: 'خطا در حذف مکان از فعال امروز.' };
    }
}




// --- توابع حذف دائمی (برای مودال تایید) ---

function openPermanentDeleteConfirm(item, type) {
    itemToDeletePermanently.value = item;
    permanentDeleteType.value = type;
    showPermanentDeleteConfirmModal.value = true;
}

async function confirmPermanentDelete() {
    if (!itemToDeletePermanently.value) return;

    showPermanentDeleteConfirmModal.value = false;

    try {
        if (permanentDeleteType.value === 'product') {
            await axios.delete(`/admin/products/${itemToDeletePermanently.value.id}`);
            addProductStatus.value = { type: 'success', text: `محصول "${itemToDeletePermanently.value.name}" برای همیشه حذف شد.` };
        } else if (permanentDeleteType.value === 'location') {
            await axios.delete(`/admin/locations/${itemToDeletePermanently.value.id}`);
            addLocationStatus.value = { type: 'success', text: `مکان "${itemToDeletePermanently.value.name}" برای همیشه حذف شد.` };
        }
        await fetchData(); // Refresh all data
    } catch (error) {
        console.error("خطا در حذف دائمی:", error);
        const errorMessage = error.response?.data?.message || 'خطا در حذف دائمی. (شاید قیمت‌هایی وابسته باشند؟)';
        if (permanentDeleteType.value === 'product') {
            addProductStatus.value = { type: 'error', text: errorMessage };
        } else if (permanentDeleteType.value === 'location') {
            addLocationStatus.value = { type: 'error', text: errorMessage };
        }
    }
    itemToDeletePermanently.value = null;
    permanentDeleteType.value = '';
}

// --- ذخیره قیمت‌ها ---
async function submitPrices() {
      statusMessage.value = { type: 'loading', text: 'در حال ذخیره...' };
    let payload = [];
    
    productsForPricing.value.forEach(product => {
        locationsForPricing.value.forEach(location => {
            const key = `${product.id}_${location.id}`;
            const priceData = pricesMap.get(key); // <--- از Map میگیریم
            
            const hadPriceBefore = product.prices && product.prices[location.id] && 
                                   (product.prices[location.id].min_price !== null || product.prices[location.id].max_price !== null);

            const hasNewData = priceData && (priceData.min_price || priceData.max_price);
            const isBeingCleared = hadPriceBefore && !hasNewData;

            if (hasNewData || isBeingCleared) {
                 payload.push({
                    product_id: product.id,
                    location_id: location.id,
                    min_price: priceData?.min_price ?? null,
                    max_price: priceData?.max_price ?? null,
                });
            }
        });
    });

    if (payload.length === 0) {
        statusMessage.value = { type: 'info', text: 'هیچ تغییری برای ثبت وجود ندارد.' };
        return;
    }

    try {
        await axios.post('/admin/prices', { prices: payload });
        statusMessage.value = { type: 'success', text: 'قیمت‌ها با موفقیت ثبت شد.' };
        await fetchData(); // بروزرسانی اطلاعات
    } catch (error) {
        console.error("خطا در ثبت قیمت‌ها:", error);
        const errorMessage = error.response?.data?.message || 'خطا در ثبت قیمت‌ها. لطفاً دوباره تلاش کنید.';
        statusMessage.value = { type: 'error', text: errorMessage };
    }
}


// --- Lifecycle Hooks ---
onMounted(() => {
    fetchData();
    getShamsiDate();
});


watch(() => [productsForPricing.value, locationsForPricing.value], (current, prev) => {
    // فقط اگر محصولات یا مکان ها واقعا تغییر کرده اند و صفحه در حال لودینگ نیست
    // و اگر بعد از لودینگ اولیه بوده
    if (!isLoading.value && (productsForPricing.value.length > 0 || locationsForPricing.value.length > 0)) {
        initializePrices();
    }
}, { deep: true, immediate: true }); // immediate: true برای اجرای اولیه

// <--- اصلاح تابع getPriceModel
const getPriceModel = (productId, locationId, type) => {
    const key = `${productId}_${locationId}`;
    if (!pricesMap.has(key)) {
        pricesMap.set(key, reactive({ min_price: null, max_price: null }));
    }
    const priceEntry = pricesMap.get(key);

    return computed({
        get() {
            // console.log(`Getter for ${key}-${type}:`, priceEntry[type]); // <--- برای اشکال زدایی
            return priceEntry[type];
        },
        set(value) {
            // console.log(`Setter for ${key}-${type}: old: ${priceEntry[type]}, new: ${value}`); // <--- برای اشکال زدایی
            // <--- مهم: اطمینان حاصل میکنیم که value عدد باشد یا null
            priceEntry[type] = value === '' ? null : parseFloat(value);
            if (isNaN(priceEntry[type])) { // اگر تبدیل به عدد ناموفق بود
                priceEntry[type] = null;
            }
        }
    });
};


// --- Utility Functions (برای استفاده در template) ---

// آیا محصولی در لیست تنظیمات روزانه هست؟
const isProductInDailySettings = (productId) => {
    return activeProductSettings.value.some(s => s.product_id === productId);
};

// آیا مکانی در لیست تنظیمات روزانه هست؟
const isLocationInDailySettings = (locationId) => {
    return activeLocationSettings.value.some(s => s.location_id === locationId);
};

function hexToRgba(hex, alpha) {
    if (!hex) return null;
    const h = hex.replace('#','');
    const bigint = parseInt(h, 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}
</script>



<template>
    <Head title="پنل ادمین" />

    <!--
        AuthenticatedLayout را حذف کردیم و Layout اصلی را خودمان کنترل میکنیم.
        اما برای AuthenticatedLayout، محتوای <template #header> و <slot> در AuthenticatedLayout.vue
        مطابق با طرح شما نیست. پس لازم است AuthenticatedLayout.vue را به یک EmptyLayout
        تبدیل کنیم یا محتوای آن را خالی کنیم تا با طرح جدید ما تداخل نداشته باشد.
        اینجا فرض بر این است که <AuthenticatedLayout> فقط یک wrapper ساده است.
    -->
    <AuthenticatedLayout>
        <template #header>
            <!-- این بخش header در AuthenticatedLayout.vue دیگر استفاده نمی شود -->
        </template>

        <!-- هدر جدید شبیه به PriceList.vue -->
        <header class="bg-gradient-to-br from-primaryPurple via-primaryRed to-primaryGreen px-4 sm:px-6 md:p-10 py-6 md:py-10 text-center text-white rounded-b-3xl shadow-xl">
            <div class="flex items-center justify-center text-xs sm:text-sm md:text-lg mb-2">
                <span>پنل مدیریت قیمت‌ها</span>
                <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 sm:mr-3 h-4 w-4 sm:h-5 sm:w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <div class="text-xs sm:text-sm md:text-base mb-4">
                تاریخ امروز: {{ shamsiDate }}
            </div>
            <h1 class="text-xl sm:text-2xl md:text-4xl font-extrabold">مدیریت محصولات و قیمت‌ها</h1>
            <div class="mt-4 md:mt-6">
                <a href="/" target="_blank" class="inline-block px-4 sm:px-8 py-2 sm:py-3 bg-blue-600 text-white font-bold text-xs sm:text-sm md:text-base rounded-full hover:shadow-lg transform hover:scale-105 transition-all duration-300 border-2 border-blue-700">
                    📊 مشاهده لیست قیمت‌ها
                </a>
            </div>
        </header>

        <div class="w-full mx-auto p-3 sm:p-4 md:p-6 lg:p-8" dir="rtl">
            <!-- <--- لودر اصلی برای بارگذاری اولیه -->
            <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white shadow-xl rounded-lg">
                <svg class="animate-spin h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-lg text-gray-600">در حال بارگذاری پنل ادمین...</p>
            </div>

            <!-- <--- محتوای صفحه، فقط زمانی که isLoading == false است نمایش داده می‌شود -->
            <div v-else class="space-y-8">
                <!-- بخش مدیریت محصولات و مکان ها (لیست های جهانی) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 lg:gap-8">
                    <!-- مدیریت محصولات (لیست جهانی) -->
                    <div class="bg-white overflow-hidden shadow-xl rounded-lg p-2 sm:p-3 md:p-4 lg:p-6">
                        <h3 class="font-bold text-base sm:text-lg md:text-xl lg:text-2xl mb-3 md:mb-4 lg:mb-6">
                            <span>محصولات موجود</span>
                        </h3>
                        <ul class="space-y-2 md:space-y-3 lg:space-y-4 max-h-96 overflow-y-auto pr-2">
                            <li v-for="product in allProducts" :key="product.id"
                                class="flex items-center justify-between p-2 sm:p-3 md:p-4 lg:p-5 rounded-lg transition-all duration-200 border-l-4 gap-2 hover:shadow-md"
                                :class="{ 'border-blue-600 bg-blue-50 font-semibold': isProductInDailySettings(product.id), 'border-gray-200 bg-gray-50': !isProductInDailySettings(product.id) }"
                            >
                                <div class="flex items-center gap-2 md:gap-3 lg:gap-4 flex-1 min-w-0">
                                    <div v-if="product.image" class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 rounded-lg overflow-hidden border-2 flex-shrink-0">
                                        <img :src="`/storage/${product.image}`" alt="" class="w-full h-full object-cover">
                                    </div>
                                    <div v-else class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 rounded-lg bg-gray-300 flex items-center justify-center text-xs text-gray-600 flex-shrink-0">
                                        عکس
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-xs sm:text-sm md:text-base lg:text-lg truncate">{{ product.name }}</div>
                                        <div v-if="product.color" class="flex items-center gap-1.5 text-xs sm:text-xs md:text-sm text-gray-600 mt-0.5">
                                            <span :style="{ background: product.color }" class="w-2.5 h-2.5 sm:w-3 sm:h-3 md:w-4 md:h-4 lg:w-4 lg:h-4 rounded border"></span>
                                            <span class="font-mono hidden sm:inline">{{ product.color }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <button v-if="!isProductInDailySettings(product.id)"
                                        @click="addProductToToday(product)" type="button"
                                        class="px-2 sm:px-3 md:px-4 lg:px-4 py-1.5 sm:py-2 md:py-2 lg:py-2.5 bg-primaryPurple text-white rounded-lg text-xs sm:text-xs md:text-sm lg:text-sm font-medium whitespace-nowrap hover:bg-primaryPurple/80 transition-colors">
                                        + امروز
                                    </button>
                                    <button v-else
                                        @click="removeProductFromToday(product.id)" type="button"
                                        class="px-2 sm:px-3 md:px-4 lg:px-4 py-1.5 sm:py-2 md:py-2 lg:py-2.5 bg-gray-400 text-white rounded-lg text-xs sm:text-xs md:text-sm lg:text-sm font-medium whitespace-nowrap hover:bg-gray-500 transition-colors">
                                        - امروز
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- مدیریت مکان‌ها (لیست جهانی) -->
                    <div class="bg-white overflow-hidden shadow-xl rounded-lg p-2 sm:p-3 md:p-4 lg:p-6">
                        <h3 class="font-bold text-base sm:text-lg md:text-xl lg:text-2xl mb-3 md:mb-4 lg:mb-6">
                            مکان‌های موجود
                        </h3>
                        <div v-if="addLocationStatus" class="mb-3 md:mb-4 text-xs sm:text-xs md:text-sm text-center p-2 md:p-3 rounded-lg"
                            :class="{ 'bg-green-100 text-green-700': addLocationStatus.type === 'success', 'bg-red-100 text-red-700': addLocationStatus.type === 'error', 'bg-yellow-100 text-yellow-700': addLocationStatus.type === 'loading' }">
                            {{ addLocationStatus.text }}
                        </div>
                        <ul class="space-y-2 md:space-y-3 lg:space-y-4 max-h-96 overflow-y-auto pr-2">
                            <li v-for="loc in allLocations" :key="loc.id"
                                class="flex items-center justify-between p-2 sm:p-3 md:p-4 lg:p-5 rounded-lg transition-all duration-200 border-l-4 gap-2 hover:shadow-md"
                                :class="{ 'border-blue-600 bg-blue-50 font-semibold': isLocationInDailySettings(loc.id), 'border-gray-200 bg-gray-50': !isLocationInDailySettings(loc.id) }"
                            >
                                <div class="flex-1 min-w-0 flex flex-col">
                                    <span class="font-semibold text-xs sm:text-sm md:text-base lg:text-lg break-words">{{ loc.name }}</span>
                                    <span class="text-xs sm:text-xs md:text-sm text-gray-600">{{ loc.currency }}</span>
                                </div>
                                <div class="flex-shrink-0">
                                    <button v-if="!isLocationInDailySettings(loc.id)"
                                        @click="addLocationToToday(loc)" type="button"
                                        class="px-2 sm:px-3 md:px-4 lg:px-4 py-1.5 sm:py-2 md:py-2 lg:py-2.5 bg-primaryPurple text-white rounded-lg text-xs sm:text-xs md:text-sm lg:text-sm font-medium whitespace-nowrap hover:bg-primaryPurple/80 transition-colors">
                                        + امروز
                                    </button>
                                    <button v-else
                                        @click="removeLocationFromToday(loc.id)" type="button"
                                        class="px-2 sm:px-3 md:px-4 lg:px-4 py-1.5 sm:py-2 md:py-2 lg:py-2.5 bg-gray-400 text-white rounded-lg text-xs sm:text-xs md:text-sm lg:text-sm font-medium whitespace-nowrap hover:bg-gray-500 transition-colors">
                                        - امروز
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ماتریس ثبت/بروزرسانی قیمت‌ها (فقط برای محصولات و مکان‌های فعال امروز) -->
                <div class="bg-white overflow-hidden shadow-xl rounded-lg p-3 sm:p-4 md:p-6 mt-6 md:mt-8">
                    <h3 class="font-bold text-base sm:text-lg md:text-xl mb-3 md:mb-4">قیمت‌های محصولات فعال امروز</h3>
                    <form @submit.prevent="submitPrices">
                        <div v-if="!productsForPricing.length && !locationsForPricing.length" class="text-center text-gray-500 py-6 sm:py-8">
                            هیچ محصول یا مکانی برای قیمت‌گذاری امروز فعال نشده است.
                        </div>
                        <div v-else-if="!productsForPricing.length" class="text-center text-gray-500 py-6 sm:py-8">
                            هیچ محصولی برای قیمت‌گذاری امروز فعال نشده است.
                        </div>
                        <div v-else-if="!locationsForPricing.length" class="text-center text-gray-500 py-6 sm:py-8">
                            هیچ مکانی برای قیمت‌گذاری امروز فعال نشده است.
                        </div>

                        <div v-else class="space-y-4 md:space-y-6">
                            <div v-for="product in productsForPricing" :key="product.id" :style="{ backgroundColor: product.color ? hexToRgba(product.color, 0.05) : '', borderLeftColor: product.color || '#ccc' }" class="p-3 sm:p-4 md:p-6 border-2 border-l-4 rounded-lg shadow-sm">
                                <div class="flex items-center gap-2 sm:gap-3 md:gap-4 mb-3 md:mb-4 pb-3 md:pb-4 border-b">
                                    <div v-if="product.image" class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded overflow-hidden border-2 flex-shrink-0">
                                        <img :src="`/storage/${product.image}`" alt="" class="w-full h-full object-cover">
                                    </div>
                                    <div v-else class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded bg-gray-200 flex items-center justify-center text-xs text-gray-400 flex-shrink-0 border-2">
                                        عکس
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-sm sm:text-base md:text-lg truncate">{{ product.name }}</h4>
                                        <div v-if="product.color" class="flex items-center gap-2 mt-1">
                                            <span :style="{ background: product.color }" class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 rounded border-2" :title="`رنگ: ${product.color}`"></span>
                                            <span class="font-mono text-xs sm:text-sm text-gray-600">{{ product.color }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4">
                                    <div v-for="loc in locationsForPricing" :key="loc.id" class="bg-white rounded-lg p-3 md:p-4 border border-gray-200 hover:shadow-md transition-shadow">
                                        <label class="block font-bold text-sm md:text-base text-gray-800 mb-3">{{ loc.name }} <span class="text-xs text-gray-500">({{ loc.currency }})</span></label>
                                        <template v-if="pricesMap.has(`${product.id}_${loc.id}`)">
                                            <div class="space-y-2.5 md:space-y-3">
                                                <div>
                                                    <label class="block text-xs md:text-sm font-medium text-gray-600 mb-1.5">قیمت کمینه</label>
                                                    <input type="number" placeholder="مثال: 29000"
                                                        :value="getPriceModel(product.id, loc.id, 'min_price').value"
                                                        @input="getPriceModel(product.id, loc.id, 'min_price').value = $event.target.value"
                                                        class="w-full text-sm md:text-base border border-gray-300 rounded-lg px-3 md:px-4 py-2 md:py-2.5 focus:outline-none focus:ring-2 focus:ring-primaryPurple focus:border-transparent">
                                                </div>
                                                <div>
                                                    <label class="block text-xs md:text-sm font-medium text-gray-600 mb-1.5">قیمت بیشینه</label>
                                                    <input type="number" placeholder="مثال: 32000"
                                                        :value="getPriceModel(product.id, loc.id, 'max_price').value"
                                                        @input="getPriceModel(product.id, loc.id, 'max_price').value = $event.target.value"
                                                        class="w-full text-sm md:text-base border border-gray-300 rounded-lg px-3 md:px-4 py-2 md:py-2.5 focus:outline-none focus:ring-2 focus:ring-primaryPurple focus:border-transparent">
                                                </div>
                                            </div>
                                        </template>
                                        <div v-else class="flex items-center gap-2 text-gray-400 text-sm">
                                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span>(بارگذاری...)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="productsForPricing.length && locationsForPricing.length" class="flex flex-col sm:flex-row items-center justify-end gap-2 mt-4 md:mt-6">
                            <button type="submit" class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-blue-600 text-white font-medium text-sm sm:text-base rounded-md hover:bg-blue-700 transition-colors duration-200">
                                ✓ ثبت/بروزرسانی قیمت‌ها
                            </button>
                        </div>
                        <div v-if="statusMessage" class="mt-3 md:mt-4 text-center p-2 sm:p-3 rounded-md text-xs sm:text-sm"
                            :class="{ 'bg-green-100 text-green-700': statusMessage.type === 'success', 'bg-red-100 text-red-700': statusMessage.type === 'error', 'bg-yellow-100 text-yellow-700': statusMessage.type === 'loading' }">
                            {{ statusMessage.text }}
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- <--- مودال تایید حذف دائمی (بدون تغییر) -->
        <TransitionRoot as="template" :show="showPermanentDeleteConfirmModal">
            <Dialog as="div" class="relative z-10" @close="showPermanentDeleteConfirmModal = false">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto" dir="rtl">
                    <div class="flex min-h-full items-end justify-center p-3 sm:p-4 text-center sm:items-center sm:p-0">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-300"
                            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to="opacity-100 translate-y-0 sm:scale-100"
                            leave="ease-in duration-200"
                            leave-from="opacity-100 translate-y-0 sm:scale-100"
                            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <DialogPanel
                                class="relative transform overflow-hidden rounded-lg bg-white px-3 sm:px-4 pb-3 sm:pb-4 pt-4 sm:pt-5 text-right shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                            >
                                <div>
                                    <div class="mx-auto flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-red-100">
                                        <svg class="h-5 w-5 sm:h-6 sm:w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.948 3.374c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-5">
                                        <DialogTitle as="h3" class="text-sm sm:text-base font-semibold leading-6 text-gray-900">
                                            حذف دائمی {{ permanentDeleteType === 'product' ? 'محصول' : 'مکان' }} "{{ itemToDeletePermanently?.name }}"
                                        </DialogTitle>
                                        <div class="mt-2">
                                            <p class="text-xs sm:text-sm text-gray-500">
                                                آیا مطمئن هستید که می‌خواهید این {{ permanentDeleteType === 'product' ? 'محصول' : 'مکان' }} را
                                                **برای همیشه از دیتابیس حذف کنید؟** تمام قیمت‌های مرتبط با آن نیز حذف خواهند شد.
                                                این عمل قابل بازگشت نیست.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 sm:mt-5 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                    <button
                                        type="button"
                                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 sm:col-start-2"
                                        @click="confirmPermanentDelete()"
                                    >
                                        بله، حذف دائمی
                                    </button>
                                    <button
                                        type="button"
                                        class="mt-2 sm:mt-0 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-xs sm:text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1"
                                        @click="showPermanentDeleteConfirmModal = false"
                                    >
                                        انصراف
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

    </AuthenticatedLayout>
</template>

<style scoped>
/* Product entries with colored left border */
.price-entry-card {
    transition: all 0.2s ease;
}

.price-entry-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Input styling */
input[type="number"] {
    font-size: 0.9rem;
}

input[type="number"]:focus {
    border-color: var(--primary-purple, #7d00fc);
    box-shadow: 0 0 0 3px rgba(125, 0, 252, 0.1);
}

/* Product grid responsive */
@media (max-width: 1024px) {
    .grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>


