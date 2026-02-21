<script setup>
import { ref, computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3'; // <--- router اضافه شد

const showingNavigationDropdown = ref(false);

const page = usePage();
const user = page.props.auth.user; // <--- اطلاعات کاربر لاگین شده

// <--- چک میکنیم که آیا کاربر مدیر کل است
const isAdmin = computed(() => {
    return user && user.is_admin;
});

// <--- برای نمایش لینک داشبورد
const canViewDashboard = computed(() => {
    return user && (user.can_view_dashboard || user.is_admin);
});

// <--- چک کردن هر دسترسی
const canViewPrices = computed(() => user && user.can_view_dashboard);
const canViewProductsMenu = computed(() => user && user.can_view_products);
const canViewLocationsMenu = computed(() => user && user.can_view_locations);
const canViewUsersMenu = computed(() => user && user.can_view_users);
const canViewRolesMenu = computed(() => user && user.can_view_roles);

const logout = () => {
    router.post(route('logout')); // <--- router.post استفاده شد
};
</script>

<template>
    <div :dir="user && user.is_admin ? 'rtl' : 'ltr'">
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <img src="/images/my-logo2.png" alt="لوگوی من" class="h-9 w-auto">
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex" :class="{'sm:space-x-reverse': user.is_admin}">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    داشبورد
                                </NavLink>
                                <NavLink v-if="canViewPrices" :href="route('admin.prices')" :active="route().current('admin.prices')">
                                    ثبت قیمت‌ها
                                </NavLink>
                                <NavLink v-if="canViewProductsMenu" :href="route('admin.products.index')" :active="route().current('admin.products.index')">
                                    مدیریت محصولات
                                </NavLink>
                                <NavLink v-if="canViewLocationsMenu" :href="route('admin.locations.index')" :active="route().current('admin.locations.index')">
                                    مدیریت مکان‌ها
                                </NavLink>
                                <NavLink v-if="canViewUsersMenu" :href="route('admin.users.index')" :active="route().current('admin.users.index')">
                                    مدیریت کاربران
                                </NavLink>
                                <NavLink v-if="canViewRolesMenu" :href="route('admin.roles.index')" :active="route().current('admin.roles.index')">
                                    مدیریت سمت‌ها
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <!-- <button v-if="$page.props.jetstream.managesProfilePhotos" ... -->
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }} ({{ user.role ? user.role.display_name : 'بدون نقش' }})
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            مدیریت حساب
                                        </div>

                                        <DropdownLink :href="route('profile.edit')">
                                            پروفایل
                                        </DropdownLink>

                                        <div class="border-t border-gray-200" />

                                        <!-- Authentication -->
                                        <button @click="logout" class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none">
                                            خروج
                                        </button>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            داشبورد
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="canViewPrices" :href="route('admin.prices')" :active="route().current('admin.prices')">
                            ثبت قیمت‌ها
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="canViewProductsMenu" :href="route('admin.products.index')" :active="route().current('admin.products.index')">
                            مدیریت محصولات
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="canViewLocationsMenu" :href="route('admin.locations.index')" :active="route().current('admin.locations.index')">
                            مدیریت مکان‌ها
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="canViewUsersMenu" :href="route('admin.users.index')" :active="route().current('admin.users.index')">
                            مدیریت کاربران
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="canViewRolesMenu" :href="route('admin.roles.index')" :active="route().current('admin.roles.index')">
                            مدیریت سمت‌ها
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="flex items-center px-4">
                            <!-- <div v-if="$page.props.jetstream.managesProfilePhotos" ... -->
                            <div>
                                <div class="font-medium text-base text-gray-800">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="font-medium text-sm text-gray-500">
                                    {{ $page.props.auth.user.phone }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')" :active="route().current('profile.edit')">
                                پروفایل
                            </ResponsiveNavLink>
                            
                            <!-- Authentication -->
                            <button @click="logout" class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:bg-gray-100 rounded">
                                خروج
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
