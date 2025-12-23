<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  invoices: Object,
  filters: Object,
  businesses: Array,
})

const selectedStatus = ref(props.filters?.status || 'all')
const businessSearch = ref('')
const selectedBusinessId = ref('')

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
    alert('Please select a business to generate an invoice for.')
    return
  }

  router.post(route('admin.invoices.generate'), {
    business_id: selectedBusinessId.value,
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

const markPaid = (invoice) => {
  const amount = prompt(`Enter payment amount for invoice ${invoice.invoice_number}`, invoice.balance_due)
  if (!amount) return

  const reference = prompt('Optional: enter payment reference (e.g. transaction ID)', '')
  const paymentMethod = prompt('Optional: enter payment method (e.g. bank_transfer, manual)', 'manual')

  router.post(route('admin.invoices.mark-paid', invoice.id), {
    amount: parseFloat(amount),
    payment_method: paymentMethod || null,
    reference: reference || null,
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
                      @click="markPaid(invoice)"
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
  </AdminLayout>
</template>


