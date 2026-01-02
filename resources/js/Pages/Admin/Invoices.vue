<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import FormModal from '@/Components/FormModal.vue'
import { showToast } from '@/helpers/toast'

const props = defineProps({
  invoices: Object,
  filters: Object,
  businesses: Array,
})

const selectedStatus = ref(props.filters?.status || 'all')
const businessSearch = ref('')
const selectedBusinessId = ref('')

// Payment modal state
const showPaymentModal = ref(false)
const selectedInvoice = ref(null)
const paymentAmount = ref('')
const paymentReference = ref('')
const paymentMethod = ref('manual')
const isSubmitting = ref(false)

const statusOptions = [
  { value: 'all', label: 'All statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'sent', label: 'Sent' },
  { value: 'paid', label: 'Paid' },
  { value: 'overdue', label: 'Overdue' },
  { value: 'cancelled', label: 'Cancelled' },
]

const applyFilters = () => {
  router.get(route('admin.invoices'), { status: selectedStatus.value }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const generateInvoice = () => {
  if (!selectedBusinessId.value) {
    showToast.warning('Please select a business to generate an invoice for.')
    return
  }

  router.post(route('admin.invoices.generate'), {
    business_id: selectedBusinessId.value,
  }, {
    onSuccess: () => {
      showToast.success('Invoice generated successfully.')
    },
    onError: (errors) => {
      const errorMessage = errors.message || 'Failed to generate invoice. Please try again.'
      showToast.error(errorMessage)
    }
  })
}

const filteredBusinesses = computed(() => {
  const term = businessSearch.value.toLowerCase().trim()
  if (!term) return props.businesses
  return props.businesses.filter((b) =>
    b.business_name.toLowerCase().includes(term) ||
    String(b.id).includes(term),
  )
})

const openPaymentModal = (invoice) => {
  selectedInvoice.value = invoice
  paymentAmount.value = invoice.balance_due || ''
  paymentReference.value = ''
  paymentMethod.value = 'manual'
  showPaymentModal.value = true
}

const closePaymentModal = () => {
  showPaymentModal.value = false
  selectedInvoice.value = null
  paymentAmount.value = ''
  paymentReference.value = ''
  paymentMethod.value = 'manual'
  isSubmitting.value = false
}

const confirmPayment = () => {
  if (!selectedInvoice.value) return

  // Validate amount
  const amount = parseFloat(paymentAmount.value)
  if (!amount || amount <= 0) {
    showToast.error('Please enter a valid payment amount.')
    return
  }

  isSubmitting.value = true

  router.post(route('admin.invoices.mark-paid', selectedInvoice.value.id), {
    amount: amount,
    payment_method: paymentMethod.value || null,
    reference: paymentReference.value || null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showToast.success(`Payment of ${selectedInvoice.value.currency} ${amount.toFixed(2)} recorded successfully for invoice ${selectedInvoice.value.invoice_number}.`)
      closePaymentModal()
    },
    onError: (errors) => {
      const errorMessage = errors.message || 'Failed to record payment. Please try again.'
      showToast.error(errorMessage)
      isSubmitting.value = false
    }
  })
}
</script>

<template>
  <AdminLayout>
    <Head title="Invoices" />

    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
          <p class="text-sm text-gray-600 mt-1">
            Manage postpaid billing invoices and mark them as paid.
          </p>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Generate Invoice -->
        <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-100">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Generate Billing Cycle Invoice</h2>
          <div class="flex flex-col md:flex-row md:items-end md:space-x-4 space-y-3 md:space-y-0">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">Select Business</label>
              <div class="flex space-x-2">
                <div class="flex-1">
                  <input
                    v-model="businessSearch"
                    type="text"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mb-2"
                    placeholder="Search by name or ID..."
                  />
                  <select
                    v-model="selectedBusinessId"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="">Select a business</option>
                    <option
                      v-for="b in filteredBusinesses"
                      :key="b.id"
                      :value="b.id"
                    >
                      {{ b.business_name }} (ID: {{ b.id }})
                    </option>
                  </select>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Start typing to search and select a business to generate an invoice for.
              </p>
            </div>
            <div class="flex-shrink-0">
              <button
                type="button"
                @click="generateInvoice"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Generate Invoice
              </button>
            </div>
          </div>
        </div>

        <!-- Invoice List -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-100">
          <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Invoice List</h2>
            <div class="flex items-center space-x-3">
              <label class="text-sm text-gray-600">Status:</label>
              <select
                v-model="selectedStatus"
                @change="applyFilters"
                class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option
                  v-for="opt in statusOptions"
                  :key="opt.value"
                  :value="opt.value"
                >
                  {{ opt.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance Due</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="invoice in invoices.data" :key="invoice.id">
                  <td class="px-4 py-3 text-sm font-mono text-gray-900">
                    {{ invoice.invoice_number }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-800">
                    <div class="font-medium">
                      {{ invoice.business?.business_name || `Business #${invoice.business_id}` }}
                    </div>
                    <div class="text-xs text-gray-500">
                      ID: {{ invoice.business_id }}
                    </div>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-700">
                    <div>{{ invoice.period_start }} → {{ invoice.period_end }}</div>
                    <div class="text-xs text-gray-500">
                      Due: {{ invoice.due_date }}
                    </div>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900 font-semibold">
                    {{ invoice.currency }} {{ Number(invoice.total_amount || 0).toFixed(2) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900">
                    {{ invoice.currency }} {{ Number(invoice.balance_due || 0).toFixed(2) }}
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                        'bg-green-100 text-green-800': invoice.status === 'paid',
                        'bg-yellow-100 text-yellow-800': invoice.status === 'sent' || invoice.status === 'overdue',
                        'bg-gray-100 text-gray-800': invoice.status === 'draft',
                        'bg-red-100 text-red-800': invoice.status === 'cancelled',
                      }"
                    >
                      {{ invoice.status?.toUpperCase() }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-right space-x-2">
                    <button
                      v-if="invoice.status !== 'paid' && Number(invoice.balance_due) > 0"
                      type="button"
                      @click="openPaymentModal(invoice)"
                      class="inline-flex items-center px-3 py-1.5 border border-green-600 text-green-700 text-xs font-medium rounded-md hover:bg-green-50"
                    >
                      Mark Paid
                    </button>
                  </td>
                </tr>
                <tr v-if="!invoices.data.length">
                  <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                    No invoices found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div
            v-if="invoices.links && invoices.links.length > 3"
            class="px-6 py-4 border-t border-gray-200 flex items-center justify-between"
          >
            <div class="text-sm text-gray-700">
              Showing {{ invoices.from }} to {{ invoices.to }} of {{ invoices.total }} results
            </div>
            <div class="flex space-x-1">
              <button
                v-for="link in invoices.links"
                :key="link.label"
                type="button"
                @click="link.url && router.visit(link.url)"
                v-html="link.label"
                :class="[
                  'px-3 py-1.5 text-xs rounded-md',
                  link.active
                    ? 'bg-blue-600 text-white'
                    : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50',
                  !link.url ? 'opacity-50 cursor-not-allowed' : '',
                ]"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Confirmation Modal -->
    <FormModal
      :show="showPaymentModal"
      title="Record Payment"
      confirm-text="Confirm Payment"
      cancel-text="Cancel"
      type="success"
      :loading="isSubmitting"
      @confirm="confirmPayment"
      @cancel="closePaymentModal"
    >
      <div v-if="selectedInvoice" class="space-y-4">
        <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
          <p class="text-sm text-blue-800">
            <span class="font-semibold">Invoice:</span> {{ selectedInvoice.invoice_number }}
          </p>
          <p class="text-sm text-blue-800 mt-1">
            <span class="font-semibold">Business:</span> {{ selectedInvoice.business?.business_name || `Business #${selectedInvoice.business_id}` }}
          </p>
          <p class="text-sm text-blue-800 mt-1">
            <span class="font-semibold">Balance Due:</span> {{ selectedInvoice.currency }} {{ Number(selectedInvoice.balance_due || 0).toFixed(2) }}
          </p>
        </div>

        <div>
          <label for="payment-amount" class="block text-sm font-medium text-gray-700 mb-1">
            Payment Amount <span class="text-red-500">*</span>
          </label>
          <input
            id="payment-amount"
            v-model="paymentAmount"
            type="number"
            step="0.01"
            min="0.01"
            :max="selectedInvoice.balance_due"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
            placeholder="Enter payment amount"
            required
          />
          <p class="mt-1 text-xs text-gray-500">
            Maximum: {{ selectedInvoice.currency }} {{ Number(selectedInvoice.balance_due || 0).toFixed(2) }}
          </p>
        </div>

        <div>
          <label for="payment-method" class="block text-sm font-medium text-gray-700 mb-1">
            Payment Method
          </label>
          <select
            id="payment-method"
            v-model="paymentMethod"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="manual">Manual</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="card">Card Payment</option>
            <option value="cash">Cash</option>
            <option value="cheque">Cheque</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div>
          <label for="payment-reference" class="block text-sm font-medium text-gray-700 mb-1">
            Payment Reference (Optional)
          </label>
          <input
            id="payment-reference"
            v-model="paymentReference"
            type="text"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
            placeholder="e.g. Transaction ID, Receipt Number"
          />
          <p class="mt-1 text-xs text-gray-500">
            Transaction ID, receipt number, or other reference for this payment
          </p>
        </div>
      </div>
    </FormModal>
  </AdminLayout>
</template>


