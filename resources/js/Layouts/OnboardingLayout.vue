<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useBusinessRegistration } from '@/stores/useBusinessRegistration';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const businessStore = useBusinessRegistration();

const props = defineProps({
    currentStep: {
        type: Number,
        required: true,
        validator: (value) => value >= 1 && value <= 4
    }
});

const steps = [
    { 
        number: 1, 
        label: 'Business Info', 
        route: 'business.register',
        description: 'Basic business details'
    },
    { 
        number: 2, 
        label: 'Email Verification', 
        route: 'business.verify-email',
        description: 'Verify your email'
    },
    { 
        number: 3, 
        label: 'CAC Info', 
        route: 'business.cac-info',
        description: 'Business registration details'
    },
    { 
        number: 4, 
        label: 'Director Info', 
        route: 'business.director-info',
        description: 'Director identification'
    }
];

const isStepAccessible = (stepNumber) => {
    return businessStore.canProceedToStep(stepNumber);
};

const goToStep = (step) => {
    if (isStepAccessible(step.number)) {
        window.location = route(step.route);
    }
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <Link href="/">
                                <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Progress Steps -->
                <div class="mb-12">
                    <div class="max-w-3xl mx-auto px-4">
                        <div class="relative">
                            <!-- Progress Bar -->
                            <div class="absolute top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 rounded-full">
                                <div
                                    class="h-full bg-primary-600 rounded-full transition-all duration-500 ease-in-out"
                                    :style="{ width: `${((currentStep - 1) / (steps.length - 1)) * 100}%` }"
                                ></div>
                            </div>

                            <!-- Steps -->
                            <div class="relative flex justify-between">
                                <div
                                    v-for="step in steps"
                                    :key="step.number"
                                    class="flex flex-col items-center"
                                >
                                    <!-- Step Circle -->
                                    <div
                                        class="flex items-center justify-center w-10 h-10 rounded-full transition-all duration-300 ease-in-out transform"
                                        :class="
                                            step.number === currentStep
                                                ? 'bg-green-500 text-white shadow-lg ring-4 ring-green-100 scale-110'
                                                : isStepAccessible(step.number)
                                                    ? 'bg-primary-600 text-white cursor-pointer shadow-lg hover:bg-primary-700 hover:scale-110'
                                                    : step.number < currentStep
                                                        ? 'bg-primary-600 text-white shadow-md'
                                                        : 'bg-white text-gray-400 border-2 border-gray-200'
                                        "
                                        @click="goToStep(step)"
                                    >
                                        <template v-if="step.number < currentStep">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </template>
                                        <template v-else>
                                            <span 
                                                class="text-sm font-semibold"
                                                :class="{ 'animate-bounce': step.number === currentStep }"
                                            >
                                                {{ step.number }}
                                            </span>
                                        </template>
                                    </div>

                                    <!-- Step Label -->
                                    <div class="mt-4 space-y-2">
                                        <span
                                            class="block text-sm font-medium text-center transition-colors duration-300"
                                            :class="{
                                                'text-green-600': step.number === currentStep,
                                                'text-primary-600': step.number < currentStep,
                                                'text-gray-500': step.number > currentStep
                                            }"
                                        >
                                            {{ step.label }}
                                        </span>
                                        <span 
                                            v-if="step.number === currentStep"
                                            class="block text-xs text-green-600 animate-pulse"
                                        >
                                            Current Step
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="max-w-5xl mx-auto">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg transition-all duration-300 hover:shadow-md">
                        <slot></slot>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Gradient Background */
.bg-gradient-to-br {
    background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

/* Progress Bar Animation */
@keyframes progressGrow {
    from { width: 0%; }
    to { width: 100%; }
}

/* Pulse Animation */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Step Transitions */
.step-transition {
    transition: all 0.3s ease-in-out;
}

.step-circle {
    position: relative;
    z-index: 10;
}

/* Hover Effects */
.step-circle:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Active Step Styles */
.step-active {
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
}

/* Complete Step Animation */
@keyframes checkmark {
    0% { 
        transform: scale(0);
        opacity: 0;
    }
    100% { 
        transform: scale(1);
        opacity: 1;
    }
}

.checkmark-animate {
    animation: checkmark 0.3s ease-in-out forwards;
}
</style>
