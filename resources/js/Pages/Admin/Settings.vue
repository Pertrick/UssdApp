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

                <!-- Network Pricing (Dynamic: AT Cost + Markup) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Network Pricing</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Prices are calculated automatically: <strong>Final Price = AT Cost × (1 + Markup%)</strong><br>
                                    AT costs are updated automatically from AfricasTalking webhook events.
                                </p>
                            </div>
                            <button
                                @click="addNewMarkup"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Add Network Pricing
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
                                            AT Cost (Auto)
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Markup %
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Final Price (Calculated)
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Min Price
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="pricing in networkPricing" :key="pricing.id || pricing.network" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ pricing.network }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ pricing.currency }} {{ formatPrice(pricing.at_cost || 0) }}
                                            </div>
                                            <div class="text-xs text-gray-500">Updated from events</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ pricing.markup_percentage || 50 }}%
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-green-600">
                                                {{ pricing.currency }} {{ formatPrice(calculateFinalPrice(pricing)) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                {{ pricing.minimum_price ? formatPrice(pricing.minimum_price) : '-' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button
                                                @click="editMarkup(pricing)"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                Edit Markup
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="networkPricing.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No network pricing configured. Prices will use default 50% markup.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <h4 class="text-sm font-medium text-blue-900 mb-2">How dynamic pricing works:</h4>
                            <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                                <li><strong>AT Cost:</strong> Automatically updated from AfricasTalking webhook events (read-only)</li>
                                <li><strong>Markup %:</strong> Admin-set profit margin (e.g., 50% = 50% markup on cost)</li>
                                <li><strong>Final Price:</strong> AT Cost × (1 + Markup%) = Customer Price</li>
                                <li><strong>Minimum Price:</strong> Optional floor price to ensure profitability</li>
                                <li>Business discounts are applied after calculating the final price</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Markup Modal -->
        <Modal :show="showEditMarkupModal" @close="showEditMarkupModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editMarkupForm.id ? 'Edit' : 'Add' }} Network Markup
                </h2>
                <form @submit.prevent="updateMarkup">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Network</label>
                            <select
                                v-if="!editMarkupForm.id"
                                v-model="editMarkupForm.network"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Select Network</option>
                                <option value="MTN">MTN</option>
                                <option value="Airtel">Airtel</option>
                                <option value="Glo">Glo</option>
                                <option value="9mobile">9mobile</option>
                            </select>
                            <input
                                v-else
                                v-model="editMarkupForm.network"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                readonly
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Markup Percentage</label>
                            <div class="flex items-center mt-1">
                                <input
                                    v-model.number="editMarkupForm.markup_percentage"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="1000"
                                    required
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />
                                <span class="ml-2 text-sm text-gray-500">%</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Profit margin: {{ editMarkupForm.markup_percentage }}% markup on AT cost
                            </p>
                            <p class="mt-1 text-xs text-blue-600">
                                Example: If AT cost is ₦0.05 and markup is 50%, final price = ₦0.075
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Minimum Price (Optional)</label>
                            <div class="flex items-center mt-1">
                                <input
                                    v-model.number="editMarkupForm.minimum_price"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Leave empty for no minimum"
                                />
                                <span class="ml-2 text-sm text-gray-500">{{ editMarkupForm.currency }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Ensures price never goes below this amount (useful for low-cost networks)
                            </p>
                        </div>
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-xs text-blue-800">
                                <strong>Note:</strong> AT costs are automatically updated from webhook events. 
                                You only need to set the markup percentage to control profit margins.
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showEditMarkupModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700"
                        >
                            {{ editMarkupForm.id ? 'Update' : 'Create' }} Markup
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
    networkPricing: Array,
})

const showEditMarkupModal = ref(false)
const editMarkupForm = ref({
    id: null,
    network: '',
    markup_percentage: 50,
    minimum_price: null,
    currency: 'NGN',
})

const formatPrice = (price) => {
    return Number(price).toFixed(4)
}

const calculateFinalPrice = (pricing) => {
    const atCost = pricing.at_cost || 0
    const markup = pricing.markup_percentage || 50
    let price = atCost * (1 + (markup / 100))
    
    // Apply minimum price if set
    if (pricing.minimum_price && price < pricing.minimum_price) {
        price = pricing.minimum_price
    }
    
    return price
}

const addNewMarkup = () => {
    editMarkupForm.value = {
        id: null,
        network: '',
        markup_percentage: 50,
        minimum_price: null,
        currency: 'NGN',
    }
    showEditMarkupModal.value = true
}

const editMarkup = (pricing) => {
    editMarkupForm.value = {
        id: pricing.id || null,
        network: pricing.network,
        markup_percentage: pricing.markup_percentage || 50,
        minimum_price: pricing.minimum_price || null,
        currency: pricing.currency || 'NGN',
    }
    showEditMarkupModal.value = true
}

const updateMarkup = () => {
    const payload = {
        ...editMarkupForm.value,
        country: 'NG',
    }
    
    if (editMarkupForm.value.id) {
        router.put(route('admin.network-pricing.update', editMarkupForm.value.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                showEditMarkupModal.value = false
            },
        })
    } else {
        router.post(route('admin.network-pricing.create'), payload, {
            preserveScroll: true,
            onSuccess: () => {
                showEditMarkupModal.value = false
            },
        })
    }
}
</script>
