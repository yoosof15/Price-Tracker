<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    phone: {
        type: String,
        default: '',
    },
    token: {
        type: String,
        required: true,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    token: props.token,
    code: '',
});

const submit = () => {
    form.post(route('phone.otp.verify.submit'));
};
</script>

<template>
    <GuestLayout>
        <Head title="تأیید کد" />

        <!-- لوگو -->
        <div class="flex justify-center mb-6 sm:mb-8">
            <img src="/images/my-logo2.png" alt="لوگو" class="h-16 sm:h-20 md:h-24 w-auto">
        </div>

        <div class="mb-3 text-xs sm:text-sm text-gray-600 text-center">
            کد ارسال شده به شماره موبایل <span class="font-bold">{{ phone }}</span> را وارد کنید.
        </div>

        <div v-if="status" class="mb-4 text-xs sm:text-sm font-medium text-green-600 text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="w-full">
            <div>
                <InputLabel for="code" value="کد تأیید" />

                <TextInput
                    id="code"
                    type="text"
                    class="mt-1 block w-full text-center text-xl sm:text-2xl tracking-widest"
                    v-model="form.code"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    maxlength="6"
                    placeholder="123456"
                />

                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-0">
                <Link :href="route('phone.otp.request')" class="text-xs sm:text-sm text-gray-600 hover:text-gray-900 underline">
                    ارسال مجدد کد
                </Link>

                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="w-full sm:w-auto">
                    ورود
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
