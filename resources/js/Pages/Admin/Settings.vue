<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Admin Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto">
                <!-- Roles Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">System Roles</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Role Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Display Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Description
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Users
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ role.name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ role.display_name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-500">
                                                {{ role.description }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ role.users_count || 0 }} users
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- USSD Cost Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">USSD Gateway Costs</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Manage AfricasTalking costs per network. Update these with actual costs from your AfricasTalking dashboard.
                                </p>
                            </div>
                            <button
                                @click="showAddCostModal = true"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Add Network Cost
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Network
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cost per Session
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Currency
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Effective From
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="cost in ussdCosts" :key="cost.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ cost.network }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ formatCurrency(cost.cost_per_session) }} {{ cost.currency }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                {{ cost.currency }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                {{ formatDate(cost.effective_from) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="cost.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 text-xs font-medium rounded-full">
                                                {{ cost.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button
                                                @click="editCost(cost)"
                                                class="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="ussdCosts.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No USSD costs configured. Click "Add Network Cost" to get started.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <h4 class="text-sm font-medium text-blue-900 mb-2">How to get real costs:</h4>
                            <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                                <li>Log into your <a href="https://account.africastalking.com" target="_blank" class="underline">AfricasTalking Dashboard</a></li>
                                <li>Navigate to Billing or Pricing section</li>
                                <li>Check your actual per-session costs by network</li>
                                <li>Update the costs above with the real values</li>
                                <li>Contact AfricasTalking support if pricing is not visible in dashboard</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Cost Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Edit USSD Cost</h2>
                <form @submit.prevent="updateCost">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Network</label>
                            <input
                                v-model="editForm.network"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                readonly
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cost per Session ({{ editForm.currency }})</label>
                            <input
                                v-model.number="editForm.cost_per_session"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">Enter cost in main currency (e.g., 3.00 for ₦3.00)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Effective From</label>
                            <input
                                v-model="editForm.effective_from"
                                type="date"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="editForm.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                />
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700"
                        >
                            Update Cost
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Add Cost Modal -->
        <Modal :show="showAddCostModal" @close="showAddCostModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Add Network Cost</h2>
                <form @submit.prevent="createCost">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Network</label>
                            <input
                                v-model="newCostForm.network"
                                type="text"
                                required
                                placeholder="e.g., MTN, Airtel, Glo, 9mobile"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cost per Session ({{ newCostForm.currency }})</label>
                            <input
                                v-model.number="newCostForm.cost_per_session"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Country</label>
                                <input
                                    v-model="newCostForm.country"
                                    type="text"
                                    maxlength="2"
                                    required
                                    placeholder="NG"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Currency</label>
                                <input
                                    v-model="newCostForm.currency"
                                    type="text"
                                    maxlength="3"
                                    required
                                    placeholder="NGN"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Effective From</label>
                            <input
                                v-model="newCostForm.effective_from"
                                type="date"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showAddCostModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700"
                        >
                            Create Cost
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    roles: Array,
    ussdCosts: Array,
})

const showEditModal = ref(false)
const showAddCostModal = ref(false)
const editForm = ref({
    id: null,
    network: '',
    cost_per_session: 0,
    currency: 'NGN',
    effective_from: '',
    is_active: true,
})

const newCostForm = ref({
    network: '',
    cost_per_session: 0,
    country: 'NG',
    currency: 'NGN',
    effective_from: new Date().toISOString().split('T')[0],
})

const formatCurrency = (costInSmallestUnit) => {
    return (costInSmallestUnit / 100).toFixed(2)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString()
}

const editCost = (cost) => {
    editForm.value = {
        id: cost.id,
        network: cost.network,
        cost_per_session: cost.cost_per_session / 100, // Convert from smallest unit
        currency: cost.currency,
        effective_from: cost.effective_from,
        is_active: cost.is_active,
    }
    showEditModal.value = true
}

const updateCost = () => {
    router.put(route('admin.ussd-costs.update', editForm.value.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
        },
    })
}

const createCost = () => {
    router.post(route('admin.ussd-costs.create'), newCostForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showAddCostModal.value = false
            newCostForm.value = {
                network: '',
                cost_per_session: 0,
                country: 'NG',
                currency: 'NGN',
                effective_from: new Date().toISOString().split('T')[0],
            }
        },
    })
}
</script>
