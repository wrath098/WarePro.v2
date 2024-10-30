<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    
    const props = defineProps({
        ppmpTransaction: Object,
        ppmp: Object,
    });
</script>

<template>
    <Head title="Office" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-gray-600 hover:text-green-700">Project Procurement and Manangement Plan</a></li>
                    <li class="after:content-['/'] after:ml-2 text-green-700" aria-current="page">{{ props.ppmp.type }}</li> 
                    <li class="text-green-700" aria-current="page">{{ props.ppmp.status }}</li> 
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
                    <div class="relative overflow-x-auto text-center">
                        <DataTable class="w-full text-gray-900 display">
                            <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                <tr>
                                    <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                    <th scope="col" class="px-6 py-3 w-1/12">Transaction No.</th>
                                    <th scope="col" class="px-6 py-3 w-1/12">Office Code</th>
                                    <th scope="col" class="px-6 py-3 w-3/12 text-center">Office Name</th>
                                    <th scope="col" class="px-6 py-3 w-1/12">Calendar Year</th>
                                    <th scope="col" class="px-6 py-3 w-1/12">Version</th>
                                    <th scope="col" class="px-6 py-3 w-2/12">Created/Updated By</th>
                                    <th scope="col" class="px-6 py-3 w-2/12">Action/s</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(transaction, index) in ppmpTransaction" :key="transaction.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                    <td class="px-6 py-3">{{ index + 1 }}</td>
                                    <td class="px-6 py-3">{{ transaction.ppmp_code }}</td>
                                    <td class="px-6 py-3 text-left">{{ transaction.requestee.office_code }}</td>
                                    <td class="px-6 py-3 text-left">{{ transaction.requestee.office_name }}</td>
                                    <td class="px-6 py-3">{{ transaction.ppmp_year }}</td>
                                    <td class="px-6 py-3">v.{{ transaction.ppmp_version }}</td>
                                    <td class="px-6 py-3">{{ transaction.updater.name }}</td>
                                    <td class="px-6 py-3">
                                        <EditButton @click="openEditModal(office)" tooltip="Edit"/>
                                        <RemoveButton @click="openDeactivateModal(office)" tooltip="Remove"/>
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 