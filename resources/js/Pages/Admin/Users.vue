<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                User Management
            </h2>
        </template>

        <div class="py-12">
            <!-- Search -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search users..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                @input="debounceSearch"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Businesses
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Roles
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Joined
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ user.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ user.email }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ user.businesses_count || 0 }} businesses
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="role in user.roles" :key="role.id" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                                {{ role.display_name }}
                                            </span>
                                            <span v-if="user.roles.length === 0" class="text-sm text-gray-500">
                                                No roles
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusBadgeClass(user.is_active)">
                                            {{ user.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button @click="editUserRoles(user)" class="text-blue-600 hover:text-blue-900">
                                                Edit Roles
                                            </button>
                                            <button @click="toggleUserStatus(user)" :class="user.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'">
                                                {{ user.is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.links && users.links.length > 3" class="mt-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in users.links"
                                    :key="link.label"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 text-sm font-medium rounded-md',
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Roles Modal -->
        <Modal :show="showRolesModal" @close="showRolesModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit User Roles</h3>
                <form @submit.prevent="updateUserRoles">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Roles for {{ selectedUser?.name }}
                        </label>
                        <div class="space-y-2">
                            <label v-for="role in roles" :key="role.id" class="flex items-center">
                                <input
                                    type="checkbox"
                                    :value="role.id"
                                    v-model="selectedRoles"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                >
                                <span class="ml-2 text-sm text-gray-700">{{ role.display_name }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showRolesModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                        >
                            Update Roles
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
})

const search = ref(props.filters.search || '')
const showRolesModal = ref(false)
const selectedUser = ref(null)
const selectedRoles = ref([])

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const getStatusBadgeClass = (isActive) => {
    return isActive 
        ? 'bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full'
        : 'bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full'
}

const debounceSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        filterUsers()
    }, 300)
}

let searchTimeout = null

const filterUsers = () => {
    router.get(route('admin.users'), {
        search: search.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const editUserRoles = (user) => {
    selectedUser.value = user
    selectedRoles.value = user.roles.map(role => role.id)
    showRolesModal.value = true
}

const updateUserRoles = () => {
    router.patch(route('admin.users.roles', selectedUser.value.id), {
        roles: selectedRoles.value
    }, {
        onSuccess: () => {
            showRolesModal.value = false
            selectedUser.value = null
            selectedRoles.value = []
        }
    })
}

const toggleUserStatus = (user) => {
    router.patch(route('admin.users.toggle-status', user.id), {}, {
        onSuccess: () => {
            // Page will be refreshed
        }
    })
}
</script>
