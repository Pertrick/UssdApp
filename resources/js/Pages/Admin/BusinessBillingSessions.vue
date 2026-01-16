<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Billing Sessions: {{ business.business_name }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Individual session details with session strings for auditing
                    </p>
                </div>
                <Link :href="route('admin.billing-report')" class="inline-flex items-center bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Report
                </Link>
            </div>
        </template>

        <div class="py-12">
            <!-- Summary Card -->
            <div class="bg-gradient-to-r from-black to-gray-800 shadow-lg rounded-lg p-6 mb-6 text-white">
                <h3 class="text-lg font-semibold mb-4">Summary ({{ summary.period_start }} to {{ summary.period_end }})</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm opacity-90">Total Sessions</p>
                        <p class="text-2xl font-bold">{{ formatNumber(summary.total_sessions, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Total Revenue</p>
                        <p class="text-2xl font-bold">{{ summary.currency_symbol }}{{ formatNumber(summary.total_revenue) }}</p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Gateway Costs</p>
                        <p class="text-2xl font-bold">{{ summary.currency_symbol }}{{ formatNumber(summary.total_gateway_costs) }}</p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Net Profit</p>
                        <p class="text-2xl font-bold" :class="summary.total_profit >= 0 ? 'text-green-200' : 'text-red-200'">
                            {{ summary.currency_symbol }}{{ formatNumber(summary.total_profit) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <form @submit.prevent="loadSessions" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input
                            type="date"
                            v-model="filters.start_date"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input
                            type="date"
                            v-model="filters.end_date"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Network</label>
                        <select
                            v-model="filters.network"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        >
                            <option value="">All Networks</option>
                            <template v-if="availableNetworks && availableNetworks.length > 0">
                                <option v-for="network in availableNetworks" :key="network" :value="network">{{ network }}</option>
                            </template>
                            <template v-else>
                                <option value="MTN">MTN</option>
                                <option value="Airtel">Airtel</option>
                                <option value="Glo">Glo</option>
                                <option value="9mobile">9mobile</option>
                            </template>
                        </select>
                    </div>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-black text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-black"
                    >
                        Update
                    </button>
                    <button
                        type="button"
                        @click="clearFilters"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                        Clear
                    </button>
                </form>
            </div>

            <!-- Sessions Table -->
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S/N</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Network</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gateway Cost</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Profit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(session, index) in sessions.data" :key="session.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    {{ getSerialNumber(index) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDateTime(session.billed_at) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">{{ session.session_id }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ session.phone_number || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ session.network_provider || 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                    {{ summary.currency_symbol }}{{ formatNumber(session.billing_amount, 4) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    {{ summary.currency_symbol }}{{ formatGatewayCost(session.gateway_cost) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium" :class="getProfitClass(session)">
                                    {{ summary.currency_symbol }}{{ formatProfit(session) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusBadgeClass(session.billing_status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                        {{ session.billing_status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="sessions.data.length === 0">
                                <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No sessions found for the selected filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="sessions && (sessions.total > sessions.per_page || sessions.last_page > 1)" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link v-if="sessions.prev_page_url" :href="buildPaginationUrl(sessions.prev_page_url)" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </Link>
                            <Link v-if="sessions.next_page_url" :href="buildPaginationUrl(sessions.next_page_url)" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ sessions.from }}</span>
                                    to
                                    <span class="font-medium">{{ sessions.to }}</span>
                                    of
                                    <span class="font-medium">{{ sessions.total }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <template v-for="(link, index) in sessions.links" :key="index">
                                        <Link
                                            v-if="link.url"
                                            :href="buildPaginationUrl(link.url)"
                                            v-html="link.label"
                                            :class="[
                                                'relative inline-flex items-center px-2 py-2 border border-gray-300 text-sm font-medium',
                                                link.active
                                                    ? 'z-10 bg-gray-100 border-black text-black'
                                                    : 'bg-white text-gray-500 hover:bg-gray-50',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === sessions.links.length - 1 ? 'rounded-r-md' : ''
                                            ]"
                                        ></Link>
                                        <span
                                            v-else
                                            v-html="link.label"
                                            :class="[
                                                'relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-default',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === sessions.links.length - 1 ? 'rounded-r-md' : ''
                                            ]"
                                        ></span>
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    business: Object,
    sessions: Object,
    summary: Object,
    filters: Object,
    availableNetworks: {
        type: Array,
        default: () => []
    },
})

const filters = ref({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    network: props.filters?.network || '',
})

const formatNumber = (value, decimals = 2) => {
    if (value === null || value === undefined) return '0.' + '0'.repeat(decimals)
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    })
}

const formatDateTime = (dateString) => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const formatGatewayCost = (costInSmallestUnit) => {
    if (!costInSmallestUnit) return formatNumber(0, 4)
    // Convert from smallest unit (kobo) to main currency
    const cost = costInSmallestUnit / 100
    return formatNumber(cost, 4)
}

const formatProfit = (session) => {
    const revenue = parseFloat(session.billing_amount) || 0
    const cost = (session.gateway_cost || 0) / 100
    const profit = revenue - cost
    return formatNumber(profit, 4)
}

const getProfitClass = (session) => {
    const revenue = parseFloat(session.billing_amount) || 0
    const cost = (session.gateway_cost || 0) / 100
    const profit = revenue - cost
    return profit >= 0 ? 'text-green-600' : 'text-red-600'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'charged': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'failed': 'bg-red-100 text-red-800',
        'refunded': 'bg-gray-100 text-gray-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getSerialNumber = (index) => {
    // Calculate serial number based on current page and index
    const currentPage = props.sessions?.current_page || 1
    const perPage = props.sessions?.per_page || 50
    return (currentPage - 1) * perPage + index + 1
}

const loadSessions = () => {
    router.get(route('admin.billing-report.business.sessions', props.business.id), {
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
        network: filters.value.network,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    filters.value = {
        start_date: '',
        end_date: '',
        network: '',
    }
    loadSessions()
}

const buildPaginationUrl = (url) => {
    if (!url) return '#'
    // Preserve filters in pagination links
    const urlObj = new URL(url, window.location.origin)
    if (filters.value.start_date) {
        urlObj.searchParams.set('start_date', filters.value.start_date)
    }
    if (filters.value.end_date) {
        urlObj.searchParams.set('end_date', filters.value.end_date)
    }
    if (filters.value.network) {
        urlObj.searchParams.set('network', filters.value.network)
    }
    return urlObj.pathname + urlObj.search
}
</script>
