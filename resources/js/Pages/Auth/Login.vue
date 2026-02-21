<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    phone: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="ورود" />

        <!-- لوگوی سفارشی -->
        <div class="flex justify-center mb-6 sm:mb-8">
            <img src="/images/my-logo2.png" alt="لوگوی من" class="h-16 sm:h-20 md:h-24 w-auto">
        </div>

        <div v-if="status" class="mb-4 text-xs sm:text-sm font-medium text-green-600 text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="w-full">
            <div>
                <InputLabel for="phone" value="شماره موبایل" />

                <TextInput
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full text-sm sm:text-base"
                    v-model="form.phone"
                    required
                    autofocus
                    autocomplete="tel"
                />

                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="رمز" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full text-sm sm:text-base"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center gap-2">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-xs sm:text-sm text-gray-600"
                        >مرا به خاطر بسپار</span
                    >
                </label>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-0">
                <Link
                    v-if="canResetPassword"
                    :href="route('phone.otp.request')"
                    class="text-xs sm:text-sm text-gray-600 hover:text-gray-900 underline"
                >
                    فراموشی رمز (ورود با کد)
                </Link>

                <PrimaryButton
                    class="w-full sm:w-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    ورود
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
