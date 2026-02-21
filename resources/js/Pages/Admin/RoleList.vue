<script setup>
import { ref, onMounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

// <--- Props
const props = defineProps({
    roles: Array, // لیست نقش‌ها از بک اند
    allPermissions: Array, // لیست تمام دسترسی‌ها از بک اند
});

// <--- State Variables
const roles = computed(() => props.roles);
const allPermissions = ref(props.allPermissions);

const newRole = ref({
    name: '',
    display_name: '',
    permissions: [], // IDهای دسترسی های انتخاب شده
});
const statusMessage = ref({ type: '', text: '' });
const addRoleStatus = ref({ type: '', text: '' });
const isLoading = ref(false);

// <--- User Data
const page = usePage();
const authUser = ref(page.props.auth.user);

// <--- Computed Properties (برای دسترسی ها)
const canViewRoles = computed(() => authUser.value.can_view_roles);
const canCreateRole = computed(() => authUser.value.can_create_role);
const canDeleteRole = computed(() => authUser.value.can_delete_role);
const canAssignPermissionsToRole = computed(() => authUser.value.can_assign_permissions_to_role);
const canEditRole = computed(() => {
    const u = authUser.value || {};
    return !!(
        u.can_edit_role ||
        u.can_update_role ||
        u.can_edit_roles ||
        u.can_update_roles ||
        u.is_super_admin
    );
});

// --- Edit role state ---
const editingRole = ref(null);
const editRoleName = ref('');
const editRoleDisplayName = ref('');
const editRolePermissions = ref([]);
const editRoleStatus = ref({ type: '', text: '' });

function openEditRole(role) {
    editingRole.value = role;
    editRoleName.value = role.name;
    editRoleDisplayName.value = role.display_name;
    editRolePermissions.value = role.permissions ? role.permissions.map(p => p.id) : [];
    editRoleStatus.value = { type: '', text: '' };
}

function cancelEditRole() {
    editingRole.value = null;
    editRoleName.value = '';
    editRoleDisplayName.value = '';
    editRolePermissions.value = [];
    editRoleStatus.value = { type: '', text: '' };
}

function toggleEditPermission(permissionId) {
    const i = editRolePermissions.value.indexOf(permissionId);
    if (i === -1) editRolePermissions.value.push(permissionId);
    else editRolePermissions.value.splice(i, 1);
}

async function submitEditRole() {
    if (!editingRole.value) return;
    if (!editRoleName.value.trim() || !editRoleDisplayName.value.trim()) {
        editRoleStatus.value = { type: 'error', text: 'نام و نام قابل نمایش نمی‌توانند خالی باشند.' };
        return;
    }
    editRoleStatus.value = { type: 'loading', text: 'در حال ذخیره تغییرات...' };
    try {
        await axios.put(`/admin/roles/${editingRole.value.id}`, {
            name: editRoleName.value,
            display_name: editRoleDisplayName.value,
            permissions: editRolePermissions.value,
        });
        editRoleStatus.value = { type: 'success', text: 'نقش با موفقیت بروزرسانی شد.' };
        cancelEditRole();
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('خطا در ویرایش نقش:', error);
        const errorMessage = error.response?.data?.message || 'خطا در ذخیره تغییرات.';
        editRoleStatus.value = { type: 'error', text: errorMessage };
    }
}


// <--- توابع
async function handleAddRole() {
    addRoleStatus.value = { type: 'loading', text: 'در حال ایجاد نقش...' };
    if (!authUser.value.can_create_role) {
        addRoleStatus.value = { type: 'error', text: 'شما اجازه ایجاد نقش جدید را ندارید.' };
        return;
    }
    try {
        const roleDisplayName = newRole.value.display_name; // <--- نام نقش را قبل از ریست کردن ذخیره کن
        await axios.post('/admin/roles', newRole.value);
        newRole.value = { name: '', display_name: '', permissions: [] }; // ریست فرم
        addRoleStatus.value = { type: 'success', text: `نقش "${roleDisplayName}" با موفقیت ایجاد شد.` }; // <--- استفاده از نام ذخیره شده
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error("خطا در ایجاد نقش:", error);
        const errorMessage = error.response?.data?.message || 'خطا در ایجاد نقش. (شاید تکراری باشد؟)';
        addRoleStatus.value = { type: 'error', text: errorMessage };
    }
}

// <--- تابع برای انتخاب/عدم انتخاب دسترسی ها
function togglePermission(permissionId) {
    const index = newRole.value.permissions.indexOf(permissionId);
    if (index === -1) {
        newRole.value.permissions.push(permissionId);
    } else {
        newRole.value.permissions.splice(index, 1);
    }
}

// <--- تابع برای چک کردن Meta-Permissions (مثلا مدیریت محصولات)
function toggleMetaPermission(metaPermissionName) {
    const permissionsToToggle = getPermissionsForMeta(metaPermissionName);
    const allSelected = permissionsToToggle.every(pId => newRole.value.permissions.includes(pId));

    if (allSelected) {
        // اگر همه انتخاب شده اند، همه را deselect کن
        permissionsToToggle.forEach(pId => {
            const index = newRole.value.permissions.indexOf(pId);
            if (index !== -1) newRole.value.permissions.splice(index, 1);
        });
    } else {
        // اگر همه انتخاب نشده اند، همه را select کن
        permissionsToToggle.forEach(pId => {
            if (!newRole.value.permissions.includes(pId)) {
                newRole.value.permissions.push(pId);
            }
        });
    }
}

// <--- تابع کمکی: برگرداندن IDهای دسترسی های جزئی برای یک Meta-Permission
function getPermissionsForMeta(metaPermissionName) {
    const metaMap = {
        'مدیریت محصولات': ['access-admin-panel','view-products', 'create-product', 'edit-product', 'delete-product'],
        'مدیریت مکان‌ها': ['access-admin-panel','view-locations', 'create-location', 'edit-location', 'delete-location'],
        'مدیریت کاربران': ['access-admin-panel','view-users', 'create-user', 'edit-user-role', 'delete-user', 'edit-user'],
        'مدیریت سمت‌ها': ['access-admin-panel','view-roles', 'create-role', 'edit-role', 'delete-role', 'assign-permissions-to-role'],
        'مدیریت قیمت‌ها': ['access-admin-panel','view-dashboard','view-daily-price-settings', 'add-to-daily-price-settings', 'remove-from-daily-price-settings', 'save-daily-prices'],
    };
    const permissionNames = metaMap[metaPermissionName] || [];
    return allPermissions.value.filter(p => permissionNames.includes(p.name)).map(p => p.id);
}

// ... کدهای قبلی (import ها، State Variables, Computed Properties, توابع handleAddRole, togglePermission, toggleMetaPermission, getPermissionsForMeta)


async function handleDeleteRole(role) {
    if (!confirm(`آیا از حذف نقش "${role.display_name}" مطمئن هستید؟ این عمل غیرقابل بازگشت است و تمام کاربرانی که این نقش را دارند، بدون نقش خواهند شد.`)) {
        return;
    }
    if (!authUser.value.can_delete_role) {
        statusMessage.value = { type: 'error', text: 'شما اجازه حذف نقش را ندارید.' };
        return;
    }
    statusMessage.value = { type: 'loading', text: `در حال حذف نقش "${role.display_name}"...` }; // <--- لودینگ
    try {
        await axios.delete(`/admin/roles/${role.id}`);
        statusMessage.value = { type: 'success', text: `نقش "${role.display_name}" با موفقیت حذف شد.` };
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error("خطا در حذف نقش:", error);
        const errorMessage = error.response?.data?.message || 'خطا در حذف نقش. (شاید کاربرانی با این نقش وجود داشته باشند؟)';
        statusMessage.value = { type: 'error', text: errorMessage };
    }
}

// --- Lifecycle Hooks
onMounted(() => {
    // console.log('RoleList.vue received props.roles:', props.roles);
    // console.log('RoleList.vue received props.allPermissions:', props.allPermissions);
});

// ... بقیه کد در پیام بعدی (بخش template)
</script>

<template>
    <Head title="مدیریت سمت‌ها" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">مدیریت سمت‌ها</h2>
        </template>

        <div class="py-12" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- <--- فرم ایجاد نقش جدید -->
                <div v-if="canCreateRole" class="bg-white overflow-hidden shadow-xl rounded-lg p-6">
                    <h3 class="font-bold text-lg mb-4">ایجاد سمت جدید</h3>

                    <form @submit.prevent="handleAddRole" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block font-medium text-sm text-gray-700">
                                نام سیستمی (Name)
                            </label>
                            <input
                                type="text"
                                id="name"
                                v-model="newRole.name"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                required
                            >
                        </div>

                        <div>
                            <label for="display_name" class="block font-medium text-sm text-gray-700">
                                نام قابل نمایش (Display Name)
                            </label>
                            <input
                                type="text"
                                id="display_name"
                                v-model="newRole.display_name"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                required
                            >
                        </div>

                        <!-- <--- بخش دسترسی ها (Permissions) -->
                        <div v-if="canAssignPermissionsToRole" class="md:col-span-2">
                            <h4 class="font-semibold text-gray-700 mb-2">دسترسی‌ها (Permissions)</h4>

                            <!-- جدا کردن متا دسترسی‌ها از گرید اصلی -->
                            <div class="space-y-6">
                                <!-- Meta-Permissions -->
                                <div class="p-4 border rounded-lg bg-gray-50">
                                    <h5 class="text-sm font-bold text-gray-800 mb-3">
                                        دسترسی‌های کلی (Meta-Permissions)
                                    </h5>

                                    <div class="flex flex-wrap justify-start gap-2">
                                        <button
                                            type="button"
                                            @click="toggleMetaPermission('مدیریت محصولات')"
                                            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs hover:bg-blue-200 transition"
                                        >
                                            مدیریت محصولات
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleMetaPermission('مدیریت مکان‌ها')"
                                            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs hover:bg-blue-200 transition"
                                        >
                                            مدیریت مکان‌ها
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleMetaPermission('مدیریت کاربران')"
                                            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs hover:bg-blue-200 transition"
                                        >
                                            مدیریت کاربران
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleMetaPermission('مدیریت سمت‌ها')"
                                            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs hover:bg-blue-200 transition"
                                        >
                                            مدیریت سمت‌ها
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleMetaPermission('مدیریت قیمت‌ها')"
                                            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs hover:bg-blue-200 transition"
                                        >
                                            مدیریت قیمت‌ها
                                        </button>
                                    </div>
                                </div>

                                <!-- Granular Permissions -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                    <div
                                        v-for="permission in allPermissions"
                                        :key="permission.id"
                                        class="flex flex-row-reverse items-center justify-end gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            :id="`perm-${permission.id}`"
                                            :value="permission.id"
                                            v-model="newRole.permissions"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                        >
                                        <label
                                            :for="`perm-${permission.id}`"
                                            class="text-sm text-gray-600 select-none"
                                        >
                                            {{ permission.display_name }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-full flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                ایجاد سمت
                            </button>
                        </div>
                    </form>

                    <div
                        v-if="addRoleStatus.text"
                        class="mt-4 text-center p-2 rounded-md"
                        :class="{
                            'bg-green-100 text-green-700': addRoleStatus.type === 'success',
                            'bg-red-100 text-red-700': addRoleStatus.type === 'error',
                            'bg-yellow-100 text-yellow-700': addRoleStatus.type === 'loading'
                        }"
                    >
                        {{ addRoleStatus.text }}
                    </div>
                </div>

                <!-- <--- لیست نقش‌ها -->
                <div class="bg-white overflow-hidden shadow-xl rounded-lg p-6">
                    <h3 class="font-bold text-lg mb-4">لیست سمت‌های موجود</h3>
                    <div
                        v-if="statusMessage.text"
                        class="mb-4 text-center p-2 rounded-md"
                        :class="{
                            'bg-green-100 text-green-700': statusMessage.type === 'success',
                            'bg-red-100 text-red-700': statusMessage.type === 'error',
                            'bg-yellow-100 text-yellow-700': statusMessage.type === 'loading'
                        }"
                    >
                        {{ statusMessage.text }}
                    </div>

                    <div class="relative overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm text-right text-gray-700">
                            <thead class="bg-gray-100 uppercase text-gray-600 tracking-wider">
                                <tr>
                                    <th scope="col" class="py-3 px-3 sm:px-4 text-right">#</th>
                                    <th scope="col" class="py-3 px-3 sm:px-4 text-right">نام سیستمی</th>
                                    <th scope="col" class="py-3 px-3 sm:px-4 text-right">نام قابل نمایش</th>
                                    <th scope="col" class="py-3 px-3 sm:px-4 text-right">تعداد دسترسی‌ها</th>
                                    <th v-if="canDeleteRole || canEditRole" scope="col" class="py-3 px-3 sm:px-4 text-center">عملیات</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-if="roles.length === 0">
                                    <td :colspan="canDeleteRole ? 5 : 4" class="text-center py-4 text-gray-500">
                                        نقشی یافت نشد.
                                    </td>
                                </tr>

                                <tr
                                    v-for="(role, index) in roles"
                                    :key="role.id"
                                    class="bg-white border-b last:border-0 hover:bg-gray-50 transition-colors duration-200"
                                >
                                    <td class="py-4 px-3 sm:px-4 align-middle">{{ index + 1 }}</td>
                                    <td class="py-4 px-3 sm:px-4 align-middle">{{ role.name }}</td>
                                    <td class="py-4 px-3 sm:px-4 align-middle">{{ role.display_name }}</td>
                                    <td class="py-4 px-3 sm:px-4 align-middle">{{ role.permissions.length }}</td>

                                    <td v-if="canDeleteRole || canEditRole" class="py-4 px-3 sm:px-4 text-center align-middle">
                                        <div class="flex items-center justify-center gap-2">
                                            <button v-if="canEditRole" @click="openEditRole(role)" type="button" class="text-blue-600 hover:text-blue-800 text-sm p-1" :disabled="role.name === 'super_admin'" :class="{ 'opacity-50 cursor-not-allowed': role.name === 'super_admin' }" title="ویرایش">
                                                ✎
                                            </button>
                                            <button v-if="canDeleteRole" @click="handleDeleteRole(role)" type="button" class="text-red-500 hover:text-red-700 text-sm p-1" :disabled="role.name === 'super_admin'" :class="{ 'opacity-50 cursor-not-allowed': role.name === 'super_admin' }" title="نقش مدیر کل قابل حذف نیست.">
                                                &#x2716;
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        <!-- Edit Role Modal -->
        <div v-if="editingRole" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl p-6" dir="rtl">
                <h3 class="text-lg font-bold mb-4">ویرایش نقش: {{ editingRole.display_name }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نام سیستمی (Name)</label>
                        <input type="text" v-model="editRoleName" class="w-full px-3 py-2 border rounded" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نام قابل نمایش (Display Name)</label>
                        <input type="text" v-model="editRoleDisplayName" class="w-full px-3 py-2 border rounded" />
                    </div>
                    <div class="md:col-span-2">
                        <h4 class="font-semibold text-gray-700 mb-2">دسترسی‌ها</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-64 overflow-y-auto p-2 border rounded">
                            <div v-for="perm in allPermissions" :key="perm.id" class="flex flex-row-reverse items-center justify-end gap-2">
                                <input type="checkbox" :id="`edit-perm-${perm.id}`" :value="perm.id" :checked="editRolePermissions.includes(perm.id)" @change.prevent="toggleEditPermission(perm.id)" class="rounded border-gray-300 text-blue-600" />
                                <label :for="`edit-perm-${perm.id}`" class="text-sm text-gray-600 select-none">{{ perm.display_name }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button @click="cancelEditRole" type="button" class="px-4 py-2 bg-gray-200 rounded">انصراف</button>
                    <button @click="submitEditRole" type="button" class="px-4 py-2 bg-blue-600 text-white rounded" :disabled="editRoleStatus.type === 'loading'">ذخیره</button>
                </div>
                <div v-if="editRoleStatus.text" class="mt-4 p-3 rounded" :class="{ 'bg-green-100 text-green-800': editRoleStatus.type === 'success', 'bg-red-100 text-red-800': editRoleStatus.type === 'error', 'bg-yellow-100 text-yellow-800': editRoleStatus.type === 'loading' }">{{ editRoleStatus.text }}</div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>



