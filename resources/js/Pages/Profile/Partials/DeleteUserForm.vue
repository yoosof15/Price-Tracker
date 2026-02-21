<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                حذف حساب کاربری
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                پس از حذف حساب کاربری، تمام اطلاعات و داده‌های آن برای همیشه حذف خواهند شد. قبل از حذف حساب، لطفاً اطلاعاتی که می‌خواهید نگه دارید را دانلود کنید.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion" class="w-full sm:w-auto">حذف حساب کاربری</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-4 sm:p-6">
                <h2
                    class="text-base sm:text-lg font-medium text-gray-900"
                >
                    آیا مطمئن هستید که می‌خواهید حساب خود را حذف کنید؟
                </h2>

                <p class="mt-1 text-xs sm:text-sm text-gray-600">
                    پس از حذف حساب کاربری، تمام اطلاعات و داده‌های آن برای همیشه حذف خواهند شد. لطفاً رمز عبور خود را وارد کنید تا حذف دائمی حساب خود را تأیید کنید.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="رمز عبور"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full sm:w-3/4 text-sm"
                        placeholder="رمز عبور"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row gap-2 sm:gap-0 sm:justify-end">
                    <SecondaryButton @click="closeModal" class="w-full sm:w-auto">
                        انصراف
                    </SecondaryButton>

                    <DangerButton
                        class="w-full sm:w-auto sm:ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        حذف حساب کاربری
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
