<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';

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
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-green-700">Inspection and Acceptance</a></li>
                    <li class="after:content-[''] after:ml-2 text-green-700" aria-current="page">Transactions</li> 
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
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <DataTable class="display">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>IAR No.</th>
                                <th>PO No.</th>
                                <th>Supplier</th>
                                <th>IAR Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(transaction, index) in props.iar" :key="transaction.air_id">
                                <td>{{ ++index }}</td>
                                <td>{{ transaction.air_id }}</td>
                                <td>{{ transaction.po_no }}</td>
                                <td>{{ transaction.name }}</td>
                                <td>{{ transaction.air_date }}</td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>