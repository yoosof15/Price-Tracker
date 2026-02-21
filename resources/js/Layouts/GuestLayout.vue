<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 px-4 py-6 sm:justify-center sm:px-6 sm:pt-0 lg:px-8" dir="rtl"
    >
        <div>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-4 py-6 shadow-md sm:max-w-md sm:rounded-lg sm:px-6 sm:py-8"
        >
            <slot />
        </div>
    </div>
</template>
