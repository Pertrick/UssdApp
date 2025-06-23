<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    user: Object,
    business: Object,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Welcome, {{ user?.name }}!</h3>
                        
                        <div v-if="business" class="space-y-4">
                            <div>
                                <strong>Business:</strong> {{ business.business_name }}
                            </div>
                            <div>
                                <strong>Status:</strong> {{ business.registration_status }}
                            </div>
                            <div>
                                <strong>Phone:</strong> {{ business.phone }}
                            </div>
                        </div>
                        
                        <div v-else class="text-gray-500">
                            No business information available.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
