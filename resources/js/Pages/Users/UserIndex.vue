<script setup>
import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { render } from 'nprogress';

const props = defineProps({
    users: {
        type: Object,
        required: true,
        default: () => ({})
    }
});

const columns = [
    {
        data: 'name',
        title: 'Name',
        width: '20%'
    },
    {
        data: 'email',
        title: 'Email Address',
        width: '30%'
    },
    {
        data: 'roles',
        title: 'User Role/s',
        width: '30%'
    },
    {
        data: null,
        title: 'Action',
        render: '#action',
        width: '20%'
    }
];

</script>

<template>
    <Head title="Users" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center" aria-current="page">
                        <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Account Setting
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('user')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Users
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl bg-slate-50 shadow rounded-md">
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <DataTable
                            class="display table-hover table-striped shadow-lg rounded-lg"
                            :columns="columns"
                            :data="props.users"
                            :options="{  
                                paging: true,
                                searching: true,
                                ordering: false,
                                info: false
                            }">
                            <template #action="props">
                                <RemoveButton v-if="!props.cellData?.roles?.some(role => ['Developer'].includes(role))" @click="openDropModal(props.cellData.id)" tooltip="Remove"/>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
<style scoped>
     :deep(table.dataTable) {
        border: 2px solid #7393dc;
    }

    :deep(table.dataTable thead > tr > th) {
        background-color: #d8d8f6;
        border: 2px solid #7393dc;
        text-align: center;
        color: #03244d;
    }

    :deep(table.dataTable tbody > tr > td) {
        border-right: 2px solid #7393dc;
        text-align: left;
    }

    :deep(div.dt-container select.dt-input) {
        border: 1px solid #03244d;
        margin-left: 1px;
        width: 75px;
    }

    :deep(div.dt-container .dt-search input) {
        border: 1px solid #03244d;
        margin-right: 1px;
        width: 250px;
    }

    :deep(div.dt-length > label) {
        display: none;
    }

    :deep([data-v-554ff909] table.dataTable tbody > tr > td:nth-child(4)) {
        text-align: center !important;
    }
</style>
