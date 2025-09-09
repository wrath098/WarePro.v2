<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { ref, computed, reactive, onMounted } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import Dropdown from '@/Components/Dropdown.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    
    const props = defineProps({
        ppmpTransaction: Object,
    });

    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    onMounted(() => {
        if (message.value) {
            Swal.fire({
                title: 'Success!',
                text: message.value,
                icon: 'success',
                confirmButtonText: 'OK',
            });
        }

        if (errMessage.value) {
            Swal.fire({
                title: 'Failed!',
                text: errMessage.value,
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });

    const columns = [
        {
            data: 'ppmpCode',
            title: 'Transaction No#',
            visible: false,
        },
        {
            data: 'officeCode',
            title: 'Office Code',
            width: '8%'
        },
        {
            data: 'officeName',
            title: 'Office Name',
            width: '25%',
        },
        {
            data: 'ppmpYear',
            title: 'PPMP CY',
            width: '8%'
        },
        {
            data: 'ppmpType',
            title: 'Type',
            width: '10%',
            render: function(data) {
                return data 
                    ? data == 'individual' 
                        ?'Office'
                        : 'Emergency'
                    : '';
            }
        },
        {
            data: 'dateCreated',
            title: 'Date Created',
            width: '12%'
        },
        {
            data: 'updatedBy',
            title: 'Updated By',
            width: '15%'
        },
        {
            data: 'ppmpStatus',
            title: 'Status',
            width: '10%',
            render: (data) => {
                return `
                <span class="${data === 'Draft' 
                    ? 'bg-amber-200 text-amber-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-amber-400' 
                    : 'bg-emerald-200 text-emerald-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-emerald-400'}">
                    ${data}
                </span>
                `;
            },
        },
        {
            data: null,
            title: 'Action',
            width: '12%',
            render: '#action',
        },
    ];
</script>

<template>
    <Head title="PPMP" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center">
                        <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Project Procurement Management Plan
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('indiv.ppmp.type')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Office's PPMP List
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-screen-2xl bg-zinc-300 shadow rounded-md mb-8">
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="columns"
                        :data="ppmpTransaction"
                        :options="{  paging: true,
                            searching: true,
                            ordering: false,
                            order: [[0, 'desc']]
                        }"> 
                            <template #action="props">
                                <div v-if="hasPermission('print-office-ppmp') ||  hasAnyRole(['Developer'])" class="flex justify-center">
                                    <Dropdown v-if="props.cellData.ppmpType == 'individual'" width="48">
                                        <template #trigger>
                                            <button class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600 flex items-center gap-2">
                                                <span class="sr-only">Open options</span>
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"viewBox="0 0 16 16">
                                                    <g fill="currentColor">
                                                        <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/>
                                                        <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1a.5.5 0 0 0 0 1"/>
                                                    </g>
                                                </svg>
                                                Print
                                                <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <a 
                                                :href="route('generatePdf.DraftedOfficePpmp', { ppmp: props.cellData.id, version: 'raw' })" 
                                                target="_blank" 
                                                class="flex w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M12 2v6a2 2 0 0 0 2 2h6v10a2 2 0 0 1-2 2h-7.103a2.7 2.7 0 0 0 .092-.5H11a2 2 0 0 0 2-2V16a2.5 2.5 0 0 0-2.007-2.451A2.75 2.75 0 0 0 8.25 11h-3.5q-.392.001-.75.104V4a2 2 0 0 1 2-2zm1.5.5V8a.5.5 0 0 0 .5.5h5.5zM3 13.75c0-.966.784-1.75 1.75-1.75h3.5c.966 0 1.75.784 1.75 1.75v.75h.5A1.5 1.5 0 0 1 12 16v3.5a1 1 0 0 1-1 1h-1v.75A1.75 1.75 0 0 1 8.25 23h-3.5A1.75 1.75 0 0 1 3 21.25v-.75H2a1 1 0 0 1-1-1V16a1.5 1.5 0 0 1 1.5-1.5H3zm5.5 0a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25v.75h4zm-4 5.5v2c0 .138.112.25.25.25h3.5a.25.25 0 0 0 .25-.25v-2a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25"/>
                                                </svg>
                                                <span class="ml-2 text-sm">Raw File Format</span>   
                                            </a>
                                            <a 
                                                v-if="props.cellData.initAdjustment" 
                                                :href="route('generatePdf.DraftedOfficePpmp', { ppmp: props.cellData.id, version: 'initial' })" 
                                                target="_blank" 
                                                class="flex w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M12 2v6a2 2 0 0 0 2 2h6v10a2 2 0 0 1-2 2h-7.103a2.7 2.7 0 0 0 .092-.5H11a2 2 0 0 0 2-2V16a2.5 2.5 0 0 0-2.007-2.451A2.75 2.75 0 0 0 8.25 11h-3.5q-.392.001-.75.104V4a2 2 0 0 1 2-2zm1.5.5V8a.5.5 0 0 0 .5.5h5.5zM3 13.75c0-.966.784-1.75 1.75-1.75h3.5c.966 0 1.75.784 1.75 1.75v.75h.5A1.5 1.5 0 0 1 12 16v3.5a1 1 0 0 1-1 1h-1v.75A1.75 1.75 0 0 1 8.25 23h-3.5A1.75 1.75 0 0 1 3 21.25v-.75H2a1 1 0 0 1-1-1V16a1.5 1.5 0 0 1 1.5-1.5H3zm5.5 0a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25v.75h4zm-4 5.5v2c0 .138.112.25.25.25h3.5a.25.25 0 0 0 .25-.25v-2a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25"/>
                                                </svg>
                                                <span class="ml-2 text-sm">Initial Adjustment</span>   
                                            </a>
                                            <a 
                                                v-if="props.cellData.finalAdjustment" 
                                                :href="route('generatePdf.DraftedOfficePpmp', { ppmp: props.cellData.id, version: 'final' })" 
                                                target="_blank" 
                                                class="flex w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M12 2v6a2 2 0 0 0 2 2h6v10a2 2 0 0 1-2 2h-7.103a2.7 2.7 0 0 0 .092-.5H11a2 2 0 0 0 2-2V16a2.5 2.5 0 0 0-2.007-2.451A2.75 2.75 0 0 0 8.25 11h-3.5q-.392.001-.75.104V4a2 2 0 0 1 2-2zm1.5.5V8a.5.5 0 0 0 .5.5h5.5zM3 13.75c0-.966.784-1.75 1.75-1.75h3.5c.966 0 1.75.784 1.75 1.75v.75h.5A1.5 1.5 0 0 1 12 16v3.5a1 1 0 0 1-1 1h-1v.75A1.75 1.75 0 0 1 8.25 23h-3.5A1.75 1.75 0 0 1 3 21.25v-.75H2a1 1 0 0 1-1-1V16a1.5 1.5 0 0 1 1.5-1.5H3zm5.5 0a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25v.75h4zm-4 5.5v2c0 .138.112.25.25.25h3.5a.25.25 0 0 0 .25-.25v-2a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25"/>
                                                </svg>
                                                <span class="ml-2 text-sm">Final Adjustment</span>   
                                            </a>
                                        </template>
                                    </Dropdown>
                                    <div v-else>
                                        <a 
                                            :href="route('generatePdf.emergencyPpmp', { ppmp: props.cellData.id})" 
                                            target="_blank" 
                                            class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600 flex items-center gap-2">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"viewBox="0 0 16 16">
                                                <g fill="currentColor">
                                                    <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/>
                                                    <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1a.5.5 0 0 0 0 1"/>
                                                </g>
                                            </svg>
                                            <span class="ml-2 text-sm">Print</span>   
                                        </a>
                                    </div>
                                </div>
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
 
<style scoped>
    .upload-area {
        border: 2px dashed #007BFF;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.3s ease;
    }
    .upload-area:hover {
        border-color: #08396d;
    }

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
        text-align: center;
    }

    :deep(div.dt-container select.dt-input) {
        background-color: #fafafa;
        border: 1px solid #03244d;
        margin-left: 1px;
        width: 75px;
    }

    :deep(div.dt-container .dt-search input) {
        background-color: #fafafa;
        border: 1px solid #03244d;
        margin-right: 1px;
        width: 250px;
    }

    :deep(div.dt-length > label) {
        display: none;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: left !important;
    }
</style>