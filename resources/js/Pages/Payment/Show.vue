<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Payment Details
        </h2>
        <Link
          :href="route('payment.history')"
          class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-medium"
        >
          Back to History
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Payment Details Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-medium text-gray-900">Payment Information</h3>
              <span :class="payment.status_class" class="px-3 py-1 text-sm font-semibold rounded-full">
                {{ payment.status }}
              </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Payment Details -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-500">Reference</label>
                  <p class="mt-1 text-sm text-gray-900 font-mono">{{ payment.reference }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-500">Amount</label>
                  <p class="mt-1 text-2xl font-bold text-gray-900">{{ payment.formatted_amount }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-500">Gateway</label>
                  <p class="mt-1 text-sm text-gray-900">{{ payment.gateway_name }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-500">Currency</label>
                  <p class="mt-1 text-sm text-gray-900">{{ payment.currency }}</p>
                </div>
              </div>

              <!-- Payment Timeline -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-500">Created</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(payment.created_at) }}</p>
                </div>
                
                <div v-if="payment.completed_at">
                  <label class="block text-sm font-medium text-gray-500">Completed</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(payment.completed_at) }}</p>
                </div>
                
                <div v-if="payment.metadata">
                  <label class="block text-sm font-medium text-gray-500">Additional Info</label>
                  <div class="mt-1 text-sm text-gray-900">
                    <pre class="bg-gray-50 p-2 rounded text-xs">{{ JSON.stringify(payment.metadata, null, 2) }}</pre>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Gateway Response (if available) -->
        <div v-if="payment.gateway_response" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Gateway Response</h3>
            <div class="bg-gray-50 p-4 rounded-lg">
              <pre class="text-sm text-gray-700 overflow-x-auto">{{ JSON.stringify(payment.gateway_response, null, 2) }}</pre>
            </div>
          </div>
        </div>

        <!-- Manual Verification (for admin) -->
        <div v-if="payment.gateway === 'manual' && payment.status === 'pending'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Manual Verification</h3>
            <p class="text-sm text-gray-600 mb-4">
              This payment requires manual verification. Please verify the bank transfer and update the payment status.
            </p>
            <div class="flex space-x-3">
              <button
                @click="verifyPayment(true)"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium"
              >
                Mark as Verified
              </button>
              <button
                @click="verifyPayment(false)"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium"
              >
                Mark as Failed
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  payment: Object
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const verifyPayment = async (verified) => {
  try {
    await router.post(route('payment.verify', props.payment.id), {
      verified: verified
    })
  } catch (error) {
    console.error('Payment verification failed:', error)
  }
}
</script>
