<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    phone: user.phone,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                اطلاعات پروفایل
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                اطلاعات حساب کاربری و شماره موبایل خود را بروزرسانی کنید.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
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

            <div>
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

            <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-2 sm:gap-4">
                <PrimaryButton :disabled="form.processing" class="w-full sm:w-auto">ذخیره</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs sm:text-sm text-gray-600"
                    >
                        ذخیره شد.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
