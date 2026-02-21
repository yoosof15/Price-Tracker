<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    locations: Array,
});

const locations = computed(() => props.locations);

const newLocationName = ref('');
const newLocationCurrency = ref('ریال');
const statusMessage = ref({ type: '', text: '' });
const isLoading = ref(false);

const page = usePage();
const authUser = ref(page.props.auth.user);

// -------- RBAC --------
const canViewLocations   = computed(() => authUser.value.can_view_locations);
const canCreateLocation  = computed(() => authUser.value.can_create_location);
const canDeleteLocation  = computed(() => authUser.value.can_delete_location);
const canEditLocation = computed(() => {
    const u = authUser.value || {};
    return !!(
        u.can_update_location ||
        u.can_edit_location ||
        u.can_update_locations ||
        u.can_edit_locations ||
        u.is_super_admin
    );
});

// --- Edit location state ---
const editingLocation = ref(null);
const editLocationName = ref('');
const editLocationCurrency = ref('ریال');
const editLocationStatus = ref({ type: '', text: '' });

function openEditLocation(location) {
    editingLocation.value = location;
    editLocationName.value = location.name;
    editLocationCurrency.value = location.currency || 'ریال';
    editLocationStatus.value = { type: '', text: '' };
}

function cancelEditLocation() {
    editingLocation.value = null;
    editLocationName.value = '';
    editLocationCurrency.value = 'ریال';
    editLocationStatus.value = { type: '', text: '' };
}

async function submitEditLocation() {
    if (!editingLocation.value) return;
    if (!editLocationName.value.trim()) {
        editLocationStatus.value = { type: 'error', text: 'نام مکان نمی‌تواند خالی باشد.' };
        return;
    }
    if (!editLocationCurrency.value.trim()) {
        editLocationStatus.value = { type: 'error', text: 'واحد پول نمی‌تواند خالی باشد.' };
        return;
    }
    editLocationStatus.value = { type: 'loading', text: 'در حال ذخیره تغییرات...' };
    try {
        await axios.put(`/admin/locations/${editingLocation.value.id}`, {
            name: editLocationName.value,
            currency: editLocationCurrency.value,
        });
        editLocationStatus.value = { type: 'success', text: 'مکان با موفقیت بروزرسانی شد.' };
        // close modal and refresh
        cancelEditLocation();
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('خطا در ویرایش مکان:', error);
        const errorMessage = error.response?.data?.message || 'خطا در ذخیره تغییرات.';
        editLocationStatus.value = { type: 'error', text: errorMessage };
    }
}

// -------- افزودن مکان --------
async function handleAddLocation() {
    const locationName = newLocationName.value;

    if (!newLocationCurrency.value.trim()) {
        statusMessage.value = { type: 'error', text: 'واحد پول نمی‌تواند خالی باشد.' };
        return;
    }

    statusMessage.value = { type: 'loading', text: 'در حال افزودن مکان...' };
    try {
        await axios.post('/admin/locations', { 
            name: locationName,
            currency: newLocationCurrency.value
        });
        newLocationName.value = '';
        newLocationCurrency.value = 'ریال';
        statusMessage.value = { type: 'success', text: `مکان "${locationName}" با موفقیت افزوده شد.` };
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('خطا در افزودن مکان:', error);
        const errorMessage =
            error.response?.data?.message || 'خطا در افزودن مکان. (شاید تکراری باشد؟)';
        statusMessage.value = { type: 'error', text: errorMessage };
    }
}

// -------- حذف مکان --------
async function handleDeleteLocation(location) {
    if (!confirm(`آیا از حذف مکان "${location.name}" مطمئن هستید؟`)) {
        return;
    }

    statusMessage.value = { type: 'loading', text: 'در حال حذف مکان...' };
    try {
        await axios.delete(`/admin/locations/${location.id}`);
        statusMessage.value = { type: 'success', text: `مکان "${location.name}" با موفقیت حذف شد.` };
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('خطا در حذف مکان:', error);
        const errorMessage =
            error.response?.data?.message || 'خطا در حذف مکان.';
        statusMessage.value = { type: 'error', text: errorMessage };
    }
}
</script>

<template>
    <Head title="مدیریت مکان‌ها" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                مدیریت مکان‌ها
            </h2>
        </template>

        <div class="py-12" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <div
                    v-if="canCreateLocation"
                    class="bg-white overflow-hidden shadow-xl rounded-lg p-4 sm:p-6"
                >
                    <h3 class="font-bold text-lg mb-6">افزودن مکان جدید</h3>

                    <form @submit.prevent="handleAddLocation" class="space-y-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">نام مکان</label>
                            <input
                                type="text"
                                v-model="newLocationName"
                                placeholder="نام مکان جدید را وارد کنید"
                                class="block w-full text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2"
                            />
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">واحد پول</label>
                            <input
                                type="text"
                                v-model="newLocationCurrency"
                                placeholder="مثلاً: ریال، دلار، یورو"
                                maxlength="20"
                                class="block w-full text-sm border border-gray-300 rounded-md shadow-sm px-3 py-2"
                            />
                        </div>
                        <button
                            type="submit"
                            class="w-full px-4 py-2 bg-green-600 text-white text-sm sm:text-base rounded-md hover:bg-green-700"
                        >
                            افزودن مکان
                        </button>
                    </form>

                    <div
                        v-if="statusMessage.text && statusMessage.type !== 'loading'"
                        class="mt-4 text-center p-2 text-xs sm:text-sm rounded-md"
                        :class="{
                            'bg-green-100 text-green-700': statusMessage.type === 'success',
                            'bg-red-100 text-red-700': statusMessage.type === 'error'
                        }"
                    >
                        {{ statusMessage.text }}
                    </div>
                </div>

                <!-- لیست مکان‌ها -->
                <div
                    v-if="canViewLocations"
                    class="bg-white overflow-hidden shadow-xl rounded-lg p-4 sm:p-6"
                >
                    <h3 class="font-bold text-lg mb-4">لیست مکان‌ها</h3>

                    <!-- Desktop Table View -->
                    <div class="hidden sm:block relative overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm text-right text-gray-700">
                            <thead class="bg-gray-100 uppercase text-gray-600 tracking-wider">
                                <tr>
                                    <th class="py-3 px-3 sm:px-4">#</th>
                                    <th class="py-3 px-3 sm:px-4">نام مکان</th>
                                    <th class="py-3 px-3 sm:px-4">واحد پول</th>
                                    <th
                                            v-if="canDeleteLocation || canEditLocation"
                                            class="py-3 px-3 sm:px-4 text-center"
                                        >
                                            عملیات
                                        </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-if="locations.length === 0">
                                    <td
                                        :colspan="canDeleteLocation ? 4 : 3"
                                        class="text-center py-4 text-gray-500"
                                    >
                                        مکانی یافت نشد.
                                    </td>
                                </tr>

                                <tr
                                    v-for="(location, index) in locations"
                                    :key="location.id"
                                    class="bg-white border-b last:border-0 hover:bg-gray-50"
                                >
                                    <td class="py-4 px-3 sm:px-4">{{ index + 1 }}</td>
                                    <td class="py-4 px-3 sm:px-4">{{ location.name }}</td>
                                    <td class="py-4 px-3 sm:px-4 font-medium text-gray-700">{{ location.currency }}</td>
                                    <td
                                        v-if="canDeleteLocation || canEditLocation"
                                        class="py-4 px-3 sm:px-4 text-center"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <button v-if="canEditLocation" @click="openEditLocation(location)" class="text-blue-600 hover:text-blue-800 text-sm">✎</button>
                                            <button v-if="canDeleteLocation" @click="handleDeleteLocation(location)" class="text-red-500 hover:text-red-700 text-sm">&#x2716;</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="sm:hidden space-y-3">
                        <div v-if="locations.length === 0" class="text-center py-8 text-gray-500">مکانی یافت نشد.</div>
                        <div v-for="(location, index) in locations" :key="location.id" class="border rounded-lg p-3 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">{{ index + 1 }}. {{ location.name }}</div>
                                    <div class="text-xs text-gray-600 mt-1">{{ location.currency }}</div>
                                </div>
                            </div>
                            <div v-if="canDeleteLocation || canEditLocation" class="flex gap-2 justify-end text-xs">
                                <button v-if="canEditLocation" @click="openEditLocation(location)" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">ویرایش</button>
                                <button v-if="canDeleteLocation" @click="handleDeleteLocation(location)" class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600">حذف</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- Edit Location Modal -->
        <div v-if="editingLocation" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-4 sm:p-6 max-h-[90vh] overflow-y-auto" dir="rtl">
                <h3 class="text-base sm:text-lg font-bold mb-4">ویرایش مکان: {{ editingLocation.name }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">نام مکان</label>
                        <input type="text" v-model="editLocationName" class="w-full px-3 py-2 text-sm border rounded" />
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">واحد پول</label>
                        <input type="text" v-model="editLocationCurrency" maxlength="20" class="w-full px-3 py-2 text-sm border rounded" />
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-end gap-2 sm:gap-3">
                    <button @click="cancelEditLocation" type="button" class="px-4 py-2 bg-gray-200 rounded text-sm w-full sm:w-auto">انصراف</button>
                    <button @click="submitEditLocation" type="button" class="px-4 py-2 bg-blue-600 text-white rounded text-sm w-full sm:w-auto" :disabled="editLocationStatus.type === 'loading'">ذخیره</button>
                </div>
                <div v-if="editLocationStatus.text" class="mt-4 p-3 rounded text-xs sm:text-sm" :class="{ 'bg-green-100 text-green-800': editLocationStatus.type === 'success', 'bg-red-100 text-red-800': editLocationStatus.type === 'error', 'bg-yellow-100 text-yellow-800': editLocationStatus.type === 'loading' }">{{ editLocationStatus.text }}</div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
