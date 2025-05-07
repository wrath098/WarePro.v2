<script setup>
    import { reactive, ref, watch, onMounted, computed } from 'vue';
    import { Head, router, useForm, usePage } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import axios from 'axios';
    import Swal from 'sweetalert2';

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
            } catch (error) {
                console.error('Error fetching office data:', error);
            }
        }
    };

    const risNo = ref('');
    const stockData = ref(null);
    watch(risNo, (newRisNo) => {
        fetchData(newRisNo);
    });
    const fetchData = (newRisNo) => {
        if (newRisNo > 0 ) {
            const foundStock = officePpmpParticulars.value.data.find(item => item.id === newRisNo);
            if (foundStock) {
            stockData.value = {
                ...foundStock,
                qty: foundStock.quantity || 0
            };
            } else {
            stockData.value = null;
            }
        } else {
            stockData.value = null;
        }
    }

    watch(() => stockData.value?.qty, (newQty) => {});
    const requestData = ref([]);
    const addToRequestData = () => {
        if (stockData.value && stockData.value.qty > 0 ) {
            requestData.value.push({
                ...stockData.value,
                qty: stockData.value.qty
            });
            stockData.value = null;
        } else {
            alert("Please enter a valid quantity.");
        }
    };

    const removeItem = (index) => {
        requestData.value.splice(index, 1);
    };

    
    const file = ref([]);
    const fileInput = ref(null); 
    const onFileChange = (event) => {
        const selectedFile = event.target.files[0];
        if (selectedFile) {
            file.value = selectedFile;
        }
    };

    const onDrop = (event) => {
        event.preventDefault();
        const droppedFile = event.dataTransfer.files[0];
        if (droppedFile) {
            file.value = droppedFile;
        }
    };

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
        create.file = file.value;
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
                            <select v-model="searchOfficePpmp.officeId" @change="fetchOfficePpmp" name="officeId" id="officeId" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" :disabled="requestData.length > 0" required>
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
                    <div v-if="officePpmpParticulars.data" class="relative z-0 w-full group ">
                        <select v-model="risNo" name="requestedItem" id="requestedItem"  class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                            <option value="" disabled selected>Available Requested Items</option>
                            <option v-for="particular in officePpmpParticulars.data" :key="particular.id" :value="particular.id">{{ particular.prodDesc }}</option>
                        </select>
                        <label for="requestedItem" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Select Product Item</label>
                    </div>
                    
                    <div v-if="stockData && stockData.availableStock > 0" class="mt-5">
                        <div class="grid gap-6 grid-cols-1 lg:grid-cols-2">
                            <div class="flow-root py-3 border-2 border-blue-400 ">
                                <dl class="-my-3 divide-gray-100 text-base">
                                    <div class="grid grid-cols-1 gap-1 p-3 bg-blue-100 sm:grid-cols-3 sm:gap-4 border-b-2 border-blue-400">
                                        <dt class="font-bold text-blue-900 sm:col-span-2">Product Information</dt>
                                    </div>
                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-200/90 sm:grid-cols-3 sm:gap-4">
                                        <dt class="font-bold text-gray-900 sm:col-span-2">Stock No#</dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ stockData.prodStockNo }}</dd>
                                    </div>
                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-200/90 sm:grid-cols-3 sm:gap-4">
                                        <dt class="font-bold text-gray-900 sm:col-span-2">Unit of Measurement</dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ stockData.prodUnit }}</dd>
                                    </div>
                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-200/90 sm:grid-cols-3 sm:gap-4">
                                        <dt class="font-bold text-gray-900 sm:col-span-2">Stock Available in Warehouse</dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ stockData.availableStock }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="flow-root py-3 border-2 border-blue-400 ">
                                <dl class="-my-3 divide-gray-100 text-base">
                                    <div class="grid grid-cols-1 gap-1 p-3 bg-blue-100 sm:grid-cols-3 sm:gap-4 border-b-2 border-blue-400">
                                        <dt class="font-bold text-blue-900 sm:col-span-2">Office Maximum Allowed Quantity</dt>
                                    </div>
                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-200/90 sm:grid-cols-3 sm:gap-4">
                                        <dt class="font-bold text-gray-900 sm:col-span-2">1st Semester (Qty)</dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ stockData.treshFirstQty }}</dd>
                                    </div>
                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-200/90 sm:grid-cols-3 sm:gap-4">
                                        <dt class="font-bold text-gray-900 sm:col-span-2">2nd Semester (Qty)</dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ stockData.treshSecondQty }}</dd>
                                    </div>
                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-200/90 sm:grid-cols-3 sm:gap-4">
                                        <dt class="font-bold text-gray-900 sm:col-span-2">Remaining Quantity to be Released</dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ stockData.remainingQty }}</dd>
                                    </div>
                                </dl>
                            </div> 
                        </div>
                        <div class="mb-2 grid lg:grid-cols-2 lg:gap-6 my-10">
                            <div>
                                <label for="quantity"><strong class="text-base text-gray-700 font-semibold">Enter Requested Quantity: </strong> <span class="text-rose-600">*</span></label>
                                <input 
                                    type="number" 
                                    id="quantity" 
                                    v-model="stockData.qty" 
                                    min="0"
                                    class="border-0 border-b-2 text-center focus:outline-none focus:ring-0 focus:border-indigo-600 w-64"
                                />
                            </div>
                            <button
                                v-if="stockData.availableStock > 0 && stockData.remainingQty != 0" @click="addToRequestData"
                                class="inline-flex w-full items-center justify-center rounded cursor-pointer bg-[#1a966c] px-6 py-3 font-semibold text-white transition [box-shadow:rgb(179,_254,232)-8px_8px] hover:[box-shadow:rgb(179,_254,232)0px_0px]">
                                <span class="mr-2">Add To Cart</span>
                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="m3.72 2.788l.55 1.862h14.654c1.84 0 3.245 1.717 2.715 3.51l-1.655 5.6c-.352 1.194-1.471 1.99-2.715 1.99H8.113c-1.244 0-2.362-.796-2.715-1.99L2.281 3.213a.75.75 0 1 1 1.438-.425m11.372 6.151a.75.75 0 0 0-1.216-.878l-1.713 2.371l-.599-.684a.75.75 0 1 0-1.128.988l1.034 1.181a.974.974 0 0 0 1.522-.07zM8.5 17.25a2.25 2.25 0 1 0 0 4.5a2.25 2.25 0 0 0 0-4.5m8 0a2.25 2.25 0 1 0 0 4.5a2.25 2.25 0 0 0 0-4.5"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div v-else-if="stockData && stockData.availableStock == 0" class="mt-5">
                        <div class="w-full mx-auto bg-indigo-600 shadow-lg rounded-lg">
                            <div class="px-6 py-5">
                                <div class="flex items-center">
                                    <svg class="fill-current flex-shrink-0 mr-5" width="30" height="30" viewBox="0 0 30 30">
                                        <path class="text-indigo-300" d="m16 14.883 14-7L14.447.106a1 1 0 0 0-.895 0L0 6.883l16 8Z" />
                                        <path class="text-indigo-200" d="M16 14.619v15l13.447-6.724A.998.998 0 0 0 30 22V7.619l-14 7Z" />
                                        <path class="text-indigo-500" d="m16 14.619-16-8V21c0 .379.214.725.553.895L16 29.619v-15Z" />
                                    </svg>
                                    <div class="flex-grow truncate">
                                        <div class="w-full sm:flex justify-between items-center mb-3">
                                            <h2 class="text-2xl leading-snug font-extrabold text-gray-50 truncate mb-1 sm:mb-0">Out Of Stock</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

            </div>
        </div>

        <div v-if="requestData.length > 0" class="w-full my-4 bg-white shadow rounded-md">
            <div class="p-4 flex justify-between rounded-t-md">
                <div class="flex flex-wrap">
                    <p class="text-lg text-indigo-700">
                        <strong class="font-semibold">List of Products Item on Cart</strong>
                    </p>
                </div>
            </div>
            <div class="w-full p-4 relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-[#03244d] border-2 border-[#7393dc] rounded-md">
                    <thead class="text-[#03244d] uppercase bg-[#d8d8f6] ">
                        <tr class="rounded-md">
                            <th class="px-6 py-3 w-1/12 text-center border-2 border-[#7393dc]">Action</th>
                            <th class="px-6 py-3 w-1/12 text-center border-2 border-[#7393dc]">Stock No.</th>
                            <th class="px-6 py-3 w-8/12 text-center border-2 border-[#7393dc]">Product Item Description</th>
                            <th class="px-6 py-3 w-1/12 text-center border-2 border-[#7393dc]">Unit</th>
                            <th class="px-6 py-3 w-1/12 text-center border-2 border-[#7393dc]">Requested Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item,index) in requestData" :key=index class="odd:bg-white even:bg-gray-100 border-b">
                            <td scope="col" class="py-3 text-center border-2 border-[#7393dc]">
                                <button @click="removeItem(index)">
                                    <svg class="w-6 h-6 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm5.757-1a1 1 0 1 0 0 2h8.486a1 1 0 1 0 0-2H7.757Z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </td>
                            <td scope="col" class="py-3 text-center border-2 border-[#7393dc]">{{ item.prodStockNo }}</td>
                            <td scope="col" class="py-3 border-2 border-[#7393dc]">{{ item.prodDesc }}</td>
                            <td scope="col" class="py-3 text-center border-2 border-[#7393dc]">{{ item.prodUnit }}</td>
                            <td scope="col" class="py-3 text-center border-2 border-[#7393dc]">{{ item.qty }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="requestData.length > 0" class="w-full my-4 p-4 bg-white shadow rounded-md mb-8">
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
                    <!-- UPLOAD FILES -->
                    <!-- <div class="px-5">
                        <div class="mb-8 border-2 border-dashed border-slate-400 hover:border-slate-600 bg-gray-100 hover:bg-gray-200" @dragover.prevent @drop="onDrop">
                            <input type="file" ref="fileInput" @change="onFileChange" multiple name="files[]" id="file" class="sr-only" accept=".pdf"/>
                            <label for="file" class="relative flex min-h-[200px] items-center justify-center rounded-md border border-dashed border-[#e0e0e0] p-12 text-center cursor-pointer">
                                <div class="cursor-pointer">
                                    <span class="mb-2 block text-base font-semibold text-[#545557]">
                                        Drop a PDF file
                                    </span>
                                    <span class="mb-2 block text-base font-medium text-[#6B7280]">
                                        Or
                                    </span>
                                    <span class="inline-flex rounded border-2 border-dashed border-slate-400 hover:border-slate-600 bg-gray-100 text-gray-400 hover:bg-gray-100 hover:text-[#1f2024] py-2 px-7 text-base font-medium">
                                        Browse
                                    </span>
                                </div>
                            </label>
                        </div>

                        <div v-if="file">
                            <h4 class="text-lg font-semibold">Selected Files:</h4>
                            <ul class="mt-2">
                                <li class="text-[#07074D]">
                                    {{ file.name }}
                                </li>
                            </ul>
                        </div>
                    </div> -->
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
