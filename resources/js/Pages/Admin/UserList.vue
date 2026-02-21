<script setup>
import { ref, onMounted, computed  } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3'; // <--- router را import کن
import axios from 'axios';

// <--- props را برای دریافت کاربران اضافه کن
const props = defineProps({
    users: Array, // لیست کاربران از بک اند
    allRoles: Array, // <--- لیست نقش ها را هم از بک اند می گیریم (جدید)
});
// --- State Variables ---
const users = computed(() => props.users);
const allRoles = ref(props.allRoles);
const newUser = ref({
    name: '',
    phone: '',
    password: '',
    role_id: null, // نقش پیش فرض
});
const statusMessage = ref({ type: '', text: '' });
const addUserStatus = ref({ type: '', text: '' });
const isLoading = ref(false); // لودینگ کلی صفحه

// <--- دسترسی به اطلاعات کاربر لاگین شده
const page = usePage();
const authUser = ref(page.props.auth.user);

// RBAC helpers
const canEditUser = computed(() => {
    const u = authUser.value || {};
    return !!(u.can_edit_user || u.can_update_user || u.is_super_admin);
});

// --- Edit user state ---
const editingUser = ref(null);
const editUserName = ref('');
const editUserPhone = ref('');
const editUserPassword = ref('');
const editUserRoleId = ref(null);
const editUserStatus = ref({ type: '', text: '' });

function openEditUser(user) {
    editingUser.value = user;
    editUserName.value = user.name;
    editUserPhone.value = user.phone;
    editUserPassword.value = '';
    editUserRoleId.value = user.role_id;
    editUserStatus.value = { type: '', text: '' };
}

function cancelEditUser() {
    editingUser.value = null;
    editUserName.value = '';
    editUserPhone.value = '';
    editUserPassword.value = '';
    editUserRoleId.value = null;
    editUserStatus.value = { type: '', text: '' };
}

async function submitEditUser() {
    if (!editingUser.value) return;
    if (!editUserName.value.trim() || !editUserPhone.value.trim()) {
        editUserStatus.value = { type: 'error', text: 'نام و شماره موبایل نمی‌توانند خالی باشند.' };
        return;
    }
    editUserStatus.value = { type: 'loading', text: 'در حال ذخیره تغییرات...' };
    try {
        await axios.put(`/admin/users/${editingUser.value.id}`, {
            name: editUserName.value,
            phone: editUserPhone.value,
            password: editUserPassword.value || null,
            role_id: editUserRoleId.value,
        });
        editUserStatus.value = { type: 'success', text: 'کاربر با موفقیت بروزرسانی شد.' };
        cancelEditUser();
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('خطا در ویرایش کاربر:', error);
        const errorMessage = error.response?.data?.message || 'خطا در ذخیره تغییرات.';
        editUserStatus.value = { type: 'error', text: errorMessage };
    }
}


// --- Functions ---
async function handleAddUser() {
    addUserStatus.value = { type: 'loading', text: 'در حال ایجاد کاربر...' };
    try {
        await axios.post('/admin/users', newUser.value);
        addUserStatus.value = { type: 'success', text: `کاربر ${newUser.value.name} با موفقیت ایجاد شد.` };
        newUser.value = { name: '', phone: '', password: '', role_id: null }; // <--- ریست فرم
        router.reload({ preserveScroll: true }); // <--- استفاده از Inertia.reload()
    } catch (error) {
        console.error("خطا در ایجاد کاربر:", error);
        const errorMessage = error.response?.data?.message || 'خطا در ایجاد کاربر.';
        addUserStatus.value = { type: 'error', text: errorMessage };
    }
}

async function handleDeleteUser(user) {
    if (!confirm(`آیا از حذف کاربر "${user.name}" ( ${user.phone} ) مطمئن هستید؟ این عمل قابل بازگشت نیست.`)) {
        return;
    }
    statusMessage.value = { type: 'loading', text: 'در حال حذف کاربر...' };
    try {
        await axios.delete(`/admin/users/${user.id}`);
        statusMessage.value = { type: 'success', text: `کاربر ${user.name} با موفقیت حذف شد.` };
        router.reload({ preserveScroll: true }); // <--- استفاده از Inertia.reload()
    } catch (error) {
        console.error("خطا در حذف کاربر:", error);
        const errorMessage = error.response?.data?.message || 'خطا در حذف کاربر.';
        statusMessage.value = { type: 'error', text: errorMessage };
    }
}


// <--- تابع جدید: ویرایش نقش کاربر
async function handleUpdateUserRole(user, newRoleId) {
    statusMessage.value = { type: 'loading', text: `در حال تغییر نقش کاربر ${user.name}...` };
    try {
        await axios.put(`/admin/users/${user.id}/role`, { role_id: newRoleId });
        statusMessage.value = { type: 'success', text: `نقش کاربر ${user.name} با موفقیت تغییر یافت.` };
        router.reload({ preserveScroll: true }); // <--- بروزرسانی لیست
    } catch (error) {
        console.error("خطا در تغییر نقش کاربر:", error);
        const errorMessage = error.response?.data?.message || 'خطا در تغییر نقش کاربر.';
        statusMessage.value = { type: 'error', text: errorMessage };
    }
}


onMounted(() => {
    // debug: log auth user and computed permission to browser console
    try {
        // stringify to avoid reactive circulars
        console.log('authUser props:', JSON.parse(JSON.stringify(page.props.auth.user)));
        console.log('canEditUser computed:', canEditUser.value);
    } catch (e) {
        console.log('UserList debug:', page.props.auth.user, canEditUser.value);
    }
});

// <--- تابع کمکی برای نمایش نقش کاربر
const displayRole = (role) => {
    return role ? role.display_name : 'نامشخص'; // <--- تغییر: حالا role یک آبجکت است
};
</script>

<template>
    <Head title="مدیریت کاربران" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center" dir="rtl">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">مدیریت کاربران</h2>
                <span class="text-sm text-gray-500">{{ authUser.name }} ({{ displayRole(authUser.role) }})</span>
            </div>
        </template>

        <div class="py-12" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- <--- لودر اصلی برای بارگذاری اولیه -->
                <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white shadow-xl rounded-lg">
                    <svg class="animate-spin h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-4 text-lg text-gray-600">در حال بارگذاری لیست کاربران...</p>
                </div>

                <!-- <--- محتوای صفحه، فقط زمانی که isLoading == false است نمایش داده می‌شود -->
                <div v-else class="space-y-8">
                    <!-- فرم ایجاد کاربر جدید -->
                    <div class="bg-white overflow-hidden shadow-xl rounded-lg p-4 sm:p-6">
                        <h3 class="font-bold text-lg mb-4">ایجاد کاربر جدید</h3>
                        <form @submit.prevent="handleAddUser" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                            <div>
                                <label for="name" class="block font-medium text-xs sm:text-sm text-gray-700 mb-1">نام</label>
                                <input type="text" id="name" v-model="newUser.name" class="mt-1 block w-full text-sm border border-gray-300 rounded-md shadow-sm" required>
                            </div>
                            <div>
                                <label for="phone" class="block font-medium text-xs sm:text-sm text-gray-700 mb-1">شماره موبایل</label>
                                <input type="tel" id="phone" v-model="newUser.phone" class="mt-1 block w-full text-sm border border-gray-300 rounded-md shadow-sm" required>
                            </div>
                            <div>
                                <label for="password" class="block font-medium text-xs sm:text-sm text-gray-700 mb-1">رمز عبور</label>
                                <input type="password" id="password" v-model="newUser.password" class="mt-1 block w-full text-sm border border-gray-300 rounded-md shadow-sm" required>
                            </div>
                            <div>
                                <label for="role_id" class="block font-medium text-xs sm:text-sm text-gray-700 mb-1">نقش</label>
                                <select id="role_id" v-model="newUser.role_id" class="mt-1 block w-full text-sm border border-gray-300 rounded-md shadow-sm" required>
                                    <option :value="null" disabled>نقش را انتخاب کنید</option>
                                    <option v-for="role in allRoles" :key="role.id" :value="role.id">
                                        {{ role.display_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                                    ایجاد کاربر
                                </button>
                            </div>
                        </form>
                        <div v-if="addUserStatus.text" class="mt-4 text-center p-2 text-xs sm:text-sm rounded-md"
                            :class="{ 'bg-green-100 text-green-700': addUserStatus.type === 'success', 'bg-red-100 text-red-700': addUserStatus.type === 'error', 'bg-yellow-100 text-yellow-700': addUserStatus.type === 'loading' }">
                            {{ addUserStatus.text }}
                        </div>
                    </div>

                    <!-- لیست کاربران -->
                    <div class="bg-white overflow-hidden shadow-xl rounded-lg p-4 sm:p-6">
                        <h3 class="font-bold text-lg mb-4">لیست کاربران</h3>
                        <div v-if="statusMessage.text" class="mb-4 text-center p-2 text-xs sm:text-sm rounded-md"
                            :class="{ 'bg-green-100 text-green-700': statusMessage.type === 'success', 'bg-red-100 text-red-700': statusMessage.type === 'error', 'bg-yellow-100 text-yellow-700': statusMessage.type === 'loading' }">
                            {{ statusMessage.text }}
                        </div>
                        
                        <!-- Desktop Table View -->
                        <div class="hidden sm:block relative overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm text-right text-gray-700">
                                <thead class="bg-gray-100 uppercase text-gray-600 tracking-wider">
                                    <tr>
                                        <th scope="col" class="py-3 px-3 sm:px-4 text-right">نام</th>
                                        <th scope="col" class="py-3 px-3 sm:px-4 text-right">شماره موبایل</th>
                                        <th scope="col" class="py-3 px-3 sm:px-4 text-right">نقش</th>
                                        <th scope="col" class="py-3 px-3 sm:px-4 text-center">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="users.length === 0">
                                        <td colspan="4" class="text-center py-4 text-gray-500">کاربری یافت نشد.</td>
                                    </tr>
                                    <tr v-for="user in users" :key="user.id" class="bg-white border-b last:border-0 hover:bg-gray-50 transition-colors duration-200">
                                        <td class="py-4 px-3 sm:px-4 text-right align-middle">{{ user.name }}</td>
                                        <td class="py-4 px-3 sm:px-4 text-right align-middle text-xs sm:text-sm">{{ user.phone }}</td>
                                        <td class="py-4 px-3 sm:px-4 text-right align-middle">
                                            <select
                                                :value="user.role_id"
                                                @change="event => handleUpdateUserRole(user, event.target.value)"
                                                class="border border-gray-300 rounded-md shadow-sm text-xs sm:text-sm"
                                                :disabled="user.id === authUser.id"
                                                :class="{'opacity-50 cursor-not-allowed bg-gray-100': user.id === authUser.id}"
                                                title="نمی‌توانید نقش کاربری خود را تغییر دهید.">
                                                <option v-for="role in allRoles" :key="role.id" :value="role.id">
                                                    {{ role.display_name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="py-4 px-3 sm:px-4 text-center align-middle">
                                            <div class="flex items-center justify-center gap-2">
                                                <button v-if="canEditUser" @click="openEditUser(user)" type="button" class="text-blue-600 hover:text-blue-800 text-sm p-1" :disabled="user.id === authUser.id" :class="{'opacity-50 cursor-not-allowed': user.id === authUser.id}" title="ویرایش کاربر">✎</button>
                                                <button @click="handleDeleteUser(user)" type="button" 
                                                    class="text-red-500 hover:text-red-700 text-sm p-1"
                                                    :disabled="user.id === authUser.id"
                                                    :class="{'opacity-50 cursor-not-allowed': user.id === authUser.id}"
                                                    title="نمی‌توانید حساب کاربری خود را حذف کنید.">
                                                    &#x2716;
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mobile Card View -->
                        <div class="sm:hidden space-y-3">
                            <div v-if="users.length === 0" class="text-center py-8 text-gray-500">کاربری یافت نشد.</div>
                            <div v-for="user in users" :key="user.id" class="border rounded-lg p-3 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-gray-900 truncate">{{ user.name }}</div>
                                        <div class="text-xs text-gray-600 mt-1">{{ user.phone }}</div>
                                    </div>
                                    <select
                                        :value="user.role_id"
                                        @change="event => handleUpdateUserRole(user, event.target.value)"
                                        class="border border-gray-300 rounded text-xs px-2 py-1"
                                        :disabled="user.id === authUser.id"
                                        :class="{'opacity-50 cursor-not-allowed bg-gray-100': user.id === authUser.id}">
                                        <option v-for="role in allRoles" :key="role.id" :value="role.id">
                                            {{ role.display_name }}
                                        </option>
                                    </select>
                                </div>
                                <div v-if="canEditUser || true" class="flex gap-2 justify-end text-xs">
                                    <button v-if="canEditUser" @click="openEditUser(user)" type="button" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" :disabled="user.id === authUser.id">
                                        ویرایش
                                    </button>
                                    <button @click="handleDeleteUser(user)" type="button" 
                                        class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600"
                                        :disabled="user.id === authUser.id">
                                        حذف
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Edit User Modal -->
                    <div v-if="editingUser" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-4 sm:p-6 max-h-[90vh] overflow-y-auto" dir="rtl">
                            <h3 class="text-base sm:text-lg font-bold mb-4">ویرایش کاربر: {{ editingUser.name }}</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">نام</label>
                                    <input type="text" v-model="editUserName" class="w-full px-3 py-2 text-sm border rounded" />
                                </div>
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">شماره موبایل</label>
                                    <input type="tel" v-model="editUserPhone" class="w-full px-3 py-2 text-sm border rounded" />
                                </div>
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">رمز عبور جدید (اختیاری)</label>
                                    <input type="password" v-model="editUserPassword" class="w-full px-3 py-2 text-sm border rounded" placeholder="اگر نمی‌خواهید تغییر دهید خالی بگذارید" />
                                </div>
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">نقش</label>
                                    <select v-model="editUserRoleId" class="w-full px-3 py-2 text-sm border rounded" :disabled="editingUser.id === authUser.id">
                                        <option v-for="role in allRoles" :key="role.id" :value="role.id">{{ role.display_name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-end gap-2 sm:gap-3">
                                <button @click="cancelEditUser" type="button" class="px-4 py-2 bg-gray-200 rounded text-sm w-full sm:w-auto">انصراف</button>
                                <button @click="submitEditUser" type="button" class="px-4 py-2 bg-blue-600 text-white rounded text-sm w-full sm:w-auto" :disabled="editUserStatus.type === 'loading'">ذخیره</button>
                            </div>
                            <div v-if="editUserStatus.text" class="mt-4 p-3 rounded text-xs sm:text-sm" :class="{ 'bg-green-100 text-green-800': editUserStatus.type === 'success', 'bg-red-100 text-red-800': editUserStatus.type === 'error', 'bg-yellow-100 text-yellow-800': editUserStatus.type === 'loading' }">{{ editUserStatus.text }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

