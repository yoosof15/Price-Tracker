<script setup>
import { ref, onMounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({ summary: Object });

const todayPrices = ref([]);
const isLoading = ref(true);
const selectedTrendProduct = ref(null);
const priceHistory = ref({});
const locationsList = ref([]);
const productsList = ref([]);

const page = usePage();

onMounted(async () => {
  try {
    // Fetch today's prices
    const { data: prices } = await axios.get('/api/prices/today');
    todayPrices.value = Array.isArray(prices) ? prices : [];

    // Fetch locations and products for UI
    const { data: locs } = await axios.get('/api/locations');
    locationsList.value = Array.isArray(locs) ? locs : [];

    const { data: prods } = await axios.get('/admin/api/products');
    productsList.value = Array.isArray(prods) ? prods : [];

    // Fetch history for first few products
    for (let i = 0; i < Math.min(todayPrices.value.length, 3); i++) {
      const pid = todayPrices.value[i].id;
      try {
        const res = await axios.get(`/api/prices/history/${pid}`);
        priceHistory.value[pid] = Array.isArray(res.data) ? res.data : [];
      } catch (e) {
        priceHistory.value[pid] = [];
      }
    }
  } catch (e) {
    console.error('Failed to load dashboard data', e);
  } finally {
    isLoading.value = false;
  }
});

// computed stats
const productsCount = computed(() => props.summary?.products_count ?? 0);
const locationsCount = computed(() => props.summary?.locations_count ?? 0);
const usersCount = computed(() => props.summary?.users_count ?? 0);

// active products today
const activeProductsToday = computed(() => todayPrices.value.length);

// distribution: products by number of locations
const productDistribution = computed(() => {
  return todayPrices.value
    .map(p => ({
      id: p.id,
      name: p.product_name || `محصول ${p.id}`,
      locCount: p.prices ? Object.keys(p.prices).length : 0,
      color: p.color || '#60A5FA',
    }))
    .sort((a, b) => b.locCount - a.locCount)
    .slice(0, 10);
});

// price range stats
const priceRangeStats = computed(() => {
  const all = [];
  todayPrices.value.forEach(p => {
    if (p.prices) {
      Object.values(p.prices).forEach(pr => {
        if (pr.min_price) all.push(pr.min_price);
        if (pr.max_price) all.push(pr.max_price);
      });
    }
  });
  if (all.length === 0) return { min: 0, max: 0, avg: 0 };
  return {
    min: Math.min(...all),
    max: Math.max(...all),
    avg: Math.round(all.reduce((s, v) => s + v, 0) / all.length),
  };
});

// locations by activity
const locationActivity = computed(() => {
  const map = {};
  todayPrices.value.forEach(p => {
    if (p.prices) {
      Object.keys(p.prices).forEach(lid => {
        map[lid] = (map[lid] || 0) + 1;
      });
    }
  });
  return locationsList.value
    .filter(l => l.id in map)
    .map(l => ({
      id: l.id,
      name: l.name || `مکان ${l.id}`,
      active: map[l.id] || 0,
    }))
    .sort((a, b) => b.active - a.active)
    .slice(0, 8);
});

</script>

<template>
  <Head title="داشبورد - پنل ادمین" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-semibold leading-tight text-gray-800">داشبورد</h2>
    </template>

    <div class="py-6" dir="rtl">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Summary Cards -->
        <div class="bg-white overflow-hidden shadow-xl rounded-lg p-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="p-4 bg-red-50 rounded-lg border border-red-100">
              <div class="text-sm text-gray-600 font-medium">کل محصولات</div>
              <div class="text-3xl font-bold text-red-600 mt-1">{{ productsCount }}</div>
              <div class="text-xs text-gray-500 mt-2">تعداد کل محصولات</div>
            </div>
            <div class="p-4 bg-green-50 rounded-lg border border-green-100">
              <div class="text-sm text-gray-600 font-medium">کل مکان‌ها</div>
              <div class="text-3xl font-bold text-green-600 mt-1">{{ locationsCount }}</div>
              <div class="text-xs text-gray-500 mt-2">تعداد کل مکان‌ها</div>
            </div>
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
              <div class="text-sm text-gray-600 font-medium">فعال امروز</div>
              <div class="text-3xl font-bold text-blue-600 mt-1">{{ activeProductsToday }}</div>
              <div class="text-xs text-gray-500 mt-2">محصولات فعال برای ثبت قیمت</div>
            </div>
            <div class="p-4 bg-purple-50 rounded-lg border border-purple-100">
              <div class="text-sm text-gray-600 font-medium">کل کاربران</div>
              <div class="text-3xl font-bold text-purple-600 mt-1">{{ usersCount }}</div>
              <div class="text-xs text-gray-500 mt-2">کاربران فعال سیستم</div>
            </div>
          </div>
        </div>

        <!-- Price Range Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" v-if="todayPrices.length > 0">
          <div class="bg-white overflow-hidden shadow rounded-lg p-6">
            <div class="flex items-center">
              <div class="flex-1">
                <div class="text-sm text-gray-600">حداقل قیمت امروز</div>
                <div class="text-2xl font-bold text-gray-900">{{ priceRangeStats.min.toLocaleString() }}</div>
              </div>
              <div class="text-3xl text-red-300">📉</div>
            </div>
          </div>
          <div class="bg-white overflow-hidden shadow rounded-lg p-6">
            <div class="flex items-center">
              <div class="flex-1">
                <div class="text-sm text-gray-600">متوسط قیمت امروز</div>
                <div class="text-2xl font-bold text-gray-900">{{ priceRangeStats.avg.toLocaleString() }}</div>
              </div>
              <div class="text-3xl text-blue-300">📊</div>
            </div>
          </div>
          <div class="bg-white overflow-hidden shadow rounded-lg p-6">
            <div class="flex items-center">
              <div class="flex-1">
                <div class="text-sm text-gray-600">حداکثر قیمت امروز</div>
                <div class="text-2xl font-bold text-gray-900">{{ priceRangeStats.max.toLocaleString() }}</div>
              </div>
              <div class="text-3xl text-green-300">📈</div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="bg-white overflow-hidden shadow-xl rounded-lg p-6">
          <h3 class="font-bold text-lg mb-6 text-gray-800">تحلیل‌ها و نمودارها</h3>

          <div v-if="isLoading" class="text-center py-12 text-gray-500">در حال بارگذاری...</div>

          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Product Distribution Chart -->
            <div class="border rounded-lg p-6 bg-gray-50">
              <h4 class="font-semibold text-gray-700 mb-4">توزیع محصولات (تعداد مکان‌های فعال)</h4>
              <div v-if="productDistribution.length === 0" class="text-center py-12 text-gray-400">
                داده‌ای برای نمایش وجود ندارد
              </div>
              <div v-else class="space-y-3">
                <div v-for="(item, idx) in productDistribution.slice(0, 8)" :key="item.id" class="flex items-center gap-3">
                  <div class="flex-1">
                    <div class="text-sm font-medium text-gray-700">{{ item.name }}</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                      <div
                        class="h-2 rounded-full"
                        :style="{ 
                          width: `${(item.locCount / (productDistribution[0]?.locCount || 1)) * 100}%`,
                          backgroundColor: item.color 
                        }"
                      ></div>
                    </div>
                  </div>
                  <span class="text-sm font-semibold text-gray-600 w-8 text-left">{{ item.locCount }}</span>
                </div>
              </div>
            </div>

            <!-- Location Activity Chart -->
            <div class="border rounded-lg p-6 bg-gray-50">
              <h4 class="font-semibold text-gray-700 mb-4">فعالیت مکان‌ها (تعداد محصولات ثبت‌شده)</h4>
              <div v-if="locationActivity.length === 0" class="text-center py-12 text-gray-400">
                داده‌ای برای نمایش وجود ندارد
              </div>
              <div v-else class="space-y-3">
                <div v-for="(loc, idx) in locationActivity" :key="loc.id" class="flex items-center gap-3">
                  <div class="flex-1">
                    <div class="text-sm font-medium text-gray-700">{{ loc.name }}</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                      <div
                        class="h-2 rounded-full bg-cyan-500"
                        :style="{ 
                          width: `${(loc.active / (locationActivity[0]?.active || 1)) * 100}%`
                        }"
                      ></div>
                    </div>
                  </div>
                  <span class="text-sm font-semibold text-gray-600 w-8 text-left">{{ loc.active }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Price Trend Charts for Top Products -->
        <div class="bg-white overflow-hidden shadow-xl rounded-lg p-6" v-if="Object.keys(priceHistory).length > 0">
          <h3 class="font-bold text-lg mb-6 text-gray-800">روند قیمت‌ها</h3>

          <div class="grid grid-cols-1 gap-6">
            <div v-for="(productId, idx) in Object.keys(priceHistory).slice(0, 3)" :key="productId" class="border rounded-lg p-4 bg-gray-50">
              <div class="flex justify-between items-center mb-3">
                <h5 class="font-medium text-gray-700">
                  {{ todayPrices.find(p => p.id == productId)?.product_name || `محصول ${productId}` }}
                </h5>
                <span class="text-xs bg-gray-200 px-2 py-1 rounded text-gray-600">
                  {{ priceHistory[productId].length }} نقطه داده
                </span>
              </div>

              <div v-if="priceHistory[productId].length === 0" class="py-8 text-center text-gray-400">
                داده‌ تاریخی برای این محصول وجود ندارد
              </div>
              <svg v-else viewBox="0 0 700 150" class="w-full h-32">
                <!-- Grid lines -->
                <line x1="30" y1="130" x2="680" y2="130" stroke="#E5E7EB" stroke-width="1" />
                <!-- Trend line -->
                <polyline
                  :points="computeTrendLine(priceHistory[productId])"
                  fill="none"
                  stroke="#3B82F6"
                  stroke-width="2"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                />
                <!-- X axis labels -->
                <g v-for="(item, i) in priceHistory[productId]" :key="i">
                  <text 
                    v-if="i % Math.ceil(priceHistory[productId].length / 5) === 0"
                    :x="30 + (i / (priceHistory[productId].length - 1 || 1)) * 650"
                    y="145"
                    class="text-xs fill-gray-500"
                    style="font-size: 10px; text-anchor: middle"
                  >
                    {{ item.date }}
                  </text>
                </g>
              </svg>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="todayPrices.length === 0 && !isLoading" class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
          <div class="text-2xl mb-2">📌</div>
          <h4 class="font-semibold text-blue-900 mb-2">هیچ قیمتی برای امروز ثبت نشده است</h4>
          <p class="text-blue-700 text-sm">ابتدا قیمت‌ها را ثبت کنید تا نمودارها و آمار‌ها نمایش داده شوند.</p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script>
export default {
  methods: {
    computeTrendLine(items) {
      if (!items || items.length === 0) return '';
      
      // Extract numeric values from each item
      const values = items.map(item => {
        const nums = Object.values(item).filter(v => typeof v === 'number');
        if (nums.length === 0) return 0;
        return Math.round(nums.reduce((s, n) => s + n, 0) / nums.length);
      });

      const max = Math.max(...values);
      const min = Math.min(...values);
      const range = max - min || 1;

      return values
        .map((v, i) => {
          const x = 30 + (i / (values.length - 1 || 1)) * 650;
          const norm = (v - min) / range;
          const y = 130 - (norm * 110);
          return `${x},${y}`;
        })
        .join(' ');
    },
  },
};
</script>

<style scoped>
svg text {
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
}
</style>
