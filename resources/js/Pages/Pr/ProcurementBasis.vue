<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Swal from 'sweetalert2';
    import { computed, onMounted } from 'vue';
    import useAuthPermission from '@/Composables/useAuthPermission';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const props = defineProps({
        ppmpList: Object
    });

    const ppmpArray = Object.values(props.ppmpList);

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
            data: 'code',
            title: 'PPMP No#',
            width: '10%'
        },
        {
            data: 'type',
            title: 'PPMP Type',
            width: '10%'
        },
        {
            data: 'ppmpYear',
            title: 'PPMP for CY',
            width: '10%'
        },
        {
            data: 'pr',
            title: 'No# of PR/s',
            width: '10%'
        },
        {
            data: 'prList',
            title: 'Purchase Request/s',
            width: '40%',
        },
        {
            data: 'createdAt',
            title: 'Created At',
            width: '10%'
        },
        {
            data: null,
            title: 'Action',
            width: '10%',
            render: '#action',
        },
    ];
</script>

<template>
    <Head title="Purchase Request" />
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
                            Purchase Request
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('pr.display.procurementBasis')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Procurement Basis List
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
                        :data="ppmpArray"
                        :options="{  paging: true,
                            searching: true,
                            ordering: false
                        }">
                            <template #action="props">
                                <ViewButton v-if="hasPermission('view-procurement-basis') ||  hasAnyRole(['Developer'])" :href="route('pr.display.availableToPurchase', { ppmpTransaction: props.cellData.id })" tooltip="View"/>
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
    .badge-pending {
    background-color: yellow;
    color: black;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    }

    .badge-approved {
    background-color: green;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    }

    .badge-rejected {
    background-color: red;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
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

    :deep(table.dataTable tbody > tr > td:nth-child(4)) {
        text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(6)) {
        text-align: left !important;
    }
</style>