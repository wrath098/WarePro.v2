<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { computed, onMounted, ref } from 'vue';
    import { DataTable } from 'datatables.net-vue3';
    import Swal from 'sweetalert2';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Print from '@/Components/Buttons/Print.vue';
    import { debounce } from 'lodash';
    import axios from 'axios';
    import LoadingOverlay from '@/Components/LoadingOverlay.vue';

    const page = usePage();
    const isLoading = ref(false);
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    const props = defineProps({
        transactions: Object,
    });

    onMounted(() => {
        if (message.value) {
            Swal.fire({
                title: 'Success',
                text: message.value,
                icon: 'success',
                confirmButtonText: 'OK',
            });
        }

        if (errMessage.value) {
            Swal.fire({
                title: 'Failed',
                text: errMessage.value,
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });

    const columns = [
        {
            data: 'risNo',
            title: 'RIS No.',
            width: '15%',
        },
        {
            data: 'officeRequestee',
            title: 'Office <br> (Requestee)',
            width: '12%',
        },
        {
            data: 'requesteeRemarks',
            title: 'Office <br> (Remarks)',
            width: '12%',
        },
        {
            data: 'noOfItems',
            title: 'No Of Items',
            width: '10%',
        },
        {
            data: 'issuedTo',
            title: 'Issued To',
            width: '20%',
        },
        {
            data: 'releasedBy',
            title: 'Issued By',
            width: '20%',
        },
        {
            data: null,
            title: 'Action',
            width: '11%',
            render: '#action',
        },
    ];

    const months = [
        'January', 'February', 'March', 'April', 
        'May', 'June', 'July', 'August',
        'September', 'October', 'November', 'December'
    ];
    
    const risTransactions = ref([ ...props.transactions ]);
    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 4 }, (_, i) => currentYear - 2 + i);

    const selectedDay = ref(null);
    const selectedMonth = ref(null);
    const selectedYear = ref(null);

    const daysInMonth = computed(() => {
        if (!selectedMonth.value || !selectedYear.value) return 31;
        
        return new Date(selectedYear.value, selectedMonth.value, 0).getDate();
    });

    const updateDaysInMonth = () => {
        if (selectedDay.value && daysInMonth.value < selectedDay.value) {
            selectedDay.value = null;
        }
        handleDateFilter();
    };

    const handleDateFilter = debounce(async () => {   
        isLoading.value = true;
        try {
            const filtered = {
                day: selectedDay.value,
                month: selectedMonth.value,
                year: selectedYear.value
            };

            const response = await axios.get(route('filter.ris.transactions', { period: filtered }));
            risTransactions.value = response.data?.data;
            isLoading.value = false;
        } catch (error) {
            isLoading.value = false;
            console.error('Error fetching filtered data:', error);
        }
    }, 1500);
</script>

<template>
    <Head title="Requisition and Issuances" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center" aria-current="page">
                        <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Request and Issuances
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('ris.display.logs')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Issuances
                            </a>
                        </div>
                    </li>
                </ol>
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="flex flex-col lg:flex-row w-20">
                        <select v-model="selectedYear" id="filterYear" name="filterYear" @change="updateDaysInMonth" class="w-full h-10 border-0 focus:border-0 text-gray-500 rounded px-2 md:px-3 py-0 md:py-1 bg-zinc-100">
                            <option disabled :value="null">Year</option>
                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </li>
                    <li class="flex flex-col lg:flex-row w-32">
                        <select v-model="selectedMonth" id="filterMonth" name="filterMonth" @change="updateDaysInMonth" class="w-full h-10 border-0 focus:border-0 text-gray-500 rounded px-2 md:px-3 py-0 md:py-1 bg-zinc-100">
                            <option :value="null">Month</option>
                            <option v-for="(month, index) in months" :key="index" :value="index + 1">{{ month }}</option>
                        </select>
                    </li>
                    <li class="flex flex-col lg:flex-row w-20">
                        <select v-model="selectedDay" id="filterDat" name="filterDay" @change="handleDateFilter" class="w-full h-10 border-0 focus:border-0 text-gray-500 rounded px-2 md:px-3 py-0 md:py-1 bg-zinc-100">
                            <option :value="null">Day</option>
                            <option v-for="day in daysInMonth" :value="day" :key="day">{{ day }}</option>
                        </select>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="w-full mx-auto sm:mx-4 lg:mx-0 mt-4">
            <div class="bg-zinc-300 shadow-md sm:rounded-lg p-4">
                <div class="relative overflow-x-auto md:overflow-hidden">
                    <LoadingOverlay v-if="isLoading"/>
                    <DataTable 
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="columns"
                        :data="risTransactions"
                        :options="{  
                            paging: true,
                            searching: true,
                            ordering: false,
                            order: [[0, 'desc']]
                        }"
                    >
                        <template #action="props">
                            <ViewButton :href="route('ris.show.items', { transactionId: props.cellData.risNo, issuedTo: props.cellData.issuedTo })" tooltip="View"/>
                            <Print :href="route('generatePdf.issuance', { transactionId: props.cellData.risNo, issuedTo: props.cellData.issuedTo })" tooltip="Print"/>
                        </template>
                    </DataTable>
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

    :deep(table.dataTable tbody > tr > td:nth-child(5)) {
        text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(6)) {
        text-align: left !important;
    }
</style>