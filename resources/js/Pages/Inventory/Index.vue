<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
import { DataTable } from 'datatables.net-vue3';

    const props = defineProps({
        inventory: Object,
    });
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2 leading-tight">
                    <li><a class="after:content-[''] after:ml-2 text-green-700">Product Inventory</a></li>
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>
        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white p-2 lg:overflow-hidden shadow-sm sm:rounded-lg">
                    <DataTable class="w-full text-gray-900 display">
                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                            <tr>
                                <th class="w-1/12">No.</th>
                                <th class="w-1/12">Stock No.</th>
                                <th class="w-7/12">Description</th>
                                <th class="w-1/12">Unit of Measure</th>
                                <th class="w-1/12">Stock Available</th>
                                <th class="w-1/12">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in inventory" :key="item.id">
                                <td>{{ ++index }}</td>
                                <td>{{ item.stockNo }}</td>
                                <td>{{ item.prodDesc }}</td>
                                <td>{{ item.prodUnit }}</td>
                                <td>{{ item.stockAvailable }}</td>
                                <td>
                                    <span :class="{
                                        'bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300': item.status === 'Available',
                                        'bg-yellow-100 text-yellow-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-yellow-300': item.status == 'Reorder'
                                        }">
                                        {{ item.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>