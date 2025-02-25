<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Layouts/Sidebar.vue';
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
                return filteredLogs;
            } catch (error) {
                console.error('Error fetching product data:', error);
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
            className: 'text-center',
        },
        {
            data: 'stockNo',
            title: 'Stock No.',
        },
        {
            data: 'prodDesc',
            title: 'Description',
            width: '20%',
        },
        {
            data: 'unit',
            title: 'Unit of Measure',
        },
        {
            data: 'qty',
            title: 'Requested (Qty)',
        },
        {
            data: 'officeRequestee',
            title: 'Office (Requestee)',
        },
        {
            data: 'issuedTo',
            title: 'Issued To',
        },
        {
            data: 'releasedBy',
            title: 'Issued By',
        },
        {
            data: 'dateReleased',
            title: 'Date Released',
        },
        {
            data: 'id',
            title: 'Attachment',
            render: (data, type, row) => {
                if (row.attachment) {
                    return `<a href="${route('ris.show.attachment', { transactionId: row.id })}" tooltip="View" target="_blank" title="View">
                            <svg class="w-7 h-7 text-green-700 hover:text-indigo-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/>
                            </svg>
                        </a>`;
                }
                return '<span class="italic text-gray-400">No Attachment</span>';
            },
            searchable: false,
        },
    ];
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Purchase Request</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Procurement Basis</a></li>
                </ol>
            </nav>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="w-full bg-white h-auto mb-2 rounded-md shadow-md">
                    <div class="relative isolate flex overflow-hidden bg-indigo-500 px-6 py-2.5 rounded-md">
                        <div class="flex flex-wrap">
                            <p class="text-base text-gray-100">
                                <strong class="font-semibold">Product Information and Date of Duration</strong>
                                <svg viewBox="0 0 2 2" class="mx-2 inline size-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                                <span class="text-sm">Please enter the required data in the input field.</span>
                            </p>
                        </div>
                    </div>
                    <form @submit.prevent="submitFilter">
                        <div class="grid lg:grid-cols-4 lg:gap-6 my-5 mx-3">
                            <div class="relative z-0 w-full my-5 group">
                                <input v-model="filterLogs.startDate" type="date" name="from" id="from" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="from" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">From (Start Date)</label>
                            </div>

                            <div class="relative z-0 w-full my-5 group">
                                <input v-model="filterLogs.endDate" type="date" name="to" id="to" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="to" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">To (End Date)</label>
                            </div>

                            <div class="relative z-0 w-full my-5 group text-center">
                                <button type="submit" class="inline-block w-auto text-center mx-1 min-w-[125px] px-6 py-3 text-white transition-all bg-gray-600 rounded-md sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400 hover:-tranneutral-y-px">
                                    Filter
                                </button>
                                <a 
                                    :href="filteredLogs.data && filteredLogs.data.length > 0 ? route('generatePdf.ssmi', { filters: filterLogs }) : '#'" 
                                    target="_blank" 
                                    class="inline-block w-auto text-center mx-1 min-w-[125px] px-6 py-3 text-white transition-all bg-gray-600 rounded-md shadow-xl sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400"
                                    :class="{ 'opacity-25 cursor-not-allowed': !(filteredLogs.data && filteredLogs.data.length > 0) }"
                                    @click="!(filteredLogs.data && filteredLogs.data.length > 0) && $event.preventDefault()"
                                >
                                    Print
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="overflow-hidden shadow-md sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="w-full bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white overflow-hidden sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable 
                                        v-if="filteredLogs.length === 0"
                                        class="display table-hover table-striped shadow-lg rounded-lg"
                                        :columns="columns"
                                        :data="transactions"
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
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
<style scoped>
:deep(table.dataTable) {
    border: 2px solid #555555;
}

:deep(table.dataTable thead > tr > th) {
    background-color: #555555;
    text-align: center;
    color: aliceblue;
}
</style>