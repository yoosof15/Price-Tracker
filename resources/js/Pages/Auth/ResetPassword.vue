<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="ورود با کد" />

        <div class="text-sm text-gray-600">
            این مسیر غیرفعال است. برای ورود با کد، از گزینه ورود با شماره موبایل استفاده کنید.
        </div>

        <div class="mt-6">
            <Link
                :href="route('phone.otp.request')"
                class="text-sm text-gray-700 underline hover:text-gray-900"
            >
                رفتن به ورود با کد
            </Link>
        </div>
    </GuestLayout>
</template>
