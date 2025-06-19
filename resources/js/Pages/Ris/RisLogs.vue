<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { computed, onMounted, reactive, ref } from 'vue';
    import { DataTable } from 'datatables.net-vue3';
    import Swal from 'sweetalert2';
    import axios from 'axios';

    const page = usePage();

    const props = defineProps({
        transactions: Object,
    });

    const filteredLogs = ref([]);
    const filterLogs = reactive({
        startDate: '',
        endDate: '',
    });

    const submitFilter = async () => {
        if (filterLogs.startDate && filterLogs.endDate) {
            try {
                const response = await axios.get('../api/issuances-log', { 
                    params: { query: filterLogs},
                });
                
                filteredLogs.value = response.data;

                if (filteredLogs.value.data.length == 0) {
                    Swal.fire({
                        title: 'Warning',
                        text: 'No Data Found!',
                        icon: 'warning',
                        showConfirmButton: false,
                        timer: 1000
                    });
                }
            } catch (error) {
                Swal.fire({
                    title: 'Failed',
                    text: 'Error fetching product data:' + error,
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            }
        }
    };

    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

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
            width: '10%',
        },
        {
            data: 'stockNo',
            title: 'Stock No.',
            width: '10%',
        },
        {
            data: 'prodDesc',
            title: 'Description',
            width: '20%',
        },
        {
            data: 'unit',
            title: 'Unit of Measure',
            width: '10%',
        },
        {
            data: 'qty',
            title: 'Issued Quantity',
            width: '5%',
        },
        {
            data: 'officeRequestee',
            title: 'Office (Requestee)',
            width: '10%',
        },
        {
            data: 'issuedTo',
            title: 'Issued To',
            width: '15%',
        },
        {
            data: 'releasedBy',
            title: 'Issued By',
            width: '10%',
        },
        {
            data: 'dateReleased',
            title: 'Date Released',
            width: '10%',
        },
        // {
        //     data: 'id',
        //     title: 'Attachment',
        //     render: (data, type, row) => {
        //         if (row.attachment) {
        //             return `<a href="${route('ris.show.attachment', { transactionId: row.id })}" tooltip="View" target="_blank" title="View">
        //                     <svg class="w-7 h-7 text-green-700 hover:text-indigo-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        //                         <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/>
        //                     </svg>
        //                 </a>`;
        //         }
        //         return '<span class="italic text-gray-400">No Attachment</span>';
        //     },
        //     searchable: false,
        // },
    ];
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
            </nav>
        </template>
        <div class="w-full mx-auto sm:mx-4 lg:mx-0 mt-4">
            <div class="w-full bg-white h-auto mb-2 rounded-md shadow-md">
                <div class="bg-indigo-800 text-white p-4 flex justify-between rounded-t-md">
                    <div class="flex flex-wrap">
                        <p class="text-lg text-gray-100">
                            <strong class="font-semibold">Product Information and Date of Duration</strong>
                        </p>
                    </div>
                </div>
                <form @submit.prevent="submitFilter">
                    <div class="grid lg:grid-cols-3 lg:gap-6 my-5 mx-3">
                        <div class="relative z-0 w-full my-5 group">
                            <input v-model="filterLogs.startDate" type="date" name="from" id="from" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                            <label for="from" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">From (Start Date)</label>
                        </div>

                        <div class="relative z-0 w-full my-5 group">
                            <input v-model="filterLogs.endDate" type="date" name="to" id="to" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                            <label for="to" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">To (End Date)</label>
                        </div>

                        <div class="relative z-0 w-full my-5 group flex justify-center items-center">
                            <button type="submit" class="flex w-auto justify-center items-center mx-1 min-w-[100px] px-2 py-3 my-1 text-white transition-all bg-gray-600 rounded-md sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400 hover:-tranneutral-y-px">
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 16 16">
                                    <path fill="currentColor" d="M1 2h14v2L9 9v7l-2-2V9L1 4V2zm0-2h14v1H1V0z"/>
                                </svg>
                                Filter
                            </button>
                            <a 
                                :href="filteredLogs.data && filteredLogs.data.length > 0 ? route('generatePdf.ssmi', { filters: filterLogs }) : '#'" 
                                target="_blank" 
                                class="flex w-auto text-center mx-1 min-w-[100px] px-2 py-3 my-1 text-white transition-all bg-gray-600 rounded-md shadow-xl sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400"
                                :class="{ 'opacity-25 cursor-not-allowed': !(filteredLogs.data && filteredLogs.data.length > 0) }"
                                @click="!(filteredLogs.data && filteredLogs.data.length > 0) && $event.preventDefault()"
                            >
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 17 16">
                                    <g fill="currentColor" fill-rule="evenodd">
                                        <path d="M6 12v3.98h5.993V12H6zM5 1h7.948v2.96H5z"/>
                                        <path d="M1.041 5.016v9h3.91V11.01H13v3.006h4.041v-9h-16zm2.975 2.013H2.969V5.953h1.047v1.076zm2-.06H4.969v-1h1.047v1z"/>
                                    </g>
                                </svg>
                                Print
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-white shadow-md sm:rounded-lg p-4">
                <div class="relative overflow-x-auto md:overflow-hidden">
                    <DataTable 
                        v-if="filteredLogs.length === 0"
                        class="display table-hover table-striped shadow-lg rounded-lg"
                        :columns="columns"
                        :data="props.transactions"
                        :options="{  
                            paging: true,
                            searching: true,
                            ordering: true,
                        }"
                    />
                    <DataTable
                        v-if="filteredLogs.length !== 0"
                        class="display table-hover table-striped shadow-lg rounded-lg"
                        :columns="columns"
                        :data="filteredLogs.data"
                        :options="{  
                            paging: true,
                            searching: true,
                            ordering: true
                        }"
                    />
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

    :deep(table.dataTable tbody > tr > td:nth-child(3)) {
        text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(7)) {
        text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(8)) {
        text-align: left !important;
    }
</style>