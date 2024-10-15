<script setup>
    import { Head, router, usePage } from '@inertiajs/vue3';
    import { reactive, ref, watch, computed } from 'vue';
    import { debounce } from 'lodash';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Pagination from '@/Components/Pagination.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';

    const props = defineProps({
        ppmp: Object,
        ppmpParticulars: Object,
        user: Number,
    });

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isAddPPModalOpen = computed(() => modalState.value === 'add');
    const isEditPPModalOpen = computed(() => modalState.value === 'edit');
    const isDropPPModalOpen = computed(() => modalState.value === 'drop');
    
    const addParticular  = reactive({
        transId: props.ppmp.id,
        prodCode: '',
        firstQty: '',
        secondQty: '',
        user: props.user,
    });

    const editParticular = reactive({
        partId: '',
        prodCode: '',
        prodDesc: '',
        firstQty: '',
        secondQty: '',
        user: props.user,
    });

    const dropParticular = reactive({
        pId: '',
        user: props.user,
    });

    const openEditPpmpModal = (particular) => {
        editParticular.partId = particular.id;
        editParticular.prodCode = particular.prodCode;
        editParticular.prodDesc = particular.prodName;
        editParticular.firstQty = particular.firstQty;
        editParticular.secondQty = particular.secondQty;
        modalState.value = 'edit';
    }

    const openDropPpmpModal = (particular) => {
        dropParticular.pId = particular.id;
        modalState.value = 'drop';
    }

    const submitForm = (action, url, data) => {
        let method;

        switch (action) {
            case 'post':
                method = 'post';
                break;
            case 'put':
                method = 'put';
                break;
            case 'delete':
                method = 'delete';
                break;
            default:
                throw new Error('Invalid action specified');
        }

        Inertia[method](url, data, {
            onSuccess: () => closeModal(),
        });
    };

    const submitAdd = () => submitForm('post', 'create', addParticular);
    const submitEdit = () => submitForm('put', 'edit', editParticular);
    const submitDrop = () => {
        const ppmpParticular = dropParticular.pId;
        submitForm('delete', `delete/${ppmpParticular}`, null)
    };
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="text-green-700" aria-current="page">Project Procurement and Manangement Plan</li> 
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
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="mx-2 w-full md:w-3/12 bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                            <h2 class="text-lg font-semibold text-[#07074D] mb-4">PPMP Information</h2>

                            <div class="mb-4">
                                <label for="officeName" class="block text-sm font-medium text-[#07074D] mb-1">Requestee:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.requestee.office_name }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="ppmpCode" class="block text-sm font-medium text-[#07074D] mb-1">No.:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.ppmp_code }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="ppmpType" class="block text-sm font-medium text-[#07074D] mb-1">Type:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.ppmp_type }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="priceAdjustment" class="block text-sm font-medium text-[#07074D] mb-1">Adjusted Price:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.price_adjustment }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="ppmpRemarks" class="block text-sm font-medium text-[#07074D] mb-1">PPMP for CY:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.ppmp_remarks }}</p>
                            </div>       
                            
                            <div class="mb-4">
                                <label for="totalItems" class="block text-sm font-medium text-[#07074D] mb-1">Total Items Listed:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.totalItems }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="totalAmount" class="block text-sm font-medium text-[#07074D] mb-1">Total Amount:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.formattedOverallPrice }}</p>
                            </div>
                        </div>

                        <div class="mx-2 w-full md:w-9/12 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between pb-4">
                                        <AddButton @click="showModal('add')">
                                                <span class="mr-2">Add Particular</span>
                                        </AddButton>
                                        <label for="search" class="sr-only">Search</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 rtl:inset-r-0 rtl:right-0 flex items-center ps-3 pointer-events-none">
                                                <svg class="w-5 h-5 text-indigo-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                            </div>
                                            <input type="text" id="search" class="block p-2 ps-10 text-sm text-gray-900 border border-indigo-600 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 " placeholder="Search for Item Names">
                                        </div>
                                    </div>
                                    <table class="w-full text-left rtl:text-right text-gray-900">
                                        <thead class="text-sm text-center text-gray-100 uppercase bg-indigo-600">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 w-1/12">
                                                    No#
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12">
                                                    Stock No.
                                                </th>
                                                <th scope="col" class="xl:block hidden px-6 py-3 w-4/12">
                                                    Description
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12">
                                                    Jan (Qty)
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12">
                                                    May (Qty)
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12">
                                                    Unit
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12">
                                                    Price
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/12">
                                                    Action/s
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(particular, index) in ppmpParticulars" :key="particular.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                                <td scope="row" class="py-2 text-center">
                                                    {{  ++index }}
                                                </td>
                                                <td scope="row" class="py-2 text-center">
                                                    {{  particular.prodCode }}
                                                </td>
                                                <td class="xl:block hidden py-2">
                                                    {{ particular.prodName }}
                                                </td>
                                                <td scope="row" class="py-2 text-center">
                                                    {{  particular.firstQty }}
                                                </td>
                                                <td scope="row" class="py-2 text-center">
                                                    {{ particular.secondQty }}
                                                </td>
                                                <td scope="row" class="py-2 text-center">
                                                    {{ particular.prodUnit }}
                                                </td>
                                                <td scope="row" class="py-2 text-right">
                                                    {{ particular.prodPrice }}
                                                </td>
                                                <td scope="row" class="py-2 text-center">
                                                    <EditButton @click="openEditPpmpModal(particular)" tooltip="Edit"/>
                                                    <RemoveButton @click="openDropPpmpModal(particular)" tooltip="Remove"/>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- <div class="flex justify-center p-5">
                                    <Pagination :links="officePpmps.links" />
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="isAddPPModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitAdd">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Additional Particular</h3>
                            <p class="text-sm text-gray-500"> Enter the details for the add Product/Particular you wish to add.</p>
                            <div class="mt-3">
                                <p class="text-sm text-gray-500"> Product No: </p>
                                <input v-model="addParticular.prodCode" type="text" id="prodCode" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 01-01-01" required>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-gray-500"> Quantity: </p>
                                <input v-model="addParticular.firstQty" type="number" id="firstQty" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="First Semester" required>
                                <input v-model="addParticular.secondQty" type="number" id="secondQty" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Second Semester">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <SuccessButton>
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
            </form>
        </Modal>
        <Modal :show="isEditPPModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitEdit">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Update Quantity</h3>
                            <p class="text-sm text-gray-500"> Enter the quantity you want to update on the input field.</p>
                            <div class="mt-3">
                                <p class="text-sm text-gray-500"> Product Information: </p>
                                <input v-model="editParticular.prodCode" type="text" id="prodCode" class="mt-2 p-2 bg-gray-100 border border-gray-100 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 01-01-01" readonly>
                                
                                <textarea v-model="editParticular.prodDesc" type="text" id="prodCode" class="mt-2 p-2 bg-gray-100 border border-gray-100 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 01-01-01" readonly></textarea>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-gray-500"> Quantity: </p>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">1st Qty: </span>
                                    </div>
                                    <input v-model="editParticular.firstQty" type="number" id="firstQty" class="mt-2 pl-16 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="First Semester" required>
                                </div>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">2nd Qty: </span>
                                    </div>
                                    <input v-model="editParticular.secondQty" type="number" id="secondQty" class="mt-2 pl-16 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Second Semester">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <SuccessButton>
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
            </form>
        </Modal>
        <Modal :show="isDropPPModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDrop">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move to Trash!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remove the selected Product from the list. This action can't be undone.</p>
                            <p> Please confirm if you wish to proceed.  </p>
                            <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
                                <SuccessButton>
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
            </form>
        </Modal>
    </AuthenticatedLayout>
    </div>
</template>
 