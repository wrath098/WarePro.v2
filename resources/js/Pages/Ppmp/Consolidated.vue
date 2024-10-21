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
import PrintButton from '@/Components/Buttons/PrintButton.vue';

    const props = defineProps({
        ppmp: Object,
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
                                <label for="officeName" class="block text-sm font-medium text-[#07074D] mb-1">Version:</label>
                                <p class="text-lg text-gray-800 font-semibold">V.{{ ppmp.ppmp_version }}</p>
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
                                <label for="priceAdjustment" class="block text-sm font-medium text-[#07074D] mb-1">Adjusted Quantity:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.qty_adjustment }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="ppmpRemarks" class="block text-sm font-medium text-[#07074D] mb-1">PPMP for CY:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.ppmp_year }}</p>
                            </div>       
                            
                            <div class="mb-4">
                                <label for="totalItems" class="block text-sm font-medium text-[#07074D] mb-1">Total Items Listed:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.totalItems }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="totalAmount" class="block text-sm font-medium text-[#07074D] mb-1">Total Amount:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.totalAmount }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="totalAmount" class="block text-sm font-medium text-[#07074D] mb-1">Created/Updated By:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.updater.name }}</p>
                            </div>
                        </div>

                        <div class="mx-2 w-full md:w-9/12 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <PrintButton :href="route('generatePdf.ProductActiveList')" target="_blank">
                                        <span class="mr-2">Print List</span>
                                    </PrintButton>
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr class="text-center">
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Stock No.</th>
                                                <th scope="col" class="px-6 py-3 w-5/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Jan (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">May (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(particular, index) in ppmp.transactions" :key="particular.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                                <td class="px-6 py-3">{{ ++index }}</td>
                                                <td class="px-6 py-3">{{ particular.prodCode }}</td>
                                                <td class="px-6 py-3">{{ particular.prodName }}</td>
                                                <td class="px-6 py-3">{{ particular.qtyFirst }}</td>
                                                <td class="px-6 py-3">{{ particular.qtySecond }}</td>
                                                <td class="px-6 py-3">{{ particular.prodUnit }}</td>
                                                <td class="px-6 py-3">{{ particular.prodPrice }}</td>
                                            </tr>
                                        </tbody>
                                    </DataTable>
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
 