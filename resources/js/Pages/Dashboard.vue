<script setup>
import LineChart from '@/Components/LineChart.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watchEffect } from 'vue';
import useAuthPermission from '@/Composables/useAuthPermission';
import Swal from 'sweetalert2';

const page = usePage();
const message = computed(() => page.props.flash.message);
const errMessage = computed(() => page.props.flash.error);

const {hasAnyRole, hasPermission} = useAuthPermission();
const props = defineProps({
    core: {
        type: Object,
        required: true,
        default: () => ({})
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

const chartData = ref({
    labels: [],
    datasets: []
});

const chartOptions = ref({
    responsive: true,
    scales: {
        x: {
            display: true,
            title: {
                display: true,
                text: 'Months',
                font: {
                    family: 'Oswald',
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
                    family: 'Oswald',
                    size: 20,
                    style: 'normal',
                    lineHeight: 1.2
                },
                padding: {top: 10, left: 0, right: 0, bottom: 0}
            }
        }
    },
    maintainAspectRatio: false,
    plugins: {
        title: {
            display: true,
            text: 'Price Adjustment Monitoring',
            font: {
                family: 'Oswald',
                size: 20,
                style: 'normal',
                weight: 'bold',
                lineHeight: 1.2
            },
            padding: {top: 10, left: 0, right: 0, bottom: 5}
        },
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

onMounted(() => {
    fetchData();
});

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
            <nav class="flex" aria-label="Breadcrumb">
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
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl bg-slate-50 shadow rounded-md">
            <div class="overflow-hidden shadow-sm sm:rounded-lg">

                <!-- TRANSACTIONS -->
                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-4">
                    <div class="flex items-center border bg-white rounded-sm overflow-hidden shadow">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 16 16">
                                <path fill="currentColor" d="M15.363 14.658a.5.5 0 1 1-.713.7l-2.97-3.023a.5.5 0 0 1 .001-.7A3.9 3.9 0 1 0 8.9 12.8a.5.5 0 1 1 0 .999a4.9 4.9 0 1 1 3.821-1.833zM3.094 13a.5.5 0 1 1 0 1H2.5A2.5 2.5 0 0 1 0 11.5v-9A2.5 2.5 0 0 1 2.5 0h9A2.5 2.5 0 0 1 14 2.5v.599a.5.5 0 1 1-1 0V2.5A1.5 1.5 0 0 0 11.5 1h-9A1.5 1.5 0 0 0 1 2.5v9A1.5 1.5 0 0 0 2.5 13zM2.5 3a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1m2 0a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1m2 0a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1m-4 2a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1m2 0a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1m-2 1a.5.5 0 1 1 0 1a.5.5 0 0 1 0-1m0 3a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1m6-6a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1m2 0a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1m-8 8a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-iar') || hasAnyRole(['Developer']) ? route('iar') : '#'">IAR Transaction</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ core.iarTransaction }}</p>
                                <p class="text-sm text-gray-400">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 2048 2048">
                                <path fill="currentColor" d="M896 1537V936L256 616v880l544 273l-31 127l-641-320V472L960 57l832 415v270q-70 11-128 45V616l-640 320v473l-128 128zM754 302l584 334l247-124l-625-313l-206 103zm206 523l240-120l-584-334l-281 141l625 313zm888 71q42 0 78 15t64 41t42 63t16 79q0 39-15 76t-43 65l-717 717l-377 94l94-377l717-716q29-29 65-43t76-14zm51 249q21-21 21-51q0-31-20-50t-52-20q-14 0-27 4t-23 15l-692 692l-34 135l135-34l692-691z"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-ris') || hasAnyRole(['Developer']) ? route('ris.display.logs') : '#'">RIS Transaction</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ core.risTransaction }}</p>
                                <p class="text-sm text-gray-400">Issued Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
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
                                <p class="text-3xl mr-2">{{ core.prTransaction }}</p>
                                <p class="text-sm text-gray-400">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
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
                                <p class="text-sm text-gray-400">On Process</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRODUCT -->
                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-4">
                    <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                        <div class="p-4">
                            <svg class="h-12 w-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7.24 2H5.34C3.15 2 2 3.15 2 5.33V7.23C2 9.41 3.15 10.56 5.33 10.56H7.23C9.41 10.56 10.56 9.41 10.56 7.23V5.33C10.57 3.15 9.42 2 7.24 2Z" fill="currentColor"/>
                                <path opacity="0.4" d="M18.6695 2H16.7695C14.5895 2 13.4395 3.15 13.4395 5.33V7.23C13.4395 9.41 14.5895 10.56 16.7695 10.56H18.6695C20.8495 10.56 21.9995 9.41 21.9995 7.23V5.33C21.9995 3.15 20.8495 2 18.6695 2Z" fill="#292D32"/>
                                <path d="M18.6695 13.4302H16.7695C14.5895 13.4302 13.4395 14.5802 13.4395 16.7602V18.6602C13.4395 20.8402 14.5895 21.9902 16.7695 21.9902H18.6695C20.8495 21.9902 21.9995 20.8402 21.9995 18.6602V16.7602C21.9995 14.5802 20.8495 13.4302 18.6695 13.4302Z" fill="currentColor"/>
                                <path opacity="0.4" d="M7.24 13.4302H5.34C3.15 13.4302 2 14.5802 2 16.7602V18.6602C2 20.8502 3.15 22.0002 5.33 22.0002H7.23C9.41 22.0002 10.56 20.8502 10.56 18.6702V16.7702C10.57 14.5802 9.42 13.4302 7.24 13.4302Z" fill="#292D32"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-inventory') || hasAnyRole(['Developer']) ? route('inventory.index') : '#'">Available On Inventory</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ core.availableProductItem }}</p>
                                <p class="text-sm text-gray-400">Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 256 256">
                                <path fill="currentColor" d="M128 20a108 108 0 1 0 108 108A108.12 108.12 0 0 0 128 20Zm0 192a84 84 0 1 1 84-84a84.09 84.09 0 0 1-84 84Zm-12-80V80a12 12 0 0 1 24 0v52a12 12 0 0 1-24 0Zm28 40a16 16 0 1 1-16-16a16 16 0 0 1 16 16Z"/>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-expired-products') || hasAnyRole(['Developer']) ? route('show.expiry.products') : '#'">Expiring Product Items</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ core.expiringProduct }}</p>
                                <p class="text-sm text-gray-400">Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                        <div class="p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <g fill="currentColor">
                                    <path d="M9.446 3.25c-1.133 0-2.058 0-2.79.098c-.763.103-1.425.325-1.954.854c-.529.529-.751 1.19-.854 1.955c-.098.73-.098 1.656-.098 2.79v6.06c-.465.013-.76.056-1 .194a1.5 1.5 0 0 0-.549.549C2 16.098 2 16.565 2 17.5s0 1.402.201 1.75a1.5 1.5 0 0 0 .549.549C3.098 20 3.565 20 4.5 20s1.402 0 1.75-.201a1.5 1.5 0 0 0 .549-.549C7 18.902 7 18.435 7 17.5s0-1.402-.201-1.75a1.5 1.5 0 0 0-.549-.549c-.24-.138-.535-.181-1-.195V9c0-1.2.002-2.024.085-2.643c.08-.598.224-.89.428-1.094c.203-.204.496-.348 1.094-.428C7.476 4.752 8.3 4.75 9.5 4.75h5c1.2 0 2.024.002 2.643.085c.598.08.89.224 1.094.428c.204.203.348.496.428 1.094c.083.619.085 1.443.085 2.643v1.19l-.72-.72a.75.75 0 1 0-1.06 1.06l2 2a.75.75 0 0 0 1.06 0l2-2a.75.75 0 1 0-1.06-1.06l-.72.72V8.945c0-1.133 0-2.058-.098-2.79c-.103-.763-.325-1.425-.854-1.954c-.529-.529-1.19-.751-1.955-.854c-.73-.098-1.656-.098-2.79-.098H9.447Z"/>
                                    <path d="M9.701 15.75c-.201.348-.201.815-.201 1.75s0 1.402.201 1.75a1.5 1.5 0 0 0 .549.549c.348.201.815.201 1.75.201s1.402 0 1.75-.201a1.5 1.5 0 0 0 .549-.549c.201-.348.201-.815.201-1.75s0-1.402-.201-1.75a1.5 1.5 0 0 0-.549-.549C13.402 15 12.935 15 12 15s-1.402 0-1.75.201a1.5 1.5 0 0 0-.549.549ZM17 17.5c0-.935 0-1.402.201-1.75a1.5 1.5 0 0 1 .549-.549C18.098 15 18.565 15 19.5 15s1.402 0 1.75.201a1.5 1.5 0 0 1 .549.549c.201.348.201.815.201 1.75s0 1.402-.201 1.75a1.5 1.5 0 0 1-.549.549c-.348.201-.815.201-1.75.201s-1.402 0-1.75-.201a1.5 1.5 0 0 1-.549-.549C17 18.902 17 18.435 17 17.5Z"/>
                                </g>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-inventory') || hasAnyRole(['Developer']) ? route('inventory.index') : '#'">Re-Order Product Items</a>
                            </h3>
                            <h3 class="text-sm tracking-wider"></h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ core.redorder }}</p>
                                <p class="text-sm text-gray-400">Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                        <div class="p-4">
                            <svg class="h-12 w-12 text-rose-600" fill="currentColor" stroke="currentColor" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 544.527 544.527" xml:space="preserve">
                                <g>
                                    <g>
                                        <polygon points="286.334,234.111 148.331,234.111 152.68,286.132 281.986,286.132 		"/>
                                        <polygon points="144.993,384.052 138.331,301.432 22.951,301.432 44.117,384.052 		"/>
                                        <polygon points="146.278,399.352 48.199,399.352 60.916,448.312 150.284,448.312 		"/>
                                        <path d="M424.71,253.111c16.849-16.87,33.694-34.299,50.542-49.6h43.568c27.54,0,27.54-48.96,0-48.96h-52.381
                                            c-5.125,0-9.36,1.799-12.741,4.045c-1.983,1.077-3.927,2.708-5.777,4.56c-21.105,21.132-42.21,42.402-63.318,63.535
                                            c-2.312,2.316-3.95,4.36-5.067,7.42h-77.567l-4.349,52.021h118.178L424.71,253.111z"/>
                                        <polygon points="18.869,286.132 137.046,286.132 132.698,234.111 5.053,234.111 		"/>
                                        <polygon points="268.748,448.312 272.754,399.352 161.912,399.352 165.914,448.312 		"/>
                                        <polygon points="411.715,301.432 296.334,301.432 289.67,384.052 390.549,384.052 		"/>
                                        <polygon points="386.467,399.352 288.385,399.352 284.382,448.312 373.749,448.312 		"/>
                                        <polygon points="280.701,301.432 153.965,301.432 160.626,384.052 274.036,384.052 		"/>
                                        <circle cx="108.117" cy="503.339" r="41.188"/>
                                        <circle cx="326.546" cy="503.339" r="41.188"/>
                                        <path d="M125.351,171.366l3.418-29.707c121.029,13.926,163.694,52.405,163.694,52.405S290.615,46.846,141.651,29.707L145.069,0
                                            L5.506,70.759L125.351,171.366z"/>
                                    </g>
                                </g>
                            </svg>
                        </div>
                        <div class="px-4 text-gray-700">
                            <h3 class="text-sm tracking-wider">
                                <a class="hover:underline" :href="hasPermission('view-inventory') || hasAnyRole(['Developer']) ? route('inventory.index') : '#'">Out Of Stock</a>
                            </h3>
                            <div class="flex justify-start items-center">
                                <p class="text-3xl mr-2">{{ core.outOfStockProducts }}</p>
                                <p class="text-sm text-gray-400">Items</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4 max-w-screen-2xl bg-slate-50 shadow rounded-md">
            <div class="p-2 overflow-hidden shadow-sm sm:rounded-lg">
                <LineChart :data="chartData" :options="chartOptions" />
            </div>
        </div>

        <div class="max-w-screen-2xl bg-white shadow rounded-md mb-4">
            <div class="flex justify-center my-4">
                <h4 class="text-xl font-bold text-gray-500">Fast Moving Product Items</h4>
            </div>
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg"
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
        color: #03244d;
    }

    :deep(table.dataTable tbody > tr > td) {
        border-right: 2px solid #7393dc;
        text-align: center;
    }

    :deep([data-v-8e8f4dea] table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: left !important;
    }

    :deep([data-v-8e8f4dea] table.dataTable tbody > tr > td:nth-child(6)) {
        text-align: right !important;
    }
</style>
