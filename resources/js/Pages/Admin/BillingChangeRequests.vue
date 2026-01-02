<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Billing Change Requests
            </h2>
        </template>

        <div class="py-12">
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <select
                                v-model="statusFilter"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                @change="filterRequests"
                            >
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Business
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Current Method
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Requested Method
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reason
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Requested By
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="request in requests.data" :key="request.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ request.business.business_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ request.business.business_email }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ formatBillingMethod(request.current_method) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ formatBillingMethod(request.requested_method) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ request.reason || 'No reason provided' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ request.requester.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ request.requester.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusBadgeClass(request.status)">
                                            {{ formatStatus(request.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(request.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div v-if="request.status === 'pending'" class="flex space-x-2">
                                            <button @click="openApproveModal(request)" class="text-green-600 hover:text-green-900">
                                                Approve
                                            </button>
                                            <button @click="openRejectModal(request)" class="text-red-600 hover:text-red-900">
                                                Reject
                                            </button>
                                        </div>
                                        <div v-else class="text-gray-400">
                                            {{ request.admin_notes ? 'Reviewed' : '-' }}
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="requests.data.length === 0">
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No billing change requests found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="requests.links && requests.links.length > 3" class="mt-4 flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link :href="requests.prev_page_url" :class="{'opacity-50 cursor-not-allowed': !requests.prev_page_url}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </Link>
                            <Link :href="requests.next_page_url" :class="{'opacity-50 cursor-not-allowed': !requests.next_page_url}" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium">{{ requests.from }}</span> to <span class="font-medium">{{ requests.to }}</span> of <span class="font-medium">{{ requests.total }}</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <template v-for="(link, index) in requests.links" :key="index">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="[
                                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                link.active
                                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === requests.links.length - 1 ? 'rounded-r-md' : ''
                                            ]"
                                        />
                                        <span
                                            v-else
                                            v-html="link.label"
                                            :class="[
                                                'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === requests.links.length - 1 ? 'rounded-r-md' : ''
                                            ]"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div v-if="showApproveModal && selectedRequest" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Approve Billing Method Change</h3>
                    <form @submit.prevent="approveRequest">
                        <div v-if="selectedRequest.requested_method === 'postpaid'" class="mb-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Credit Limit</label>
                                <input
                                    v-model="approveForm.credit_limit"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter credit limit"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Terms (Days)</label>
                                <input
                                    v-model="approveForm.payment_terms_days"
                                    type="number"
                                    min="1"
                                    max="365"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="e.g., 15 for Net 15"
                                />
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                            <textarea
                                v-model="approveForm.admin_notes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Add any notes about this approval..."
                            />
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeApproveModal"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                            >
                                Approve
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="showRejectModal && selectedRequest" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Billing Method Change</h3>
                    <form @submit.prevent="rejectRequest">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                            <textarea
                                v-model="rejectForm.admin_notes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                placeholder="Please provide a reason for rejection..."
                                required
                            />
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeRejectModal"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                            >
                                Reject
                            </button>
                        </div>
                    </form>
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
    requests: Object,
    filters: Object,
})

const statusFilter = ref(props.filters?.status || 'all')
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const selectedRequest = ref(null)

const approveForm = ref({
    credit_limit: '',
    payment_terms_days: '',
    admin_notes: '',
})

const rejectForm = ref({
    admin_notes: '',
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const formatBillingMethod = (method) => {
    const methods = {
        'prepaid': 'Prepaid',
        'postpaid': 'Postpaid',
    }
    return methods[method] || method
}

const formatStatus = (status) => {
    const statuses = {
        'pending': 'Pending',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'cancelled': 'Cancelled',
    }
    return statuses[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'pending': 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800',
        'approved': 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800',
        'rejected': 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800',
        'cancelled': 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800',
    }
    return classes[status] || 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800'
}

const filterRequests = () => {
    router.get(route('admin.billing-change-requests'), {
        status: statusFilter.value === 'all' ? null : statusFilter.value,
    }, {
        preserveState: true,
        replace: true,
    })
}

const openApproveModal = (request) => {
    selectedRequest.value = request
    approveForm.value = {
        credit_limit: '',
        payment_terms_days: '',
        admin_notes: '',
    }
    showApproveModal.value = true
}

const closeApproveModal = () => {
    showApproveModal.value = false
    selectedRequest.value = null
}

const openRejectModal = (request) => {
    selectedRequest.value = request
    rejectForm.value = {
        admin_notes: '',
    }
    showRejectModal.value = true
}

const closeRejectModal = () => {
    showRejectModal.value = false
    selectedRequest.value = null
}

const approveRequest = () => {
    router.post(route('admin.billing-change-requests.approve', selectedRequest.value.id), approveForm.value, {
        onSuccess: () => {
            closeApproveModal()
        },
    })
}

const rejectRequest = () => {
    router.post(route('admin.billing-change-requests.reject', selectedRequest.value.id), rejectForm.value, {
        onSuccess: () => {
            closeRejectModal()
        },
    })
}
</script>


