<template>
  <Transition
    enter-active-class="ease-out duration-300"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click="handleBackdropClick">
      <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div
            class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
            @click.stop
          >
            <!-- Icon -->
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full" :class="iconBgClass">
              <component :is="icon" class="h-6 w-6" :class="iconColorClass" />
            </div>

            <!-- Content -->
            <div class="mt-3 text-center sm:mt-5">
              <h3 class="text-base font-semibold leading-6 text-gray-900">
                {{ title }}
              </h3>
              <div class="mt-2">
                <p class="text-sm text-gray-500">
                  {{ message }}
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
              <button
                type="button"
                class="inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 sm:col-start-2"
                :class="confirmButtonClass"
                @click="confirm"
              >
                {{ confirmText }}
              </button>
              <button
                type="button"
                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-1 sm:mt-0"
                @click="cancel"
              >
                {{ cancelText }}
              </button>
            </div>

            <!-- Close button -->
            <button
              type="button"
              class="absolute right-4 top-4 rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              @click="cancel"
            >
              <span class="sr-only">Close</span>
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </Transition>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Confirm Action',
  },
  message: {
    type: String,
    default: 'Are you sure you want to proceed?',
  },
  confirmText: {
    type: String,
    default: 'Confirm',
  },
  cancelText: {
    type: String,
    default: 'Cancel',
  },
  type: {
    type: String,
    default: 'warning', // warning, danger, info, success
  },
});

const emit = defineEmits(['confirm', 'cancel']);

const icon = computed(() => {
  switch (props.type) {
    case 'danger':
      return 'ExclamationTriangleIcon';
    case 'success':
      return 'CheckCircleIcon';
    case 'info':
      return 'InformationCircleIcon';
    default:
      return 'ExclamationTriangleIcon';
  }
});

const iconBgClass = computed(() => {
  switch (props.type) {
    case 'danger':
      return 'bg-red-100';
    case 'success':
      return 'bg-green-100';
    case 'info':
      return 'bg-blue-100';
    default:
      return 'bg-yellow-100';
  }
});

const iconColorClass = computed(() => {
  switch (props.type) {
    case 'danger':
      return 'text-red-600';
    case 'success':
      return 'text-green-600';
    case 'info':
      return 'text-blue-600';
    default:
      return 'text-yellow-600';
  }
});

const confirmButtonClass = computed(() => {
  switch (props.type) {
    case 'danger':
      return 'bg-red-600 hover:bg-red-500 focus-visible:outline-red-600';
    case 'success':
      return 'bg-green-600 hover:bg-green-500 focus-visible:outline-green-600';
    case 'info':
      return 'bg-blue-600 hover:bg-blue-500 focus-visible:outline-blue-600';
    default:
      return 'bg-yellow-600 hover:bg-yellow-500 focus-visible:outline-yellow-600';
  }
});

function confirm() {
  emit('confirm');
}

function cancel() {
  emit('cancel');
}

function handleBackdropClick() {
  cancel();
}
</script>

<script>
// Define the icon components
export default {
  components: {
    ExclamationTriangleIcon: {
      template: `
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
      `
    },
    CheckCircleIcon: {
      template: `
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      `
    },
    InformationCircleIcon: {
      template: `
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
      `
    }
  }
};
</script> 