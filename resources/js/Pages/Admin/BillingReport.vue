<template>
    <AdminLayout>
        <Head title="Billing Report" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Comprehensive Billing & Profit Report
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Revenue, Gateway Costs, and Profit Analysis with Network Breakdown
                        <span class="text-black font-medium">(Production Sessions Only)</span>
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <!-- Filters -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <form @submit.prevent="loadReport" class="flex flex-wrap items-end gap-4">
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
                            <option v-for="network in available_networks" :key="network" :value="network">
                                {{ network }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Business</label>
                        <select
                            v-model="filters.business_id"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        >
                            <option value="">All Businesses</option>
                            <option v-for="business in available_businesses" :key="business.id" :value="business.id">
                                {{ business.business_name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="submit"
                            class="px-4 py-2 bg-black text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-black"
                        >
                            Apply Filters
                        </button>
                        <button
                            type="button"
                            @click="clearFilters"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- Platform Summary -->
            <div class="bg-gradient-to-r from-black to-gray-800 shadow-lg rounded-lg p-6 mb-6 text-white">
                <h3 class="text-lg font-semibold mb-4">Platform Summary ({{ summary.period_start }} to {{ summary.period_end }})</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm opacity-90">Total Revenue</p>
                        <p class="text-2xl font-bold">{{ summary.currency_symbol }}{{ formatNumber(summary.revenue) }}</p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Gateway Costs</p>
                        <p class="text-2xl font-bold">{{ summary.currency_symbol }}{{ formatNumber(summary.gateway_costs) }}</p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Net Profit</p>
                        <p class="text-2xl font-bold" :class="summary.profit >= 0 ? 'text-green-200' : 'text-red-200'">
                            {{ summary.currency_symbol }}{{ formatNumber(summary.profit) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Profit Margin</p>
                        <p class="text-2xl font-bold" :class="summary.margin_percentage >= 0 ? 'text-green-200' : 'text-red-200'">
                            {{ formatNumber(summary.margin_percentage) }}%
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-400 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="opacity-90">Total Sessions</p>
                        <p class="text-lg font-semibold">{{ summary.total_sessions }}</p>
                    </div>
                    <div>
                        <p class="opacity-90">Avg Revenue/Session</p>
                        <p class="text-lg font-semibold">{{ summary.currency_symbol }}{{ formatNumber(summary.avg_revenue_per_session, 4) }}</p>
                    </div>
                    <div>
                        <p class="opacity-90">Avg Cost/Session</p>
                        <p class="text-lg font-semibold">{{ summary.currency_symbol }}{{ formatNumber(summary.avg_cost_per_session, 4) }}</p>
                    </div>
                    <div>
                        <p class="opacity-90">Avg Profit/Session</p>
                        <p class="text-lg font-semibold" :class="summary.avg_profit_per_session >= 0 ? 'text-green-200' : 'text-red-200'">
                            {{ summary.currency_symbol }}{{ formatNumber(summary.avg_profit_per_session, 4) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Data Quality Alert -->
            <div v-if="summary.sessions_without_gateway_cost > 0" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Data Quality Notice:</strong> {{ summary.sessions_without_gateway_cost }} sessions ({{ ((summary.sessions_without_gateway_cost / summary.total_sessions) * 100).toFixed(1) }}%) 
                            are missing gateway cost data. These sessions may have occurred before cost tracking was implemented or during testing.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Network Breakdown -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Network Breakdown</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Network</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gateway Costs</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Profit</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Margin %</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Cost/Session</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Revenue/Session</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="network in network_breakdown" :key="network.network">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ network.network }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ formatNumber(network.sessions, 0) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                    {{ summary.currency_symbol }}{{ formatNumber(network.revenue) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    {{ summary.currency_symbol }}{{ formatNumber(network.gateway_costs) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium" :class="network.profit >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ summary.currency_symbol }}{{ formatNumber(network.profit) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium" :class="network.margin_percentage >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ formatNumber(network.margin_percentage) }}%
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    {{ summary.currency_symbol }}{{ formatNumber(network.avg_cost_per_session, 4) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    {{ summary.currency_symbol }}{{ formatNumber(network.avg_revenue_per_session, 4) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Business Breakdown -->
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Business Breakdown</h3>
                    <div class="text-sm text-gray-600">
                        Showing {{ business_breakdown.length }} businesses
                        ({{ summary.profitable_businesses }} profitable, {{ summary.unprofitable_businesses }} unprofitable)
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Billing Method</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Session Price</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gateway Costs</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Profit</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Margin %</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template v-for="business in business_breakdown" :key="business.id">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ business.business_name }}</div>
                                        <div class="text-sm text-gray-500">{{ business.business_email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="business.billing_method === 'prepaid' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">
                                            {{ business.billing_method }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ formatNumber(business.sessions, 0) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                        {{ business.currency || summary.currency }}{{ formatNumber(business.session_price, 4) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                        {{ business.currency || summary.currency }}{{ formatNumber(business.revenue) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ summary.currency_symbol }}{{ formatNumber(business.gateway_costs) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium" :class="business.profit >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ summary.currency_symbol }}{{ formatNumber(business.profit) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium" :class="business.margin_percentage >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatNumber(business.margin_percentage) }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <Link
                                            :href="route('admin.billing-report.business.sessions', business.id)"
                                            class="text-black hover:text-gray-800 font-medium"
                                        >
                                            View Sessions
                                        </Link>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    summary: Object,
    platform_summary: Object,
    network_breakdown: Array,
    business_breakdown: Array,
    filters: Object,
    available_networks: Array,
    available_businesses: Array,
})

const filters = ref({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    network: props.filters?.network || '',
    business_id: props.filters?.business_id || '',
})

const formatNumber = (value, decimals = 2) => {
    if (value === null || value === undefined) return '0.00'
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    })
}

const loadReport = () => {
    router.get(route('admin.billing-report'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    filters.value = {
        start_date: '',
        end_date: '',
        network: '',
        business_id: '',
    }
    loadReport()
}
</script>