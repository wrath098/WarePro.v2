<script setup>
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { ref, computed, onMounted } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';   
    import Swal from 'sweetalert2';
    
    const page = usePage();
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);
    const isLoading = ref(false);

    const props = defineProps({
        toPr: {
            type: Object,
            default: () => ({}),
        },
        prInfo: Object,
    });

    const searchQuery = ref("");
    const sortColumn = ref("prodCode");
    const sortOrder = ref("asc");

    const particularList = computed(() => {
        return props.toPr ? Object.values(props.toPr) : [];
    });


    const filteredParticularList = computed(() => {
        return particularList.value.filter(particular => {
            return (
                particular.prodCode.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                particular.prodName.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                particular.prodUnit.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                particular.prodPrice.toString().includes(searchQuery.value) ||
                particular.qty.toString().includes(searchQuery.value) ||
                particular.amount.toString().includes(searchQuery.value)
            );
        });
    });


    const sortedParticularList = computed(() => {
        const sortedList = [...filteredParticularList.value];
        sortedList.sort((a, b) => {
            let compareA = a[sortColumn.value];
            let compareB = b[sortColumn.value];

            if (typeof compareA === "string") compareA = compareA.toLowerCase();
            if (typeof compareB === "string") compareB = compareB.toLowerCase();

            if (sortOrder.value === "asc") {
                return compareA > compareB ? 1 : compareA < compareB ? -1 : 0;
            } else {
                return compareA < compareB ? 1 : compareA > compareB ? -1 : 0;
            }
        });
        return sortedList;
    });


    const toggleSort = (column) => {
        if (sortColumn.value === column) {
            sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
        } else {
            sortColumn.value = column;
            sortOrder.value = "asc";
        }
    };

    const selectedItems = ref([]);
    const handleCheckboxChange = (prodCode, isChecked) => {
        const particular = Object.values(props.toPr).find(item => item.prodCode === prodCode);
        if (isChecked) {
            if (!selectedItems.value.some(item => item.prodCode === prodCode)) {
                selectedItems.value.push(particular);
            }
        } else {
            selectedItems.value = selectedItems.value.filter(item => item.prodCode !== prodCode);
        }
    };

    const isAllSelected = computed(() => {
        return filteredParticularList.value.length > 0 &&
            filteredParticularList.value.every(item => 
                selectedItems.value.some(selectedItem => selectedItem.prodCode === item.prodCode)
            );
    });

    const toggleSelectAll = (isChecked) => {
        if (isChecked) {
            selectedItems.value = [...filteredParticularList.value];
        } else {
            selectedItems.value = [];
        }
    };

    const formatDecimal = (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    };

    const createForm = useForm({
        selectedItems: [],
        prTransactionInfo: {},
    });
    const nextStep = () => {
        createForm.selectedItems = selectedItems.value;
        createForm.prTransactionInfo = props.prInfo;

        createForm.post(route('pr.form.submit'), {
            preserveScroll: true,
            onSuccess: () => {
                if (errMessage.value) {
                    Swal.fire({
                        title: 'Failed',
                        text: errMessage.value,
                        icon: 'error',
                    }).then(() => {
                        isLoading.value = false;
                    });
                } else {
                    createForm.reset();
                    Swal.fire({
                        title: 'Success',
                        text: message.value,
                        icon: 'success',
                    }).then(() => {
                        isLoading.value = false;
                    });
                }
            },
            onError: (errors) => {
                isLoading.value = false;
                console.error('Error: ' + JSON.stringify(errors));
            },
        });
    };
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
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Create
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Step 2
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-full bg-white shadow rounded-md mb-8">
            <div class="px-6 py-4 bg-indigo-900 text-white rounded-t mb-5">
                <h1 class="text-lg font-bold">Step 2: Purchase Request Particulars</h1>
            </div>

            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="relative overflow-x-auto md:overflow-hidden">
                            
                            <div class="h-auto m-2 flex justify-end items-center px-2">
                                <div class="relative">
                                    <input type="text" v-model="searchQuery" class="h-12 w-96 pr-8 pl-5 rounded z-0 focus:shadow focus:outline-none" placeholder="Search anything...">
                                    <div class="absolute top-4 right-3">
                                        <i class="fa fa-search text-gray-400 z-20 hover:text-gray-500"></i>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="nextStep" class="space-y-5 mx-2">
                                <div class="w-full mx-auto sm:px-6 lg:px-2">
                                    <table class="w-full text-gray-900 display border-2 border-[#7393dc]">
                                        <thead class="text-sm uppercase bg-[#d8d8f6] text-center text-[#03244d]">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 w-1/12 border-2 border-[#7393dc]">
                                                    <label for="selectAll">
                                                        All
                                                    </label>
                                                    <br>
                                                    <input 
                                                        type="checkbox"
                                                        id="selectAll"
                                                        :checked="isAllSelected"
                                                        @change="toggleSelectAll($event.target.checked)"
                                                    />
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-2/12 border-2 border-[#7393dc]" @click="toggleSort('prodCode')">
                                                    Stock No. 
                                                    <span v-if="sortColumn === 'prodCode'">
                                                        ({{ sortOrder === 'asc' ? '↑' : '↓' }})
                                                    </span>
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-5/12 border-2 border-[#7393dc]" @click="toggleSort('prodName')">
                                                    Description 
                                                    <span v-if="sortColumn === 'prodName'">
                                                        ({{ sortOrder === 'asc' ? '↑' : '↓' }})
                                                    </span>
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12 border-2 border-[#7393dc]" @click="toggleSort('prodUnit')">
                                                    Unit Of Measure
                                                    <span v-if="sortColumn === 'prodUnit'">
                                                        ({{ sortOrder === 'asc' ? '↑' : '↓' }})
                                                    </span>
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12 border-2 border-[#7393dc]" @click="toggleSort('prodPrice')">
                                                    Price
                                                    <span v-if="sortColumn === 'prodPrice'">
                                                        ({{ sortOrder === 'asc' ? '↑' : '↓' }})
                                                    </span>
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12 border-2 border-[#7393dc]" @click="toggleSort('qty')">
                                                    Quantity
                                                    <span v-if="sortColumn === 'qty'">
                                                        ({{ sortOrder === 'asc' ? '↑' : '↓' }})
                                                    </span>
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12 border-2 border-[#7393dc]" @click="toggleSort('amount')">
                                                    Amount
                                                    <span v-if="sortColumn === 'amount'">
                                                        ({{ sortOrder === 'asc' ? '↑' : '↓' }})
                                                    </span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="particular in sortedParticularList" :key="particular.prodCode" class="bg-white border-b hover:bg-gray-100">
                                                <td class="px-6 py-3 text-center border-r-2 border-[#7393dc]">
                                                    <input 
                                                        type="checkbox" 
                                                        :checked="selectedItems.some(item => item.prodCode === particular.prodCode)"
                                                        @change="handleCheckboxChange(particular.prodCode, $event.target.checked)"
                                                    />
                                                </td>
                                                <td class="px-6 py-3 text-center border-r-2 border-[#7393dc]">{{ particular.prodCode }}</td>
                                                <td class="px-6 py-3 text-left border-r-2 border-[#7393dc]">{{ particular.prodName }}</td>
                                                <td class="px-6 py-3 text-center border-r-2 border-[#7393dc]">{{ particular.prodUnit }}</td>
                                                <td class="px-6 py-3 text-right border-r-2 border-[#7393dc]">{{ formatDecimal(particular.prodPrice) }}</td>
                                                <td class="px-6 py-3 text-right border-r-2 border-[#7393dc]">{{ particular.qty }}</td>
                                                <td class="px-6 py-3 text-right border-r-2 border-[#7393dc]">{{ particular.amount }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div>
                                    <button class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none">
                                        Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>