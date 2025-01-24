<script setup>
    import { reactive, ref, watch, onMounted } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import axios from 'axios';

    const props = defineProps({
        products: Object,
        office: Object
    });

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
    const requestData = ref([]);
    const file = ref([]);
    const fileInput = ref(null);
    const modalState = ref(false);
    const closeModal = () => { modalState.value = false; }
    const showModal = () => { modalState.value = true; }

    const fetchData = () => {
        if (risNo.value.length > 0) {
            const foundStock = props.products.find(item => item.prod_newNo === risNo.value);
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
    };

    watch(() => stockData.value?.qty, (newQty) => {});

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

    const create = reactive({
        risNo: '',
        officeId: '',
        receivedBy: '',
    });

    const submit = () => {
        showModal();
    };

    const confirmSubmit = () => {
        const formData = new FormData();
            formData.append('risNo', create.risNo);
            formData.append('officeId', create.officeId);
            formData.append('receivedBy', create.receivedBy);
            formData.append('requestProducts', JSON.stringify(requestData.value));
            formData.append('file', file.value);

        router.post('ris/store', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        closeModal();
    };
</script>

<template>
    <Head title="Dashboard" />
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2 leading-tight">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Requisition and Issuance</a></li>
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-indigo-400 my-2 italic">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-gray-400 my-2 italic">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <section class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="mx-2 w-full md:w-3/12 bg-white p-4 rounded-md shadow">
                    <p class="mb-1 block text-base font-medium text-[#86591e]">PPMP Information</p>
                    <div class="relative z-0 w-full mb-5 group">
                        <select v-model="searchOfficePpmp.officeId" @change="fetchOfficePpmp" name="officeId" id="officeId" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                            <option value="" disabled selected class="pl-5">Select Office/End-User</option>
                            <option v-for="user in office" :key="user.id" :value="user.id" class="ml-5">{{ user.office_code }}</option>
                        </select>
                        <label for="officeId" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office/End-User</label>
                    </div>
                    <div v-if="searchOfficePpmp.officeId" @change="fetchOfficePpmp" class="relative z-0 w-full my-3 group">
                        <select v-model="searchOfficePpmp.year" name="ppmpYear" id="ppmpYear" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                            <option value="" disabled selected>Select year</option>
                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                        </select>
                        <label for="ppmpYear" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Calendar Year</label>
                    </div>
                </div>
                <!-- <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="py-2">
                        <div class="relative isolate flex overflow-hidden bg-indigo-600 px-6 py-2.5 ">
                            <div class="flex flex-wrap">
                                <p class="text-base text-gray-100">
                                    <strong class="font-semibold">Requested Product Information</strong>
                                    <svg viewBox="0 0 2 2" class="mx-2 inline size-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                                    <span class="text-sm">Please enter the required data in the input field.</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-col lg:flex-row px-4">
                            <div class="w-full lg:w-1/3">
                                <div class="relative z-0 w-full my-5 group">
                                    <input v-model="risNo" @input="fetchData" type="text" name="stockNo" id="stockNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                    <label for="stockNo" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Stock Number</label>
                                </div>
                                <div v-if="stockData" class="mt-2 px-4">
                                    <h3 class="text-base text-gray-700 font-semibold">Product Details:</h3>
                                    <div class="px-5 mb-2">
                                        <p class="text-base text-gray-500">
                                            <strong class="font-semibold">Stock No:</strong> 
                                            {{ stockData.prod_newNo }}
                                        </p>
                                        <p class="text-base text-gray-500">
                                            <strong class="font-semibold">Description:</strong> 
                                            {{ stockData.prod_desc }}
                                        </p>
                                        <p class="text-base text-gray-500">
                                            <strong class="font-semibold">Unit of Measure:</strong> 
                                            {{ stockData.prod_unit }}
                                        </p>                                

                                        <label for="quantity"><strong class="text-base text-gray-500 font-semibold">Quantity: </strong></label>
                                        <input 
                                            type="number" 
                                            id="quantity" 
                                            v-model="stockData.qty" 
                                            min="0"
                                            class="border-0 border-b-2 text-center focus:outline-none focus:ring-0 focus:border-indigo-600"
                                        />
                                    </div>
                                    <AddButton @click="addToRequestData">Add Product</AddButton>
                                </div>
                            </div>

                            <div v-if="requestData.length > 0" class="w-full lg:w-2/3 px-5 pt-2 relative overflow-x-auto">
                                <table class="w-full text-sm text-left rtl:text-right text-gray-700">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-300">
                                        <tr>
                                            <th class="px-6 py-3 w-1/12 text-center">Action</th>
                                            <th class="px-6 py-3 w-1/12 text-center">Stock No.</th>
                                            <th class="px-6 py-3 w-8/12 text-center">Description</th>
                                            <th class="px-6 py-3 w-1/12 text-center">Unit</th>
                                            <th class="px-6 py-3 w-1/12 text-center">Requested Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item,index) in requestData" :key=index class="odd:bg-white even:bg-gray-100 border-b">
                                            <td scope="col" class="py-3 text-center">
                                                <button @click="removeItem(index)">
                                                    <svg class="w-6 h-6 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm5.757-1a1 1 0 1 0 0 2h8.486a1 1 0 1 0 0-2H7.757Z" clip-rule="evenodd"/>
                                                    </svg>
                                                </button>
                                            </td>
                                            <td scope="col" class="py-3 text-center">{{ item.prod_newNo }}</td>
                                            <td scope="col" class="py-3">{{ item.prod_desc }}</td>
                                            <td scope="col" class="py-3 text-center">{{ item.prod_unit }}</td>
                                            <td scope="col" class="py-3 text-center">{{ item.qty }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-if="requestData.length > 0">
                            <div class="relative isolate flex overflow-hidden bg-indigo-600 px-6 py-2.5 mt-5">
                                <div class="flex flex-wrap">
                                    <p class="text-base text-gray-100">
                                        <strong class="font-semibold">Requisition and Issuance Slip Information</strong>
                                        <svg viewBox="0 0 2 2" class="mx-2 inline size-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                                        <span class="text-sm">Please enter the required data in the input field.</span>
                                    </p>
                                </div>
                            </div>
                            <form @submit.prevent="submit" class="mx-auto mt-10">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="px-5">
                                        <div class="grid lg:grid-cols-2 lg:gap-6">
                                            <div class="relative z-0 w-full mb-5 group">
                                                <input v-model="create.risNo" type="text" name="risNo" id="risNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                                <label for="risNo" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">RIS Number</label>
                                            </div>
                                            <div class="relative z-0 w-full mb-5 group">
                                                <select v-model="create.officeId" name="officeId" id="officeId" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                                    <option value="" disabled selected class="pl-5">Select Office/End-User</option>
                                                    <option v-for="user in office" :key="user.id" :value="user.id" class="ml-5">{{ user.office_code }}</option>
                                                </select>
                                                <label for="officeId" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office/End-User</label>
                                            </div>
                                        </div>
                                        <div class="relative z-0 w-full mb-5 group">
                                            <input v-model="create.receivedBy" type="text" name="receivedBy" id="receivedBy" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                            <label for="receivedBy" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Issued To</label>
                                        </div>
                                    </div>
                                    <div class="px-5">
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
                                    </div>
                                </div>       

                                <div class="my-10 px-5">
                                    <button
                                        class="hover:shadow-form w-1/3 rounded-md bg-indigo-900 hover:bg-indigo-500 py-3 text-center text-base font-semibold text-white outline-none">
                                        Submit Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div> -->
            </div>
        </section>
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
