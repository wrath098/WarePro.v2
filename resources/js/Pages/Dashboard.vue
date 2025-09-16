<script setup>
import LineChart from '@/Components/LineChart.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, reactive, ref, watchEffect } from 'vue';
import useAuthPermission from '@/Composables/useAuthPermission';
import Swal from 'sweetalert2';
import { debounce } from 'lodash';
import {
    Chart as ChartJS,
    Title,
    SubTitle,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineController,
    Filler
} from 'chart.js';

ChartJS.register(
    Title,
    SubTitle,
    Tooltip,
    Legend,
    Filler,
    LineElement,
    PointElement,
    LineController,
    CategoryScale,
    LinearScale,
);

const page = usePage();
const message = computed(() => page.props.flash.message);
const errMessage = computed(() => page.props.flash.error);

const {hasAnyRole, hasPermission} = useAuthPermission();
const props = defineProps({
    core: {
        type: Object,
    },
    monthlyPriceEvaluation: {
        type: Object,
        default: () => ({
            labels: [],
            datasets: {
                hikes: [],
                stable: [],
                drops: []
            }
        })
    }
});

const months = [
    'January', 'February', 'March', 'April', 
    'May', 'June', 'July', 'August',
    'September', 'October', 'November', 'December'
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 4 }, (_, i) => currentYear - 2 + i);

const selectedDay = ref(null);
const selectedMonth = ref(null);
const selectedYear = ref(null);

const daysInMonth = computed(() => {
    if (!selectedMonth.value || !selectedYear.value) return 31;
    
    return new Date(selectedYear.value, selectedMonth.value, 0).getDate();
});

const updatedCore = ref({ ...props.core });

const updateDaysInMonth = () => {
    if (selectedDay.value && daysInMonth.value < selectedDay.value) {
        selectedDay.value = null;
    }
    handleDateFilter();
};

const handleDateFilter = debounce(async () => {   
    try {

        const filtered = {
            day: selectedDay.value,
            month: selectedMonth.value,
            year: selectedYear.value
        };

        const response = await axios.get(route('filter.dashboard', { period: filtered }));
        updatedCore.value = response.data;
    } catch (error) {
        console.error('Error fetching filtered data:', error);
    }
}, 1500);

const chartData = ref({
    labels: [],
    datasets: []
});

let delayed = false;
const chartOptions = ref({
    responsive: true,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    scales: {
        x: {
            display: true,
            title: {
                display: true,
                text: 'Months',
                font: {
                    family: 'Saira',
                    size: 20,
                    lineHeight: 1.2,
                },
                padding: {top: 5, left: 0, right: 0, bottom: 0}
            }
        },
        y: {
            display: true,
            title: {
                display: true,
                text: 'No. of Product Items',
                font: {
                    family: 'Saira',
                    size: 20,
                    style: 'normal',
                    lineHeight: 1.2
                },
                padding: {top: 10, left: 0, right: 0, bottom: 0}
            }
        }
    },
    maintainAspectRatio: false,
    animation: {
        onComplete: () => {
            delayed = true;
        },
        delay: (context) => {
            let delay = 0;
            if (context.type === 'data' && context.mode === 'default' && !delayed) {
            delay = context.dataIndex * 100 + context.datasetIndex * 100;
            }
            return delay;
        },
    },
    plugins: {
        title: {
            display: true,
            text: 'Price Fluctuation Overview',
            align: 'start',
            font: {
                family: 'Saira',
                size: 20,
                style: 'normal',
                weight: 'bold',
                lineHeight: 1.2
            },
            padding: {top: 10, left: 0, right: 0, bottom: 0}
        },
        subtitle: {
            display: true,
            text: 'Monitoring Hike, Drop, and Stable Prices',
            align: 'start',
            position: 'top',
            font: {
                size: 12,
                family: 'Saira',
                weight: 'normal',
                style: 'normal'
            },
            padding: {
                bottom: 50
            }
        },
        legend: {
            title: {
                display: true,
                text: 'Price Movement',
                position: 'bottom',
            },
            position: 'bottom',
            align: 'end',
        }
    },
});

watchEffect(() => {
    chartData.value = {
        labels: props.monthlyPriceEvaluation?.labels || [],
        datasets: [
            {
                label: 'Drops',
                data: props.monthlyPriceEvaluation?.datasets?.drops || [],
                fill: true,
                backgroundColor: 'rgba(39, 183, 138, 0.1)',
                borderColor: 'rgba(39, 183, 138, 1)',
                tension: 0.2,
                pointRadius: 5,
                pointHoverRadius: 10
            },
            {
                label: 'Hikes',
                data: props.monthlyPriceEvaluation?.datasets?.hikes || [],
                backgroundColor: 'rgba(168, 45, 55, 0.1)',
                borderColor: 'rgba(168, 45, 55, 1)',
                fill: true,
                tension: 0.2,
                pointRadius: 5,
                pointHoverRadius: 10
            },
            {
                label: 'Stable',
                data: props.monthlyPriceEvaluation?.datasets?.stable || [],
                fill: true,
                backgroundColor: 'rgba(35, 2, 169, 0.1)',
                borderColor: 'rgba(35, 2, 169, 1)',
                tension: 0.2,
                pointRadius: 5,
                pointHoverRadius: 10
            },
        ]
    };
});

const fastMovingItems = ref([]);

const fetchData = async () => {
    try {
        const response = await axios.get('api/fast-moving-items');
        fastMovingItems.value = response.data;
    } catch (error) {
        console.log(error);
    }
}

const columns = [
    {
        data: 'code',
        title: 'Stock No#',
        width: '10%'
    },
    {
        data: 'description',
        title: 'Description',
        width: '50%'
    },
    {
        data: 'unit',
        title: 'Unit of Measurement',
        width: '10%'
    },
    {
        data: 'count',
        title: 'No. of Transactions',
        width: '10%'
    },
    {
        data: 'total_quantity',
        title: 'Quantity Issued',
        width: '10%'
    },
    {
        data: 'average',
        title: 'Average <br> <span class="text-xs">( Current Year )</span>',
        width: '10%'
    }
];

onMounted(() => {
    fetchData();
    
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
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col sm:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center" aria-current="page">
                        <a :href="route('dashboard')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Dashboard
                        </a>
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

        <div class="mt-8 w-screen-2xl rounded-md">
            <div class="overflow-hidden">

                <!-- APP -->
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg class="h-12 w-12 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" width="32" height="32" viewBox="0 0 48 48">
                                <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="4">
                                    <path d="m34 10l8 8m0-8l-8 8m10 12l-7 8l-4-4"/>
                                    <path fill="currentColor" d="M26 10H4v8h22zm0 20H4v8h22z"/>
                                </g>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('create-office-ppmp') || hasAnyRole(['Developer']) ? route('import.ppmp.index') : '#'">Office PPMP ({{ updatedCore.ppmpTransactions.year }})</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ updatedCore.ppmpTransactions.count }}</p>
                                <p class="text-sm text-gray-500">Offices</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg class="h-12 w-12 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" width="32" height="32" viewBox="0 0 24 24">
                                <path fill="currentColor" d="m17.275 20.25l3.475-3.45l-1.05-1.05l-2.425 2.375l-.975-.975l-1.05 1.075zM6 9h12V7H6zm12 14q-2.075 0-3.537-1.463T13 18t1.463-3.537T18 13t3.538 1.463T23 18t-1.463 3.538T18 23M3 22V5q0-.825.588-1.412T5 3h14q.825 0 1.413.588T21 5v6.675q-.7-.35-1.463-.513T18 11H6v2h7.1q-.425.425-.787.925T11.675 15H6v2h5.075q-.05.25-.062.488T11 18q0 1.05.288 2.013t.862 1.837L12 22l-1.5-1.5L9 22l-1.5-1.5L6 22l-1.5-1.5z"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-app-list') || hasAnyRole(['Developer']) ? route('conso.ppmp.type', { type: 'consolidated' , status: 'approved'}) : '#'">Consolidated PPMP ({{ updatedCore.consolidatedPpmp.year }})</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ updatedCore.consolidatedPpmp.status }}</p>
                                <p class="text-sm text-gray-500">Approved</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="mt-8 w-screen-2xl">
            <div class="overflow-hidden">

                <!-- TRANSACTIONS -->
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-4">
                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M16.5 12c2.5 0 4.5 2 4.5 4.5c0 .88-.25 1.71-.69 2.4l3.08 3.1L22 23.39l-3.12-3.07c-.69.43-1.51.68-2.38.68c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5m0 2a2.5 2.5 0 0 0-2.5 2.5a2.5 2.5 0 0 0 2.5 2.5a2.5 2.5 0 0 0 2.5-2.5a2.5 2.5 0 0 0-2.5-2.5M10 2h4a2 2 0 0 1 2 2v2h4a2 2 0 0 1 2 2v5.03A6.49 6.49 0 0 0 16.5 10a6.5 6.5 0 0 0-6.5 6.5c0 1.75.69 3.33 1.81 4.5H4a2 2 0 0 1-2-2V8c0-1.11.89-2 2-2h4V4c0-1.11.89-2 2-2m4 4V4h-4v2z"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-iar') || hasAnyRole(['Developer']) ? route('iar') : '#'">IAR Transaction</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ updatedCore.iarTransaction }}</p>
                                <p class="text-sm text-gray-500">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg class="h-12 w-12 text-cyan-600" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M9 11q-.85 0-1.425-.575T7 9V4q0-.825.575-1.412T9 2h6v9zm8 0V2h3q.825 0 1.413.588T22 4v5q0 .85-.587 1.425T20 11zM8 21v-8h6.8q.35 0 .6.2t.35.475t.038.575t-.338.525L13.975 16H10v1.5h4.5l4.05-3.35q.55-.4 1.163-.5t1.212.05t1.138.513t.912.937L17.1 20.075q-.55.45-1.2.688T14.55 21zm-5 1q-.425 0-.712-.288T2 21v-7q0-.425.288-.712T3 13h3v8q0 .425-.288.713T5 22z"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-ris') || hasAnyRole(['Developer']) ? route('ris.display.logs') : '#'">RIS Transaction</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ updatedCore.risTransaction }}</p>
                                <p class="text-sm text-gray-500">Issued</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <g fill="currentColor">
                                    <path d="M16.519 16.501c.175-.136.334-.295.651-.612l3.957-3.958c.096-.095.052-.26-.075-.305a4.332 4.332 0 0 1-1.644-1.034a4.332 4.332 0 0 1-1.034-1.644c-.045-.127-.21-.171-.305-.075L14.11 12.83c-.317.317-.476.476-.612.651c-.161.207-.3.43-.412.666c-.095.2-.166.414-.308.84l-.184.55l-.292.875l-.273.82a.584.584 0 0 0 .738.738l.82-.273l.875-.292l.55-.184c.426-.142.64-.212.84-.308c.236-.113.46-.25.666-.412Zm5.847-5.809a2.163 2.163 0 1 0-3.058-3.059l-.127.128a.524.524 0 0 0-.148.465c.02.107.055.265.12.452c.13.375.376.867.839 1.33a3.5 3.5 0 0 0 1.33.839c.188.065.345.1.452.12a.525.525 0 0 0 .465-.148l.127-.127Z"/>
                                    <path fill-rule="evenodd" d="M4.172 3.172C3 4.343 3 6.229 3 10v4c0 3.771 0 5.657 1.172 6.828C5.343 22 7.229 22 11 22h2c3.771 0 5.657 0 6.828-1.172C20.981 19.676 21 17.832 21 14.18l-2.818 2.818c-.27.27-.491.491-.74.686a5.107 5.107 0 0 1-.944.583a8.163 8.163 0 0 1-.944.355l-2.312.771a2.083 2.083 0 0 1-2.635-2.635l.274-.82l.475-1.426l.021-.066c.121-.362.22-.658.356-.944c.16-.335.355-.651.583-.943c.195-.25.416-.47.686-.74l4.006-4.007L18.12 6.7l.127-.127A3.651 3.651 0 0 1 20.838 5.5c-.151-1.03-.444-1.763-1.01-2.328C18.657 2 16.771 2 13 2h-2C7.229 2 5.343 2 4.172 3.172ZM7.25 9A.75.75 0 0 1 8 8.25h6.5a.75.75 0 0 1 0 1.5H8A.75.75 0 0 1 7.25 9Zm0 4a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5H8a.75.75 0 0 1-.75-.75Zm0 4a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5H8a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/>
                                </g>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-pr-transaction') || hasAnyRole(['Developer']) ? route('pr.display.transactions') : '#'">Purchase Request</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ updatedCore.prTransaction }}</p>
                                <p class="text-sm text-gray-500">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8 8a2 2 0 0 0 2.828 0l7.172-7.172a2 2 0 0 0 0-2.828l-8-8zM7 9a2 2 0 1 1 .001-4.001A2 2 0 0 1 7 9z"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-pr-transaction') || hasAnyRole(['Developer']) ? route('pr.show.onProcess') : '#'">Purchase Order</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">0</p>
                                <p class="text-sm text-gray-500">On Process</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRODUCT -->
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-4 mt-8">
                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path fill="currentColor" fill-rule="evenodd" d="M0 4.6A2.6 2.6 0 0 1 2.6 2h18.8A2.6 2.6 0 0 1 24 4.6v.8A2.6 2.6 0 0 1 21.4 8H21v10.6c0 1.33-1.07 2.4-2.4 2.4H5.4C4.07 21 3 19.93 3 18.6V8h-.4A2.6 2.6 0 0 1 0 5.4zM2.6 4a.6.6 0 0 0-.6.6v.8a.6.6 0 0 0 .6.6h18.8a.6.6 0 0 0 .6-.6v-.8a.6.6 0 0 0-.6-.6zM8 10a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-inventory') || hasAnyRole(['Developer']) ? route('inventory.index') : '#'">Available On Inventory</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ updatedCore.availableProductItem }}</p>
                                <p class="text-sm text-gray-500">Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M19 4h-2V2h-2v2H9V2H7v2H5c-1.103 0-2 .897-2 2v14c0 1.103.897 2 2 2h14c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2m-6 16h-2v-2h2zm0-4h-2v-5h2zm6-7H5V7h14z"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-expired-products') || hasAnyRole(['Developer']) ? route('show.expiry.products') : '#'">Expiring Product Items</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ updatedCore.expiringProduct }}</p>
                                <p class="text-sm text-gray-500">Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <defs>
                                    <mask id="ipSOrder0">
                                        <g fill="none" stroke-linejoin="round" stroke-width="4">
                                            <path fill="#fff" stroke="#fff" d="M33.05 7H38a2 2 0 0 1 2 2v33a2 2 0 0 1-2 2H10a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h7v3h14V7z"/>
                                            <path stroke="#fff" stroke-linecap="round" d="M17 4h14v6H17z"/>
                                            <path stroke="#000" stroke-linecap="round" d="m27 19l-8 8.001h10.004l-8.004 8"/>
                                        </g>
                                    </mask>
                                </defs>
                                <path fill="currentColor" d="M0 0h48v48H0z" mask="url(#ipSOrder0)"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-inventory') || hasAnyRole(['Developer']) ? route('inventory.index') : '#'">Re-Order Product Items</a>
                            </h3>
                            <h3 class="text-sm tracking-wider"></h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ updatedCore.reOrder }}</p>
                                <p class="text-sm text-gray-500">Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-zinc-300 rounded-md overflow-hidden">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-rose-600" fill="currentColor" stroke="currentColor" viewBox="0 0 576 512">
                                <path fill="currentColor" d="M0 24C0 10.7 10.7 0 24 0h45.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5l-51.6-271c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24m128 440a48 48 0 1 1 96 0a48 48 0 1 1-96 0m336-48a48 48 0 1 1 0 96a48 48 0 1 1 0-96"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-inventory') || hasAnyRole(['Developer']) ? route('inventory.index') : '#'">Out Of Stock</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ updatedCore.outOfStockProducts }}</p>
                                <p class="text-sm text-gray-500">Items</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 w-screen-2xl bg-zinc-300 shadow rounded-md">
            <div class="p-2 px-5 overflow-hidden shadow-sm sm:rounded-lg">
                <LineChart :data="chartData" :options="chartOptions" />
            </div>
        </div>

        <div class="w-screen-2xl bg-zinc-300 shadow rounded-md mt-8 mb-4">
            <div class="flex flex-col justify-start my-4 px-5">
                <h4 class="text-xl font-bold text-gray-500">Fast Moving Product Items</h4>
                <p class="text-gray-600 text-xs">Fast-moving items that are regularly out of stock due to strong demand.</p>
            </div>
            <div class="overflow-hidden px-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="columns"
                        :data="fastMovingItems.products"
                        :options="{  
                            paging: false,
                            searching: false,
                            ordering: false,
                            info: false
                        }">
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
        color: #1a0037;
    }

    :deep(table.dataTable tbody > tr > td) {
        border-right: 2px solid #7393dc;
        text-align: center;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(6)) {
        text-align: right !important;
    }
</style>
