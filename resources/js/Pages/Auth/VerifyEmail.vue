<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="تأیید حساب" />

        <div class="mb-4 text-sm text-gray-600">
            تأیید ایمیل غیرفعال است. ورود فقط با شماره موبایل انجام می‌شود.
        </div>

        <Link
            :href="route('login')"
            class="text-sm text-gray-700 underline hover:text-gray-900"
        >
            بازگشت به ورود
        </Link>
    </GuestLayout>
</template>
