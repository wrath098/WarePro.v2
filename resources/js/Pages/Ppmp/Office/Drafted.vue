<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';

    const props = defineProps({
        user: Object,
        lists: Object
    });

    [ { "id": 69, "ppmp_code": "20251202020954", "ppmp_type": "individual", "description": "Raw File", "account_class_ids": null, "office_ppmp_ids": null, "init_qty_adjustment": null, "final_qty_adjustment": null, "price_adjustment": "1.00", "ppmp_status": "draft", "ppmp_year": "2027", "office_id": 9, "created_by": 1, "updated_by": 1, "remarks": null, "deleted_at": null, "created_at": "2025-12-02T02:09:54.000000Z", "updated_at": "2025-12-02T02:09:54.000000Z" } ]

    const columns = [
        {
            data: 'ppmp_year',
            title: 'PPMP CY',
            width: '10%'
        },
        {
            data: 'office_id',
            title: 'TYPE',
            width: '10%',
            render: function(data) {
                return 'Office';
            }
        },
        {
            data: 'createdAt',
            title: 'DATE',
            width: '10%'
        },
        {
            data: 'ppmp_code',
            title: 'TRANSACTION CODE',
            width: '10%'
        },
        {
            data: 'updatedBy',
            title: 'Updated By',
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
    <Head title="PPMP - Drafts" />
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
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('import.ppmp.index')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Create
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="my-4 w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="columns"
                        :data="props.lists"
                        :options="{  paging: true,
                            searching: true,
                            ordering: false
                        }">
                            <template #action="props">
                                <ViewButton :href="route('indiv.ppmp.show', { ppmpTransaction: props.cellData.id })" tooltip="View"/>
                                <RemoveButton @click="openDropPpmpModal(props.cellData)" tooltip="Remove"/>
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

    .list-enter-active,
    .list-leave-active {
        transition: all 0.5s ease;
    }
    .list-enter-from,
    .list-leave-to {
        opacity: 0;
        transform: translateX(30px);
    }
</style>