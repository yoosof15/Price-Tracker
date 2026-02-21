<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="ثبت نام" />

        <form @submit.prevent="submit" class="w-full">
            <div>
                <InputLabel for="name" value="نام" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full text-sm sm:text-base"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="phone" value="شماره موبایل" />

                <TextInput
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full text-sm sm:text-base"
                    v-model="form.phone"
                    required
                    autocomplete="tel"
                />

                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="رمز عبور" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full text-sm sm:text-base"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="تکرار رمز عبور"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full text-sm sm:text-base"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 flex flex-col-reverse sm:flex-row items-center justify-between gap-3 sm:gap-0">
                <Link
                    :href="route('login')"
                    class="text-xs sm:text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    قبلا ثبت نام کردید؟
                </Link>

                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="w-full sm:w-auto">
                    ثبت نام
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
