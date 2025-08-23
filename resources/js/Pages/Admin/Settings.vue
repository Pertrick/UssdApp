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

                <!-- System Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">System Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Application Name</label>
                                <p class="mt-1 text-sm text-gray-900">USSD Application</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Environment</label>
                                <p class="mt-1 text-sm text-gray-900">{{ environment }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Laravel Version</label>
                                <p class="mt-1 text-sm text-gray-900">{{ laravelVersion }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">PHP Version</label>
                                <p class="mt-1 text-sm text-gray-900">{{ phpVersion }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Database</h4>
                                <p class="text-sm text-gray-500 mb-3">Manage database operations</p>
                                <button class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                                    View Database Status
                                </button>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Cache</h4>
                                <p class="text-sm text-gray-500 mb-3">Clear application cache</p>
                                <button class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700">
                                    Clear Cache
                                </button>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Logs</h4>
                                <p class="text-sm text-gray-500 mb-3">View system logs</p>
                                <button class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">
                                    View Logs
                                </button>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Backup</h4>
                                <p class="text-sm text-gray-500 mb-3">Create system backup</p>
                                <button class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                                    Create Backup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    roles: Array,
})

// These would typically come from the backend
const environment = 'Production'
const laravelVersion = '10.x'
const phpVersion = '8.1+'
</script>
