<template>
    <OnboardingLayout :current-step="4">
        <div class="max-w-2xl mx-auto p-6">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold mb-4">Verify Your Email Address (Optional)</h2>
                <p class="text-gray-600">
                    Email verification is optional. You can verify your email now or skip this step and continue to your dashboard.
                    If you choose to verify, please check your email for a verification link.
                </p>
            </div>

            <div v-if="status" class="mb-6">
                <div v-if="status === 'verification-link-sent'" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex flex-col items-center space-y-4">
                <form @submit.prevent="submit" class="w-full max-w-sm">
                    <PrimaryButton type="submit" :class="{ 'opacity-50': processing }" :disabled="processing" class="w-full mb-4">
                        {{ processing ? 'Sending...' : 'Send Verification Email' }}
                    </PrimaryButton>
                </form>

                <div class="text-center">
                    <p class="text-gray-500 mb-4">or</p>
                    <SecondaryButton
                        type="button"
                        @click="skipVerification"
                        class="w-full"
                    >
                        Skip Email Verification
                    </SecondaryButton>
                </div>
            </div>
        </div>
    </OnboardingLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useBusinessRegistration } from '@/stores/useBusinessRegistration';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const businessStore = useBusinessRegistration();

const props = defineProps({
    status: {
        type: String,
        default: null
    }
});

const form = useForm({});

onMounted(() => {
    businessStore.initializeFromLocalStorage();

    // Redirect if user hasn't completed previous steps
    if (!businessStore.canProceedToStep(4)) {
        window.location = route('business.director-info');
        return;
    }

    // If email is already verified, proceed to dashboard
    if (businessStore.isEmailVerified) {
        window.location = route('dashboard');
        return;
    }
});

const submit = () => {
    form.post(route('business.verification.send'), {
        onSuccess: (response) => {
            if (response?.props?.status === 'verification-link-sent') {
                businessStore.setCurrentStep(4);
            }
        },
    });
};

const skipVerification = () => {
    form.post(route('business.skip-email-verification'), {
        onSuccess: () => {
            businessStore.setEmailVerified(true);
            businessStore.clearRegistrationData();
            window.location = route('dashboard');
        },
    });
};
</script>
