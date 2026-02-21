<script setup>
import { ref, onMounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    products: Array, // لیست محصولات از بک اند
});

const products = computed(() => props.products);

const newProductName = ref('');
const selectedImage = ref(null);
const selectedImagePreview = ref(null);
const selectedColor = ref('#ffffff');
const statusMessage = ref({ type: '', text: '' });
const addProductStatus = ref({ type: '', text: ''});
const isLoading = ref(false); // برای لودینگ اولیه

const page = usePage();
const authUser = ref(page.props.auth.user);

// <--- چک کردن دسترسی ها (چندین نام ممکن برای کلیدهای پرمیشن)
const canCreateProduct = computed(() => authUser.value.can_create_product);
const canDeleteProduct = computed(() => authUser.value.can_delete_product);
const canEditProduct = computed(() => {
    const u = authUser.value || {};
    return !!(
        u.can_update_product ||
        u.can_edit_product ||
        u.can_update_products ||
        u.can_edit_products ||
        u.is_super_admin
    );
});

// --- Edit product state ---
const editingProduct = ref(null);
const editName = ref('');
const editColor = ref('#ffffff');
const editImageFile = ref(null);
const editImagePreview = ref(null);
const editStatus = ref({ type: '', text: '' });

function openEdit(product) {
    editingProduct.value = product;
    editName.value = product.name;
    editColor.value = product.color || '#ffffff';
    editImageFile.value = null;
    editImagePreview.value = product.image ? `/storage/${product.image}` : null;
    editStatus.value = { type: '', text: '' };
}

function cancelEdit() {
    editingProduct.value = null;
    editName.value = '';
    editColor.value = '#ffffff';
    editImageFile.value = null;
    editImagePreview.value = null;
    editStatus.value = { type: '', text: '' };
}

function onEditImageChange(e) {
    const file = e.target.files[0];
    editImageFile.value = file || null;
    if (file) {
        const reader = new FileReader();
        reader.onload = (ev) => editImagePreview.value = ev.target.result;
        reader.readAsDataURL(file);
    }
}

async function submitEdit() {
    if (!editingProduct.value) return;
    if (!editName.value.trim()) {
        editStatus.value = { type: 'error', text: 'نام محصول نمی‌تواند خالی باشد.' };
        return;
    }
    editStatus.value = { type: 'loading', text: 'در حال ذخیره تغییرات...' };
    try {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('name', editName.value);
        if (editImageFile.value) formData.append('image', editImageFile.value);
        formData.append('color', editColor.value);

        await axios.post(`/admin/products/${editingProduct.value.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        editStatus.value = { type: 'success', text: 'محصول با موفقیت بروزرسانی شد.' };
        // close modal and refresh page data
        cancelEdit();
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('خطا در ویرایش محصول:', error);
        const errorMessage = error.response?.data?.message || 'خطا در ذخیره تغییرات.';
        editStatus.value = { type: 'error', text: errorMessage };
    }
}


async function handleAddProduct() {
    // <--- Policy check
    if (!authUser.value.can_create_product) {
        addProductStatus.value = { type: 'error', text: 'شما اجازه ایجاد محصول جدید را ندارید.' };
        return;
    }
    if (!newProductName.value.trim()) {
        addProductStatus.value = { type: 'error', text: 'نام محصول نمی‌تواند خالی باشد.' };
        return;
    }
    addProductStatus.value = { type: 'loading', text: 'در حال افزودن محصول...' };
    try {
        const tempProductName = newProductName.value; // keep name for messages

        const formData = new FormData();
        formData.append('name', newProductName.value);
        if (selectedImage.value) {
            formData.append('image', selectedImage.value);
        }
        if (selectedColor.value) {
            formData.append('color', selectedColor.value);
        }

        await axios.post('/admin/products', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        newProductName.value = '';
        selectedImage.value = null;
        selectedColor.value = '#ffffff';
        addProductStatus.value = { type: 'success', text: `محصول "${tempProductName}" با موفقیت افزوده شد.` };
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error("خطا در افزودن محصول:", error);
        const errorMessage = error.response?.data?.message || 'خطا در افزودن محصول. (شاید تکراری باشد؟)';
        addProductStatus.value = { type: 'error', text: errorMessage };
    }
}

function onImageChange(e) {
    const file = e.target.files[0];
    selectedImage.value = file || null;
    if (file) {
        const reader = new FileReader();
        reader.onload = (ev) => selectedImagePreview.value = ev.target.result;
        reader.readAsDataURL(file);
    } else {
        selectedImagePreview.value = null;
    }
}

function hexToRgba(hex, alpha) {
    if (!hex) return null;
    const h = hex.replace('#','');
    const bigint = parseInt(h, 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}


async function handleDeleteProduct(product) {
    // <--- Policy check
    if (!authUser.value.can_delete_product) {
        statusMessage.value = { type: 'error', text: 'شما اجازه حذف محصول را ندارید.' };
        return;
    }
    if (!confirm(`آیا از حذف محصول "${product.name}" مطمئن هستید؟ تمام قیمت‌ها حذف خواهند شد.`)) {
        return;
    }
    statusMessage.value = { type: 'loading', text: `در حال حذف محصول "${product.name}"...` }; // <--- لودینگ
    try {
        await axios.delete(`/admin/products/${product.id}`);
        statusMessage.value = { type: 'success', text: `محصول "${product.name}" با موفقیت حذف شد.` };
        router.reload({ preserveScroll: true }); // بروزرسانی لیست
    } catch (error) {
        console.error("خطا در حذف محصول:", error);
        const errorMessage = error.response?.data?.message || 'خطا در حذف محصول. (شاید قیمت‌هایی وابسته باشند؟)';
        statusMessage.value = { type: 'error', text: errorMessage };
    }
}
</script>

<template>
    <Head title="مدیریت محصولات" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">مدیریت محصولات</h2>
        </template>

        <div class="py-12" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- <--- فرم افزودن محصول جدید -->
                <div v-if="canCreateProduct" class="bg-white overflow-hidden shadow-xl rounded-lg p-6">
                    <h3 class="font-bold text-lg mb-6">افزودن محصول جدید</h3>
                    <form @submit.prevent="handleAddProduct" class="space-y-4">
                        <!-- ردیف اول: نام محصول -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نام محصول</label>
                            <input type="text" v-model="newProductName" placeholder="نام محصول جدید را وارد کنید" 
                                   class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-green-500">
                        </div>

                        <!-- ردیف دوم: آپلود عکس و پیش‌نمایش -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">عکس محصول</label>
                            <div class="flex flex-col sm:flex-row sm:items-end gap-3 sm:gap-4">
                                <div class="custom-file-input">
                                    <label for="product-image-input" class="choose-btn flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white rounded-md cursor-pointer hover:bg-green-700 transition-colors text-sm sm:text-base w-full sm:w-auto">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M16 3l-4 4-4-4"></path></svg>
                                        <span class="text-xs sm:text-sm font-medium">انتخاب عکس</span>
                                    </label>
                                    <input id="product-image-input" type="file" @change="onImageChange" accept="image/*" class="hidden">
                                </div>
                                <div v-if="selectedImagePreview" class="w-20 h-20 rounded-lg overflow-hidden border-2 border-green-200 flex-shrink-0">
                                    <img :src="selectedImagePreview" alt="preview" class="w-full h-full object-cover">
                                </div>
                                <span v-else class="text-xs sm:text-sm text-gray-500">هیچ عکسی انتخاب نشده</span>
                            </div>
                        </div>

                        <!-- ردیف سوم: انتخاب رنگ -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">رنگ محصول</label>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                                <div class="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg bg-white">
                                    <input type="color" v-model="selectedColor" class="w-10 h-10 sm:w-12 p-0 border-0 rounded cursor-pointer" title="انتخاب رنگ محصول">
                                    <span class="text-xs sm:text-sm font-mono bg-gray-100 px-2 py-1 rounded">{{ selectedColor }}</span>
                                </div>
                                <div :style="{ backgroundColor: selectedColor }" class="w-16 h-10 rounded-lg border-2 border-gray-300 flex-shrink-0"></div>
                            </div>
                        </div>

                        <!-- دکمه افزودن -->
                        <div class="pt-4">
                            <button type="submit" class="w-full px-6 py-3 bg-green-600 text-white font-medium text-sm sm:text-base rounded-lg hover:bg-green-700 transition-colors"
                                    :disabled="addProductStatus.type === 'loading'" 
                                    :class="{'opacity-50 cursor-not-allowed': addProductStatus.type === 'loading'}">
                                <span v-if="addProductStatus.type === 'loading'" class="flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 ml-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    در حال افزودن...
                                </span>
                                <span v-else>افزودن محصول جدید</span>
                            </button>
                        </div>
                    </form>

                    <!-- پیام وضعیت -->
                    <div v-if="addProductStatus.text" class="mt-4 p-3 rounded-lg text-center text-xs sm:text-sm font-medium"
                        :class="{ 'bg-green-100 text-green-800 border border-green-300': addProductStatus.type === 'success', 
                                 'bg-red-100 text-red-800 border border-red-300': addProductStatus.type === 'error', 
                                 'bg-yellow-100 text-yellow-800 border border-yellow-300': addProductStatus.type === 'loading' }">
                        {{ addProductStatus.text }}
                    </div>
                </div>

                <!-- <--- لیست محصولات -->
                <div class="bg-white overflow-hidden shadow-xl rounded-lg p-4 sm:p-6">
                    <h3 class="font-bold text-lg mb-4">لیست محصولات</h3>
                     <div v-if="statusMessage.text && statusMessage.type !== 'loading'" class="mb-4 text-center p-2 text-sm sm:text-base rounded-md" 
                        :class="{ 'bg-green-100 text-green-700': statusMessage.type === 'success', 'bg-red-100 text-red-700': statusMessage.type === 'error' }">
                        {{ statusMessage.text }}
                    </div>
                    <!-- Desktop Table View -->
                    <div class="hidden sm:block relative overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm text-right text-gray-700">
                            <thead class="bg-gray-100 uppercase text-gray-600 tracking-wider">
                                <tr>
                                    <th scope="col" class="py-3 px-3 sm:px-4 text-right">#</th>
                                    <th scope="col" class="py-3 px-3 sm:px-4 text-right">نام محصول</th>
                                    <th v-if="canDeleteProduct || canEditProduct" scope="col" class="py-3 px-3 sm:px-4 text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="products.length === 0">
                                    <td :colspan="canDeleteProduct ? 3 : 2" class="text-center py-4 text-gray-500">محصولی یافت نشد.</td>
                                </tr>
                                <tr v-for="(product, index) in products" :key="product.id" 
                                    :style="{ backgroundColor: product.color ? hexToRgba(product.color, 0.12) : '' }"
                                    class="border-b last:border-0 hover:opacity-90 transition-all duration-200 border-l-4"
                                    :class="product.color ? '' : 'bg-white'">
                                    
                                    <td class="py-4 px-3 sm:px-4 align-middle font-medium text-gray-700">{{ index + 1 }}</td>
                                    <td class="py-4 px-3 sm:px-4 align-middle">
                                        <div class="flex items-center gap-4">
                                            <div v-if="product.image" class="w-12 h-12 sm:w-16 sm:h-16 rounded-lg overflow-hidden border-2 flex-shrink-0" :style="{ borderColor: product.color || '#ddd' }">
                                                <img :src="`/storage/${product.image}`" alt="" class="w-full h-full object-cover">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-semibold text-gray-900 truncate">{{ product.name }}</div>
                                                <div class="mt-2 flex items-center gap-2 text-xs sm:text-sm">
                                                    <span :style="{ background: product.color, borderColor: product.color }" class="w-4 h-4 rounded-md border-2 flex-shrink-0"></span>
                                                    <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">{{ product.color || '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td v-if="canDeleteProduct || canEditProduct" class="py-4 px-3 sm:px-4 text-center align-middle">
                                        <div class="flex items-center justify-center gap-2">
                                            <button v-if="canEditProduct" @click="openEdit(product)" type="button" class="text-blue-600 hover:text-blue-800 text-lg p-1 transition-colors"
                                                    :disabled="statusMessage.type === 'loading'" :class="{'opacity-50 cursor-not-allowed': statusMessage.type === 'loading'}" title="ویرایش">
                                                ✎
                                            </button>
                                            <button v-if="canDeleteProduct" @click="handleDeleteProduct(product)" type="button" class="text-red-500 hover:text-red-700 text-lg p-1 transition-colors"
                                                    :disabled="statusMessage.type === 'loading'" 
                                                    :class="{'opacity-50 cursor-not-allowed': statusMessage.type === 'loading'}" title="حذف">
                                                <span v-if="statusMessage.type === 'loading' && statusMessage.text.includes(product.name)">
                                                    <svg class="animate-spin h-4 w-4 text-red-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </span>
                                                <span v-else>&#x2716;</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Card View -->
                    <div class="sm:hidden space-y-3">
                        <div v-if="products.length === 0" class="text-center py-8 text-gray-500">
                            محصولی یافت نشد.
                        </div>
                        <div v-for="(product, index) in products" :key="product.id" 
                            class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                            :style="{ backgroundColor: product.color ? hexToRgba(product.color, 0.05) : '' }">
                            <div class="flex gap-3">
                                <div v-if="product.image" class="w-16 h-16 rounded-lg overflow-hidden border-2 flex-shrink-0" :style="{ borderColor: product.color || '#ddd' }">
                                    <img :src="`/storage/${product.image}`" alt="" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900">{{ index + 1 }}. {{ product.name }}</div>
                                    <div class="mt-2 flex items-center gap-2 text-xs">
                                        <span :style="{ background: product.color, borderColor: product.color }" class="w-4 h-4 rounded border-2 flex-shrink-0"></span>
                                        <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">{{ product.color || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="canDeleteProduct || canEditProduct" class="mt-3 flex gap-2 justify-end">
                                <button v-if="canEditProduct" @click="openEdit(product)" type="button" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                    ویرایش
                                </button>
                                <button v-if="canDeleteProduct" @click="handleDeleteProduct(product)" type="button" class="px-3 py-1.5 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
                                    :disabled="statusMessage.type === 'loading'">
                                    <span v-if="statusMessage.type === 'loading' && statusMessage.text.includes(product.name)">
                                        <svg class="animate-spin h-3 w-3 text-white mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                    <span v-else>حذف</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Edit Product Modal -->
        <div v-if="editingProduct" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-4 sm:p-6 max-h-[90vh] overflow-y-auto" dir="rtl">
                <h3 class="text-base sm:text-lg font-bold mb-4">ویرایش محصول: {{ editingProduct.name }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">نام محصول</label>
                        <input type="text" v-model="editName" class="w-full px-3 py-2 text-sm sm:text-base border rounded" />
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">عکس جدید (اختیاری)</label>
                        <div class="flex flex-col sm:flex-row sm:items-end gap-3 sm:gap-4">
                            <label for="edit-image-input" class="choose-btn flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white rounded-md cursor-pointer hover:bg-green-700 transition-colors text-xs sm:text-sm w-full sm:w-auto">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M16 3l-4 4-4-4"></path></svg>
                                انتخاب عکس
                            </label>
                            <input id="edit-image-input" type="file" @change="onEditImageChange" accept="image/*" class="hidden">
                            <div v-if="editImagePreview" class="w-20 h-20 rounded-lg overflow-hidden border-2 flex-shrink-0">
                                <img :src="editImagePreview" class="w-full h-full object-cover">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">رنگ محصول</label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                            <div class="flex items-center gap-2">
                                <input type="color" v-model="editColor" class="w-10 h-10 sm:w-12 p-0 border-0 rounded cursor-pointer">
                                <span class="font-mono text-xs sm:text-sm bg-gray-100 px-2 py-1 rounded">{{ editColor }}</span>
                            </div>
                            <div :style="{ backgroundColor: editColor }" class="w-16 h-10 rounded-lg border-2 border-gray-300 flex-shrink-0"></div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-end gap-2 sm:gap-3">
                    <button @click="cancelEdit" type="button" class="px-4 py-2 bg-gray-200 rounded text-sm sm:text-base w-full sm:w-auto">انصراف</button>
                    <button @click="submitEdit" type="button" class="px-4 py-2 bg-blue-600 text-white rounded text-sm sm:text-base w-full sm:w-auto" :disabled="editStatus.type === 'loading'">ذخیره</button>
                </div>
                <div v-if="editStatus.text" class="mt-4 p-3 rounded text-xs sm:text-sm" :class="{ 'bg-green-100 text-green-800': editStatus.type === 'success', 'bg-red-100 text-red-800': editStatus.type === 'error', 'bg-yellow-100 text-yellow-800': editStatus.type === 'loading' }">{{ editStatus.text }}</div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
/* فونت Vazirmatn در app.css تعریف شده است */
/* استایل‌های سفارشی برای دکمه نمودار */
.chart-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(125, 0, 252, 0.5); /* رنگ primaryPurple */
}

/* Custom file input styles */
.custom-file-input .choose-btn {
    transition: all .15s ease-in-out;
}
.custom-file-input input[type="file"] { display: none; }
.custom-file-input .choose-btn:hover { transform: translateY(-1px); }

/* make table rows look better when colored */
table tr td { transition: background-color .2s ease; }

</style>
