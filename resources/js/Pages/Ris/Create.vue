<script setup>
    import { reactive, ref, computed, onBeforeUnmount } from 'vue';
    import { Head, router, useForm, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import axios from 'axios';
    import Swal from 'sweetalert2';
    import { debounce } from 'lodash';

    const page = usePage();
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);
    const isLoading = ref(false);

    const props = defineProps({
        office: Object
    });

    const modalState = ref(false);
    const closeModal = () => { modalState.value = false; }
    const showModal = () => { modalState.value = true; }
    const requestDataTable = ref(false);

    const years = generateYears();
    function generateYears() {
        const currentYear = new Date().getFullYear() - 2;
        return Array.from({ length: 3 }, (_, i) => currentYear + (3 - 1 - i));
    }

    const searchOfficePpmp = reactive({
        officeId: '',
        year: '',
    });

    const officePpmpParticulars = ref([]);
    const fetchOfficePpmp = async () => {
        if (searchOfficePpmp.officeId && searchOfficePpmp.year) {
            try {
                const response = await axios.get('api/office-ppmp-particulars', { params: searchOfficePpmp });
                officePpmpParticulars.value = response.data;
                requestDataTable.value = response.data ? true : false;
            } catch (error) {
                console.error('Error fetching office data:', error);
            }
        }
    };

    const rows = reactive([
        {
            id: '',
            prodInvId: '',
            prodId: '',
            stockNo: '',
            description: '',
            unit: '',
            treshFirstQty: '',
            treshSecondQty: '',
            stockAvailable: '',
            requestedQty: '',
            remainingQty:'',
            showStockSuggestions: false,
            showDescSuggestions: false,
        }
    ]);

    function addRow() {
        rows.push({
            id: '',
            prodInvId: '',
            prodId: '',
            stockNo: '',
            description: '',
            unit: '',
            treshFirstQty: '',
            treshSecondQty: '',
            stockAvailable: '',
            requestedQty: '',
            remainingQty:'',
            showStockSuggestions: false,
            showDescSuggestions: false,
        })
    };

    function deleteRow(index) {
        const deletedRow = rows[index];
        rows.splice(index, 1)

        requestData.value = requestData.value.filter(item => item.id !== deletedRow.id);
    };

    const requestData = ref([]);

    function getFilteredStockNo(row) {
        row.showStockSuggestions = true;
        const query = row.stockNo?.toLowerCase().trim() ?? '';

        if (!query) {
            row.suggestions = [];
            return [];
        }

        const filtered = officePpmpParticulars.value.data.filter(item => {
            const stockNo = item.prodStockNo ?? '';
            return stockNo.toLowerCase().includes(query);
        });

        row.suggestions = filtered.slice(0, 10);
        return row.suggestions;
    }

    function getFilteredDescription(row) {
        row.showStockSuggestions = true;
        const query = row.description?.toLowerCase().trim() ?? '';

        if (!query) {
            row.suggestions = [];
            return [];
        }

        const filtered = officePpmpParticulars.value.data.filter(item => {
            const itemName = item.prodDesc ?? '';
            return itemName.toLowerCase().includes(query);
        });

        row.suggestions = filtered.slice(0, 10);
        return row.suggestions;
    }

    function selectSuggestion(row, item) {
        row.id = item.id;
        row.prodInvId = item.prodInvId,
        row.prodId = item.prodId,
        row.stockNo = item.prodStockNo;
        row.description = item.prodDesc;
        row.unit = item.prodUnit;
        row.suggestions = [];
        row.treshFirstQty = item.treshFirstQty,
        row.treshSecondQty = item.treshSecondQty,
        row.stockAvailable = item.availableStock,
        row.remainingQty = item.remainingQty,
        row.showStockSuggestions = false;
    }

    function requestedItemQty(row) {
        const query = row.id?.toString() || '';

        const filtered = requestData.value.filter(item => {
            const transId = item.id?.toString() || '';
            return transId.includes(query);
        })

        if (filtered.length > 0) {

            requestData.value = requestData.value.map(item => {
            const transId = item.id?.toString() || '';
            if (transId.includes(query)) {
                return row;
            }
            return item;
            })
        } else {
            requestData.value.push(row)
        }
    }

    const submit = () => {
        showModal();
    };

    const create = useForm({
        risNo: '',
        receivedBy: '',
        risDate: '',
        remarks: '',
        officeId: null,
        ppmpYear: null,
        requestProducts: null,
        file: null,
    });
    
    const confirmSubmit = () => {
        isLoading.value = true;
        create.officeId = searchOfficePpmp.officeId;
        create.ppmpYear = searchOfficePpmp.year;
        create.file = '';
        create.requestProducts = JSON.stringify(requestData.value);

        create.post(route('store.ris'), {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            forceFormData: true,
            onError: (errors) => {
                isLoading.value = false;
                console.error('Error: ' + JSON.stringify(errors));
            },
            onSuccess: () => {
                if (errMessage.value) {
                    Swal.fire({
                        title: 'Failed',
                        text: errMessage.value,
                        icon: 'error',
                    });
                } else {
                    create.reset();
                    isLoading.value = false;
                    Swal.fire({
                        title: 'Success',
                        text: message.value,
                        icon: 'success',
                    }).then(() => {
                        closeModal();
                        router.visit(route('create.ris'), {
                            preserveScroll: true,
                            preserveState: false,
                        });
                    });
                }
            }
        })
    };

    const requestedItemQtyDebounced = debounce((row) => {
        requestedItemQty(row)
    }, 300);

    onBeforeUnmount(() => {
        requestedItemQtyDebounced.cancel()
    });
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
                            <a :href="route('create.ris')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Releasing
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="w-full my-4 bg-white shadow rounded-md">
            <div class="bg-indigo-800 text-white p-4 flex justify-between rounded-t-md">
                <div class="flex flex-wrap">
                    <p class="text-lg text-gray-100">
                        <strong class="font-semibold">Requested Product Information</strong>
                    </p>
                </div>
            </div>
            <div class="p-4 shadow-md sm:rounded-lg">
                <div class="grid lg:grid-cols-2 lg:gap-6 mb-5">
                    <div class="relative z-0 w-full group mb-5 lg:mb-0">
                        <select v-model="searchOfficePpmp.officeId" @change="fetchOfficePpmp" name="officeId" id="officeId" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" :disabled="requestData?.length > 0" required>
                            <option value="" disabled selected class="pl-5">Select Office/End-User</option>
                            <option v-for="user in office" :key="user.id" :value="user.id" class="ml-5">{{ user.office_code }}</option>
                            <option value="others" class="ml-5">OTHERS...</option>
                        </select>
                        <label for="officeId" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office/End-User</label>
                    </div>

                    <div class="relative z-0 w-full group">
                        <select v-model="searchOfficePpmp.year" @change="fetchOfficePpmp" name="ppmpYear" id="ppmpYear"  class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                            <option value="" disabled selected>Select year</option>
                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                        </select>
                        <label for="ppmpYear" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Calendar Year</label>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="requestDataTable" class="w-full my-4 bg-white shadow rounded-md">
            <div class="w-full p-4 relative">
                <table class="w-full text-sm text-left rtl:text-right text-[#03244d] border-2 border-[#7393dc] rounded-md">
                    <thead class="text-[#03244d] uppercase bg-[#d8d8f6] ">
                        <tr class="rounded-md">
                            <th class="px-6 py-3 text-center border-2 border-[#7393dc] hidden">Id</th>
                            <th class="px-6 py-3 w-[10%] text-center border-2 border-[#7393dc]">Stock No.</th>
                            <th class="px-6 py-3 w-[40%] text-center border-2 border-[#7393dc]">Description</th>
                            <th class="px-6 py-3 w-[10%] text-center border-2 border-[#7393dc]">Unit of Measurement</th>
                            <th class="px-6 py-3 w-[8%] text-center border-2 border-[#7393dc]">Qty (1st)</th>
                            <th class="px-6 py-3 w-[8%] text-center border-2 border-[#7393dc]">Qty (2nd)</th>
                            <th class="px-6 py-3 w-[8%] text-center border-2 border-[#7393dc]">Stock Available</th>
                            <th class="px-6 py-3 w-[8%] text-center border-2 border-[#7393dc]">Remaining Qty</th>
                            <th class="px-6 py-3 w-[8%] text-center border-2 border-[#7393dc]">Requested Qty</th>
                            <th class="px-6 py-3 w-[8%] text-center border-2 border-[#7393dc]">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in rows" :key="index">
                            <td class="p-1 border-2 border-[#7393dc] hidden"><input v-model="row.id" type="text" /></td>
                            <td class="p-1 border-2 border-[#7393dc]">
                                <input v-model="row.stockNo" 
                                    @input="getFilteredStockNo(row)"
                                    @focus="row.showStockSuggestions = true"
                                    @blur="row.showStockSuggestions = false"
                                    type="text"
                                    class="w-full rounded-md"/>

                                <ul v-if="row.showStockSuggestions && row.suggestions?.length" class="absolute text-left z-10 w-full bg-white border border-gray-300 mt-1 max-h-52 overflow-y-auto rounded shadow">
                                    <li v-for="(item, i) in row.suggestions" :key="i" @mousedown.prevent="selectSuggestion(row, item)" class="px-4 py-2 cursor-pointer hover:bg-blue-100">
                                        {{ item.prodStockNo }} - {{ item.prodDesc }}
                                    </li>
                                </ul>
                                <ul v-else-if="row.showStockSuggestions && row.suggestions?.length == 0" class="absolute text-left z-10 w-full bg-white border border-gray-300 mt-1 max-h-52 overflow-y-auto rounded shadow">
                                    <li class="px-4 py-2 cursor-pointer hover:bg-rose-100">
                                        No Item Found!
                                    </li>
                                </ul>
                            </td>
                            <td class="p-1 text-center border-2 border-[#7393dc]">
                                <input v-model="row.description" @input="getFilteredDescription(row)" type="text" class="w-full rounded-md" />
                            </td>
                            <td class="p-1 text-center border-2 border-[#7393dc]">
                                <input v-model="row.unit" type="text" class="w-full text-center border-0" disabled/>
                            </td>
                            <td class="p-1 text-center border-2 border-[#7393dc]">
                                <input v-model="row.treshFirstQty" type="number" class="w-full text-center border-0" disabled/>
                            </td>
                            <td class="p-1 text-center border-2 border-[#7393dc]">
                                <input v-model="row.treshSecondQty" type="number" class="w-full text-center border-0" disabled/>
                            </td>
                            <td class="p-1 text-center border-2 border-[#7393dc]">
                                <input v-model="row.stockAvailable" type="number" class="w-full text-center border-0" disabled/>
                            </td>
                            <td class="p-1 text-center border-2 border-[#7393dc]">
                                <input v-model="row.remainingQty" type="number" class="w-full text-center border-0" disabled/>
                            </td>
                            <td class="p-1 text-center border-2 border-[#7393dc]">
                                <input v-model="row.requestedQty" @input="requestedItemQtyDebounced(row)" type="number" class="w-full rounded-md input-red-when-disabled" :disabled="row.stockAvailable == 0 || row.remainingQty == 0"/>
                            </td>
                            <td class="p-1 text-center border-2 border-[#7393dc]">
                                <button @click="deleteRow(index)" class="px-2 py-1 rounded">
                                    <svg class="w-6 h-6 text-rose-500 hover:text-rose-900 transition duration-75" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 1216 1312">
                                        <path fill="currentColor" d="M1202 1066q0 40-28 68l-136 136q-28 28-68 28t-68-28L608 976l-294 294q-28 28-68 28t-68-28L42 1134q-28-28-28-68t28-68l294-294L42 410q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 294l294-294q28-28 68-28t68 28l136 136q28 28 28 68t-28 68L880 704l294 294q28 28 28 68"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex justify-center my-3">
                    <button
                        @click="addRow"
                        class="inline-flex w-1/6 items-center justify-center rounded cursor-pointer bg-[#1a966c] px-4 py-2 font-semibold text-white transition [box-shadow:rgb(179,_254,232)-8px_8px] hover:[box-shadow:rgb(179,_254,232)0px_0px]">
                        <svg class="w-6 h-6 text-gray-50 transition duration-75" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14">
                            <path fill="currentColor" fill-rule="evenodd" d="M8 1a1 1 0 0 0-2 0v5H1a1 1 0 0 0 0 2h5v5a1 1 0 1 0 2 0V8h5a1 1 0 1 0 0-2H8z" clip-rule="evenodd"/>
                        </svg>
                        <span class="ml-2">Add Row</span>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="requestData?.length > 0" class="w-full my-4 p-4 bg-white shadow rounded-md mb-8">
            <form @submit.prevent="submit" class="mx-auto mt-10">
                <div class="grid grid-cols-2 gap-6">
                    <div class="relative z-0 w-full mb-5 group">
                        <input v-model="create.risNo" type="text" name="risNo" id="risNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                        <label for="risNo" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">RIS Number</label>
                    </div>
                    <div class="relative z-0 w-full mb-5 group">
                        <input v-model="create.receivedBy" type="text" name="receivedBy" id="receivedBy" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                        <label for="receivedBy" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Issued To (Name of the Person)</label>
                    </div>
                    <div v-if="searchOfficePpmp.officeId == 'others'" class="relative z-0 w-full mb-5 group">
                        <input v-model="create.remarks" type="text" name="remarks" id="remarks" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                        <label for="remarks" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Remarks/Description</label>
                    </div>
                    <div class="relative z-0 w-full mb-5 group">
                        <input v-model="create.risDate" type="date" name="risDate" id="risDate" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                        <label for="risDate" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Date of Issuance</label>
                    </div>
                </div>
                <div class="my-10 px-5">
                    <div class="flex justify-center">
                        <button type="submit" class="w-1/2 inline-flex items-center justify-center rounded cursor-pointer bg-[#6427f1] px-6 py-3 font-semibold text-white transition [box-shadow:rgb(171,_196,245)-8px_8px] hover:[box-shadow:rgb(171,_196,_245)0px_0px]">
                            <span class="mr-2">Submit Form</span>
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 15 16">
                                <path fill="currentColor" d="M12.49 7.14L3.44 2.27c-.76-.41-1.64.3-1.4 1.13l1.24 4.34c.05.18.05.36 0 .54l-1.24 4.34c-.24.83.64 1.54 1.4 1.13l9.05-4.87a.98.98 0 0 0 0-1.72Z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <Modal :show="modalState" @close="closeModal">
            <div class="bg-gray-100 h-auto">
                <div class="bg-white p-6  md:mx-auto">
                    <svg class="text-indigo-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12 2c-.791 0-1.55.314-2.11.874l-.893.893a.985.985 0 0 1-.696.288H7.04A2.984 2.984 0 0 0 4.055 7.04v1.262a.986.986 0 0 1-.288.696l-.893.893a2.984 2.984 0 0 0 0 4.22l.893.893a.985.985 0 0 1 .288.696v1.262a2.984 2.984 0 0 0 2.984 2.984h1.262c.261 0 .512.104.696.288l.893.893a2.984 2.984 0 0 0 4.22 0l.893-.893a.985.985 0 0 1 .696-.288h1.262a2.984 2.984 0 0 0 2.984-2.984V15.7c0-.261.104-.512.288-.696l.893-.893a2.984 2.984 0 0 0 0-4.22l-.893-.893a.985.985 0 0 1-.288-.696V7.04a2.984 2.984 0 0 0-2.984-2.984h-1.262a.985.985 0 0 1-.696-.288l-.893-.893A2.984 2.984 0 0 0 12 2Zm3.683 7.73a1 1 0 1 0-1.414-1.413l-4.253 4.253-1.277-1.277a1 1 0 0 0-1.415 1.414l1.985 1.984a1 1 0 0 0 1.414 0l4.96-4.96Z" clip-rule="evenodd"/>
                    </svg>

                    <div class="text-center">
                        <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Confirm Issuance!</h3>
                        <p class="text-gray-600 my-2">Please confirm if you wish to proceed.</p>
                        <p> This action can't be undone upon confirmation.</p>
                        <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
                            <SuccessButton @click="confirmSubmit">
                                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                                Confirm 
                            </SuccessButton>

                            <DangerButton @click="closeModal"> 
                                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                                Cancel
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
<style scoped>
.input-red-when-disabled:disabled {
  background-color: #ecc5c5;
}
</style>