<script setup>
import { computed } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
  show: Boolean,
  productName: String,
  productColor: String,
  locationName: String,
  locationCurrency: String,
  chartData: {
    type: Array,
    default: () => []
  },
  chartCategories: {
    type: Array,
    default: () => []
  },
  chartSeries: {
    type: Array,
    default: () => []
  },
  locations: {
    type: Array,
    default: () => []
  },
  selectedPeriod: String,
  selectedChartType: String,
  isLoadingChartData: Boolean,
});

const emit = defineEmits(['close', 'update:period', 'update:chartType']);

// Helper function to adjust color opacity
function adjustColorOpacity(color, opacity) {
  if (!color) return null;
  
  // Convert hex to RGB
  const hex = color.replace('#', '');
  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);
  
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

// Options for ApexCharts
const chartOptions = computed(() => ({
  chart: {
    type: props.selectedChartType,
    height: 350,
    toolbar: {
      show: true,
      tools: {
        download: true,
        selection: true,
        zoom: true,
        pan: true,
        reset: true,
      },
      autoSelected: 'zoom'
    },
    zoom: {
      enabled: true
    },
    fontFamily: 'Vazirmatn, sans-serif' // <--- فونت فارسی برای کل نمودار
  },
  xaxis: {
    categories: props.chartCategories,
    labels: {
        rotate: -45,
        rotateAlways: true,
        style: {
            fontFamily: 'Vazirmatn, sans-serif',
        },
    }
  },
  yaxis: {
    labels: {
        formatter: function (value) {
            return new Intl.NumberFormat('fa-IR').format(value); // <--- فرمت دهی فارسی و هزارگان
        },
        style: {
            fontFamily: 'Vazirmatn, sans-serif',
        },
    },
    title: {
      text: `قیمت (${props.locationCurrency || 'ریال'})`,
        style: {
            fontFamily: 'Vazirmatn, sans-serif',
        },
    }
  },
  tooltip: {
    x: {
      formatter: function(value) {
        return value; // تاریخ شمسی خودش فرمت شده میاد
      },
    },
    y: {
      formatter: function(value) {
        return value ? new Intl.NumberFormat('fa-IR').format(value) + ` ${props.locationCurrency || 'ریال'}` : '-';
      }
    },
    style: {
        fontFamily: 'Vazirmatn, sans-serif',
    },
  },
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth'
  },
  grid: {
    row: {
      colors: ['#f3f4f5', '#fff'],
      opacity: 1
    }
  },
  noData: {
    text: "داده‌ای برای نمایش وجود ندارد...",
    align: 'center',
    verticalAlign: 'middle',
    offsetY: -10,
    style: {
      color: "#999",
      fontSize: '14px',
      fontFamily: "Vazirmatn, sans-serif"
    }
  },
  // <--- رنگ‌های سفارشی شما
  colors: props.productColor 
    ? [
        props.productColor, // رنگ محصول برای کمینه
        adjustColorOpacity(props.productColor, 0.6), // رنگ محصول با opacity کمتر برای بیشینه
      ]
    : ['#ff4b2b', '#7d00fc', '#71d957', '#a0a0a0', '#4CAF50', '#FF9800'], // برای min/max هر لوکیشن
}));

const periodOptions = [
  { value: 'daily', label: 'روزانه' },
  { value: 'weekly', label: 'هفتگی' },
  { value: 'monthly', label: 'ماهانه' },
  { value: 'yearly', label: 'سالیانه' }
];

const chartTypeOptions = [
  { value: 'line', label: 'خطی' },
  { value: 'bar', label: 'میله‌ای' },
  // { value: 'rangeArea', label: 'محدوده' } // RangeArea requires different series data structure
];
</script>


<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-10" @close="emit('close')">
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

      <div class="fixed inset-0 z-10 overflow-y-auto" dir="rtl"> <!-- <--- dir="rtl" به مودال هم اضافه شد -->
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
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
              class="relative transform overflow-hidden rounded-lg bg-white px-3 sm:px-4 pb-4 pt-4 sm:pt-5 text-right shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-5xl sm:p-6" <!-- <--- text-right اضافه شد -->
              <div>
                <div class="mt-0 sm:mt-3 text-center sm:mt-0 sm:text-right">
                  <DialogTitle as="h3" class="text-base sm:text-lg font-semibold leading-6 text-gray-900 mb-2">
                    {{ `نمودار قیمت ${productName}` }}
                  </DialogTitle>
                  <div class="text-xs sm:text-sm text-gray-600 mb-4 pb-4 border-b">
                    {{ locationName ? `نمودار قیمت ${productName} - ${locationName}` : `نمودار قیمت ${productName}` }}
                  </div>
                  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
                    <!-- Period Selection -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                      <span class="text-xs sm:text-sm font-medium text-gray-700 whitespace-nowrap">زمانی:</span>
                      <div class="flex flex-wrap gap-1 sm:gap-0 w-full sm:w-auto">
                        <button
                          v-for="option in periodOptions"
                          :key="option.value"
                          type="button"
                          @click="emit('update:period', option.value)"
                          :class="[
                            'px-2 sm:px-3 py-1 text-xs sm:text-sm font-medium text-gray-900 border border-gray-300 rounded-none hover:bg-gray-100 transition-colors',
                            {'bg-blue-500 text-white hover:bg-blue-600 border-blue-500': selectedPeriod === option.value},
                            'first:rounded-r-md last:rounded-l-md sm:first:rounded-l-md sm:last:rounded-r-md'
                          ]"
                        >
                          {{ option.label }}
                        </button>
                      </div>
                    </div>

                    <!-- Chart Type Selection -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                      <span class="text-xs sm:text-sm font-medium text-gray-700 whitespace-nowrap">نوع:</span>
                      <div class="flex flex-wrap gap-1 sm:gap-0 w-full sm:w-auto">
                        <button
                          v-for="option in chartTypeOptions"
                          :key="option.value"
                          type="button"
                          @click="emit('update:chartType', option.value)"
                          :class="[
                            'px-2 sm:px-3 py-1 text-xs sm:text-sm font-medium text-gray-900 border border-gray-300 rounded-none hover:bg-gray-100 transition-colors',
                            {'bg-blue-500 text-white hover:bg-blue-600 border-blue-500': selectedChartType === option.value},
                            'first:rounded-r-md last:rounded-l-md sm:first:rounded-l-md sm:last:rounded-r-md'
                          ]"
                        >
                          {{ option.label }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- <--- اصلاح جایگاه لودر و نمودار responsive -->
                  <div class="relative w-full min-h-[300px] sm:min-h-[400px] flex items-center justify-center border-t pt-4 mt-4 bg-gray-50 rounded-md">
                      <div v-if="isLoadingChartData" class="flex flex-col items-center justify-center">
                          <svg class="animate-spin h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                          <p class="mt-4 text-sm sm:text-lg text-gray-600">در حال بارگذاری نمودار...</p>
                      </div>
                      <div v-else class="w-full h-full">
                        <VueApexCharts
                          :type="selectedChartType"
                          :height="300"
                          :options="chartOptions"
                          :series="chartSeries"
                        />
                      </div>
                  </div>
                </div>
              </div>
              <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                  @click="emit('close')"
                >
                  بستن
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>


<style>
/* فونت وزیر برای نمودار */
.apexcharts-tooltip, .apexcharts-legend-text, .apexcharts-yaxis-label, .apexcharts-xaxis-label, .apexcharts-title-text {
    font-family: 'Vazirmatn', sans-serif !important;
}
</style>
