<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Business Details
                </h2>
                <div class="flex space-x-2">
                    <Link :href="route('admin.businesses')" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <!-- Status Banner -->
            <div class="mb-6">
                <div :class="getStatusBannerClass()" class="p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path v-if="business.registration_status === 'completed_unverified'" fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    <path v-else-if="business.registration_status === 'under_review'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    <path v-else-if="business.registration_status === 'verified'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    <path v-else-if="business.registration_status === 'rejected'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    <path v-else fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium">
                                    {{ getStatusTitle() }}
                                </h3>
                                <div class="mt-1 text-sm">
                                    {{ getStatusDescription() }}
                                </div>
                            </div>
                        </div>
                        <div v-if="canTakeAction()" class="flex space-x-2">
                            <button v-if="business.registration_status === 'completed_unverified'" @click="startReview" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm">
                                Start Review
                            </button>
                            <template v-if="business.registration_status === 'under_review'">
                                <button @click="showApprovalModal = true" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm">
                                    Approve
                                </button>
                                <button @click="showRejectionModal = true" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm">
                                    Reject
                                </button>
                                <button @click="showSuspensionModal = true" class="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 text-sm">
                                    Suspend
                                </button>
                            </template>
                            <!-- Removed approve/reject buttons for completed_unverified - only Start Review is needed -->
                            <!-- Show approve/reject for email_verification_pending as well -->
                            <template v-if="business.registration_status === 'email_verification_pending'">
                                <button @click="showApprovalModal = true" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm">
                                    Approve
                                </button>
                                <button @click="showRejectionModal = true" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm">
                                    Reject
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Business Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Business Overview -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Business Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Business Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.business_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Business Email</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.business_email }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.phone }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Location</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.city }}, {{ business.state }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Address</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.address }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CAC Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">CAC Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">CAC Number</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.cac_number || 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Business Type</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ formatBusinessType(business.business_type) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Registration Date</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.registration_date ? formatDate(business.registration_date) : 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">CAC Document</label>
                                    <div class="mt-1">
                                        <a v-if="business.cac_document_path" :href="route('admin.businesses.documents.download', [business.id, 'cac'])" class="text-blue-600 hover:text-blue-800 text-sm">
                                            Download CAC Document
                                        </a>
                                        <span v-else class="text-sm text-gray-500">No document uploaded</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Director Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Director Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Director Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.director_name || 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Director Email</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.director_email || 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Director Phone</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.director_phone || 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">ID Type</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ formatIdType(business.director_id_type) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">ID Number</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.director_id_number || 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">ID Document</label>
                                    <div class="mt-1">
                                        <a v-if="business.director_id_path" :href="route('admin.businesses.documents.download', [business.id, 'director_id'])" class="text-blue-600 hover:text-blue-800 text-sm">
                                            Download ID Document
                                        </a>
                                        <span v-else class="text-sm text-gray-500">No document uploaded</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Review History -->
                    <div v-if="business.approval_notes || business.rejection_reason || business.suspension_reason" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Review History</h3>
                            <div class="space-y-4">
                                <div v-if="business.approval_notes" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <h4 class="text-sm font-medium text-green-800">Approval Notes</h4>
                                    <p class="mt-1 text-sm text-green-700">{{ business.approval_notes }}</p>
                                    <p class="mt-2 text-xs text-green-600">Approved on {{ formatDate(business.verified_at) }}</p>
                                </div>
                                <div v-if="business.rejection_reason" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <h4 class="text-sm font-medium text-red-800">Rejection Reason</h4>
                                    <p class="mt-1 text-sm text-red-700">{{ business.rejection_reason }}</p>
                                </div>
                                <div v-if="business.suspension_reason" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <h4 class="text-sm font-medium text-yellow-800">Suspension Reason</h4>
                                    <p class="mt-1 text-sm text-yellow-700">{{ business.suspension_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Owner Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Owner Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.user.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ business.user.email }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Member Since</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ formatDate(business.user.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Timeline -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Registration Timeline</h3>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-gray-900">Registration Started</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(business.created_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="business.verified_at" class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-gray-900">Verified</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(business.verified_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Billing Information</h3>
                                <button @click="openBillingModal" class="text-sm text-blue-600 hover:text-blue-800">
                                    Update
                                </button>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Billing Method</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <span :class="getBillingMethodBadgeClass(business.billing_method)">
                                            {{ formatBillingMethod(business.billing_method) }}
                                        </span>
                                    </p>
                                </div>
                                
                                <!-- Prepaid Info -->
                                <template v-if="business.billing_method === 'prepaid'">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Account Balance</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ formatCurrency(business.account_balance, business.billing_currency) }}
                                        </p>
                                    </div>
                                </template>

                                <!-- Postpaid Info -->
                                <template v-if="business.billing_method === 'postpaid'">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Credit Limit</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ formatCurrency(business.credit_limit, business.billing_currency) }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Payment Terms</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            Net {{ business.payment_terms_days || 15 }} days
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Billing Cycle</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ formatBillingCycle(business.billing_cycle) }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Account Status</label>
                                        <p class="mt-1">
                                            <span :class="business.account_suspended ? 'text-red-600' : 'text-green-600'" class="text-sm font-medium">
                                                {{ business.account_suspended ? 'Suspended' : 'Active' }}
                                            </span>
                                        </p>
                                    </div>
                                </template>

                                <!-- Pending Change Request -->
                                <div v-if="business.billing_change_request" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-sm font-medium text-yellow-800">Pending Change Request</p>
                                    <p class="text-xs text-yellow-700 mt-1">
                                        Requested: {{ formatBillingMethod(business.billing_change_request) }}
                                    </p>
                                    <p v-if="business.billing_change_reason" class="text-xs text-yellow-700 mt-1">
                                        Reason: {{ business.billing_change_reason }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Modal -->
        <div v-if="showApprovalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Approve Business</h3>
                    <form @submit.prevent="approveBusiness">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Approval Notes (Optional)</label>
                            <textarea v-model="approvalForm.notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Add any notes about this approval..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showApprovalModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                Approve
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rejection Modal -->
        <div v-if="showRejectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Business</h3>
                    <form @submit.prevent="rejectBusiness">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                            <textarea v-model="rejectionForm.reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Please provide a reason for rejection..." required></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showRejectionModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Suspension Modal -->
        <div v-if="showSuspensionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Suspend Business</h3>
                    <form @submit.prevent="suspendBusiness">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Suspension Reason *</label>
                            <textarea v-model="suspensionForm.reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500" placeholder="Please provide a reason for suspension..." required></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showSuspensionModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                                Suspend
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Billing Method Update Modal -->
        <div v-if="showBillingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Update Billing Method</h3>
                    <form @submit.prevent="updateBillingMethod">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Billing Method *</label>
                            <select
                                v-model="billingForm.billing_method"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            >
                                <option value="prepaid">Prepaid</option>
                                <option value="postpaid">Postpaid</option>
                            </select>
                        </div>

                        <!-- Postpaid Options -->
                        <div v-if="billingForm.billing_method === 'postpaid'" class="mb-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Credit Limit</label>
                                <input
                                    v-model="billingForm.credit_limit"
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
                                    v-model="billingForm.payment_terms_days"
                                    type="number"
                                    min="1"
                                    max="365"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="e.g., 15 for Net 15"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Billing Cycle</label>
                                <select
                                    v-model="billingForm.billing_cycle"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeBillingModal"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            >
                                Update
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
    business: Object,
})

const showApprovalModal = ref(false)
const showRejectionModal = ref(false)
const showSuspensionModal = ref(false)
const showBillingModal = ref(false)

const approvalForm = ref({
    notes: ''
})

const rejectionForm = ref({
    reason: ''
})

const suspensionForm = ref({
    reason: ''
})

const billingForm = ref({
    billing_method: '',
    credit_limit: '',
    payment_terms_days: '',
    billing_cycle: 'monthly',
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const formatBusinessType = (type) => {
    const types = {
        'sole_proprietorship': 'Sole Proprietorship',
        'partnership': 'Partnership',
        'limited_liability': 'Limited Liability Company'
    }
    return types[type] || type
}

const formatIdType = (type) => {
    const types = {
        'national_id': 'National ID',
        'drivers_license': 'Driver\'s License',
        'international_passport': 'International Passport'
    }
    return types[type] || type
}

const getStatusTitle = () => {
    const statuses = {
        'email_verification_pending': 'Email Verification Pending',
        'completed_unverified': 'Awaiting Review',
        'under_review': 'Under Review',
        'verified': 'Verified',
        'rejected': 'Rejected',
        'suspended': 'Suspended'
    }
    return statuses[props.business.registration_status] || 'Unknown Status'
}

const getStatusDescription = () => {
    const descriptions = {
        'email_verification_pending': 'Business registration started but email verification is pending. Admin can approve or reject.',
        'completed_unverified': 'Business registration is complete and ready for admin review',
        'under_review': 'Business is currently being reviewed by admin',
        'verified': 'Business has been approved and is operational',
        'rejected': 'Business verification was rejected',
        'suspended': 'Business has been temporarily suspended'
    }
    return descriptions[props.business.registration_status] || 'Status description not available'
}

const getStatusBannerClass = () => {
    const classes = {
        'email_verification_pending': 'bg-yellow-50 border border-yellow-200',
        'completed_unverified': 'bg-purple-50 border border-purple-200',
        'under_review': 'bg-indigo-50 border border-indigo-200',
        'verified': 'bg-green-50 border border-green-200',
        'rejected': 'bg-red-50 border border-red-200',
        'suspended': 'bg-yellow-50 border border-yellow-200'
    }
    return classes[props.business.registration_status] || 'bg-gray-50 border border-gray-200'
}

const canTakeAction = () => {
    return ['completed_unverified', 'under_review', 'email_verification_pending'].includes(props.business.registration_status)
}

const startReview = () => {
    router.post(route('admin.businesses.review', props.business.id))
}

const approveBusiness = () => {
    router.post(route('admin.businesses.approve', props.business.id), {
        approval_notes: approvalForm.value.notes
    }, {
        onSuccess: () => {
            showApprovalModal.value = false
            approvalForm.value.notes = ''
        }
    })
}

const rejectBusiness = () => {
    router.post(route('admin.businesses.reject', props.business.id), {
        rejection_reason: rejectionForm.value.reason
    }, {
        onSuccess: () => {
            showRejectionModal.value = false
            rejectionForm.value.reason = ''
        }
    })
}

const suspendBusiness = () => {
    router.post(route('admin.businesses.suspend', props.business.id), {
        suspension_reason: suspensionForm.value.reason
    }, {
        onSuccess: () => {
            showSuspensionModal.value = false
            suspensionForm.value.reason = ''
        }
    })
}

const updateBillingMethod = () => {
    const payload = {
        billing_method: billingForm.value.billing_method,
        credit_limit: billingForm.value.credit_limit !== '' && billingForm.value.credit_limit !== null
            ? Number(billingForm.value.credit_limit)
            : null,
        payment_terms_days: billingForm.value.payment_terms_days !== '' && billingForm.value.payment_terms_days !== null
            ? Number(billingForm.value.payment_terms_days)
            : null,
        billing_cycle: billingForm.value.billing_cycle || 'monthly',
    }

    router.post(route('admin.businesses.update-billing-method', props.business.id), payload, {
        onSuccess: () => {
            closeBillingModal()
        },
    })
}

const openBillingModal = () => {
    billingForm.value = {
        billing_method: props.business.billing_method || 'postpaid',
        credit_limit: props.business.credit_limit ?? '',
        payment_terms_days: props.business.payment_terms_days ?? 15,
        billing_cycle: props.business.billing_cycle || 'monthly',
    }
    showBillingModal.value = true
}

const closeBillingModal = () => {
    showBillingModal.value = false
    // Reset form back to current business values
    billingForm.value = {
        billing_method: props.business.billing_method || 'postpaid',
        credit_limit: props.business.credit_limit ?? '',
        payment_terms_days: props.business.payment_terms_days ?? 15,
        billing_cycle: props.business.billing_cycle || 'monthly',
    }
}

const formatBillingMethod = (method) => {
    if (!method) return 'Not Set'
    const methods = {
        'prepaid': 'Prepaid',
        'postpaid': 'Postpaid'
    }
    return methods[method] || method
}

const getBillingMethodBadgeClass = (method) => {
    const classes = {
        'prepaid': 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
        'postpaid': 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'
    }
    return classes[method] || 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
}

const formatCurrency = (amount, currency = 'NGN') => {
    if (amount === null || amount === undefined) return '0.00'
    const currencySymbols = {
        'NGN': '₦',
        'USD': '$',
        'GBP': '£',
        'EUR': '€'
    }
    const symbol = currencySymbols[currency] || currency
    return `${symbol}${parseFloat(amount).toFixed(2)}`
}

const formatBillingCycle = (cycle) => {
    if (!cycle) return 'Monthly'
    const cycles = {
        'daily': 'Daily',
        'weekly': 'Weekly',
        'monthly': 'Monthly'
    }
    return cycles[cycle] || cycle
}
</script>
