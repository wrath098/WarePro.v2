<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Refresh from '@/Components/Buttons/Refresh.vue';

    const props = defineProps({
        iar: Object,
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
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Inspection and Acceptance</a></li>
                    <li class="after:content-['/'] after:ml-2 text-[#86591e]" aria-current="page">Pending Transactions</li> 
                    <li><Refresh :href="route('iar.collect.transactions')" class="" tooltip="Fetch IAR"/></li>
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-indigo-400 my-2 italic">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-gray-400 my-2 italic">
                {{ $page.props.flash.error }}
            </div>
        </template>
        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white p-2 lg:overflow-hidden shadow-sm sm:rounded-lg">
                    <DataTable class="w-full text-gray-900 display">
                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                            <tr>
                                <th>No.</th>
                                <th>IAR No.</th>
                                <th>PO No.</th>
                                <th>Supplier</th>
                                <th>IAR Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(transaction, index) in props.iar" :key="transaction.air_id">
                                <td>{{ ++index }}</td>
                                <td>{{ transaction.airId }}</td>
                                <td>{{ transaction.poId }}</td>
                                <td>{{ transaction.supplier }}</td>
                                <td>{{ transaction.date }}</td>
                                <td>
                                    <span :class="{
                                        'bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-gray-300': transaction.status === 'Pending',
                                        }">
                                        {{ transaction.status }}
                                    </span>
                                </td>
                                <td>
                                    <ViewButton :href="route('iar.particular', { iar: transaction.id})" tooltip="View"/>
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