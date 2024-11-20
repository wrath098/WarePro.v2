<script setup>
    import { Head } from '@inertiajs/vue3';
    import { ref, computed } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';   
    import Sidebar from '@/Components/Sidebar.vue';

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

    const nextStep = () => {
        const requestData = {
            selectedItems: selectedItems.value,
            prTransactionInfo: props.prInfo,
        };
        Inertia.post('step-3', requestData);
    };
</script>

<template>
    <Head title="PR" />
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="text-green-700" aria-current="page">Purchase Request</li> 
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="mx-2 w-full bg-white p-4 rounded-md shadow mt-5 md:mt-0 flex flex-col justify-center align-middle">
                        <h3 class="my-2 text-[#07074D] text-center">
                            Step 2: Purchase Request Particulars
                        </h3>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="relative overflow-x-auto md:overflow-hidden">
                                <div class="mb-4">
                                    <input 
                                        type="text" 
                                        v-model="searchQuery" 
                                        placeholder="Search..." 
                                        class="border rounded p-2 w-full"
                                    />
                                </div>

                                <form @submit.prevent="nextStep" class="space-y-5 mx-2">
                                    <div class="w-full mx-auto sm:px-6 lg:px-2">
                                        <table class="w-full text-gray-900 display">
                                            <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                                <tr class="text-center">
                                                    <th scope="col" class="px-6 py-3 w-1/12">
                                                        <input 
                                                            type="checkbox" 
                                                            :checked="isAllSelected"
                                                            @change="toggleSelectAll($event.target.checked)"
                                                        />
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-1/12" @click="toggleSort('prodCode')">
                                                        Stock No. 
                                                        <span v-if="sortColumn === 'prodCode'">
                                                            ({{ sortOrder === 'asc' ? '↑' : '↓' }})
                                                        </span>
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-6/12 text-center" @click="toggleSort('prodName')">
                                                        Description 
                                                        <span v-if="sortColumn === 'prodName'">
                                                            ({{ sortOrder === 'asc' ? '↑' : '↓' }})
                                                        </span>
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-1/12" @click="toggleSort('prodUnit')">
                                                        Unit Of Measure
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-1/12" @click="toggleSort('prodPrice')">
                                                        Price
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-1/12" @click="toggleSort('qty')">
                                                        Quantity
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-1/12" @click="toggleSort('amount')">
                                                        Amount
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="particular in sortedParticularList" :key="particular.prodCode">
                                                    <td class="px-6 py-3">
                                                        <input 
                                                            type="checkbox" 
                                                            :checked="selectedItems.some(item => item.prodCode === particular.prodCode)"
                                                            @change="handleCheckboxChange(particular.prodCode, $event.target.checked)"
                                                        />
                                                    </td>
                                                    <td class="px-6 py-3">{{ particular.prodCode }}</td>
                                                    <td class="px-6 py-3 text-left">{{ particular.prodName }}</td>
                                                    <td class="px-6 py-3">{{ particular.prodUnit }}</td>
                                                    <td class="px-6 py-3">{{ particular.prodPrice }}</td>
                                                    <td class="px-6 py-3">{{ particular.qty }}</td>
                                                    <td class="px-6 py-3">{{ particular.amount }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div>
                                        <button class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none">
                                            Next
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>