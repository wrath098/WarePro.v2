<script setup>
    import { Head, router, useForm, usePage } from '@inertiajs/vue3';
    import { ref, computed, onMounted, reactive } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';
import InputError from '@/Components/InputError.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const isLoading = ref(false);
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    const props = defineProps({
        products: Object,
        list: Object,
        authUserId: Number,
    });

    const exemptedProducts = ref([]);
    onMounted(() => {
      exemptedProducts.value = Object.values(props.products);
    });

    const stockNo = ref('');
    const stockData = ref(null);
    const createForm = useForm({});

    const fetchData = () => {
        if (stockNo.value.length > 0) {
            const foundStock = props.list.find(item => item.code === stockNo.value);
            if (foundStock) {
            stockData.value = {
                ...foundStock,
            };
            } else {
            stockData.value = null;
            }
        } else {
            stockData.value = null;
        }
    };

    const create = reactive({
        prod: stockData,
        ppmpYear: '',
    });

    const edit = useForm({
        prodId: '',
    });

    const openDropUnmodifiedModal = (product) => {
        edit.prodId = product.id;
        modalState.value = 'drop';
    };

    const years = generateYears();
    const modalState = ref(null);

    const isAddUnmodifiedModalOpen = computed(() => modalState.value === 'add');
    const isDropUnmodifiedModalOpen = computed(() => modalState.value === 'drop');
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }

    const submitAddUnmodified = async () => {
        createForm.post(route('store.unmodified.product', { param: create }), {
            preserveScroll: false,
            onFinish: () => {
                isLoading.value = false;
            },
            onSuccess: () => {
                if (errMessage.value) {
                    Swal.fire({
                        title: 'Failed',
                        text: errMessage.value,
                        icon: 'error',
                    });
                } else {
                    createForm.reset();
                    Swal.fire({
                        title: 'Success',
                        text: message.value,
                        icon: 'success',
                    }).then(() => {
                        closeModal();
                        router.visit(route('product.unmodified.list'), {
                            preserveScroll: true,
                            preserveState: false,
                        });
                    });
                }
            },
            onError: (errors) => {
                isLoading.value = false;
                console.log('Error: ' + JSON.stringify(errors));
            },
        });
    };

    const submitDropUnmodify = async () => {
        edit.put(route('deactivate.unmodified.product'), {
            preserveScroll: false,
            onSuccess: () => {
                Swal.fire({
                    title: 'Success',
                    text: message.value,
                    icon: 'success',
                    confirmButtonText: 'OK',
                }).then(() => {
                    closeModal();

                    router.visit(route('product.unmodified.list'), {
                        preserveScroll: true,
                        preserveState: false,
                    });
                });
            },
            onError: (errors) => {
                isLoading.value = false;
            }
        });
    };

    function generateYears() {
        const currentYear = new Date().getFullYear() + 2;
        return Array.from({ length: 3 }, (_, i) => currentYear - i);
    }

    const columns = [
        {
            data: 'code',
            title: 'Stock No#',
            width: '10%',
        },
        // {
        //     data: 'oldCode',
        //     title: 'Old Stock No#',
        //     width: '10%',
        // },
        {
            data: 'desc',
            title: 'Description',
            width: '60%',
        },
        {
            data: 'unit',
            title: 'Unit Of Measurement',
            width: '10%',
        },
        {
            data: 'year',
            title: 'PPMP Year',
            width: '10%',
        },
        {
            data: null,
            title: 'Action',
            width: '10%',
            render: '#action',
        },
    ];
</script>

<template>
    <Head title="Products" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center" aria-current="page">
                        <a :href="route('product.display.active')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Products
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('product.unmodified.list')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Fixed Quantity (Unadjusted on APP)
                            </a>
                        </div>
                    </li>
                </ol>
                <ol v-if="hasPermission('create-product-exemption') ||  hasAnyRole(['Developer'])">
                    <li class="flex flex-col lg:flex-row">
                        <AddButton @click="showModal('add')" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">Add Item</span>
                        </AddButton>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="my-4 w-screen-2xl bg-zinc-300 shadow rounded-md mb-8">
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="columns"
                        :data="exemptedProducts"
                        :options="{  paging: true,
                            searching: true,
                            ordering: false
                        }" >
                            <template #action="props">
                                <RemoveButton v-if="hasPermission('delete-product-exemption') ||  hasAnyRole(['Developer'])" @click="openDropUnmodifiedModal(props.cellData)" tooltip="Trash"/>
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
        <Modal :show="isAddUnmodifiedModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitAddUnmodified">
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline">Add New Product to be Unmodified</h3>
                            <div class="mt-2">
                                <p class="text-sm text-zinc-700"> Enter the details of the Product you wish to add on unmodified.</p>
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037]"> Product Information</p>
                                    <div class="relative z-0 w-full group my-2">
                                        <input v-model="stockNo" @input="fetchData" type="text" name="stockNo" id="stockNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " required />
                                        <label for="stockNo" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Stock Number</label>
                                    </div>
                                    <div v-if="stockData">
                                        <div>
                                            <div class="relative z-0 w-full my-5 group">
                                                <textarea type="text" name="prodDesc" id="prodDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " disabled :value="stockData.desc"></textarea>
                                                <label for="prodDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Product Description</label>
                                            </div>
                                            <div class="relative z-0 lg:w-1/2 my-5 group">
                                                <input type="text" name="prodDesc" id="prodDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " disabled :value="stockData.unit"/>
                                                <label for="prodDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit of Measure</label>
                                            </div>                               
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037]"> PPMP Calendar Year</p>
                                    <select v-model="create.ppmpYear" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option disabled value="">Select the Ppmp Year</option>
                                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-zinc-400 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
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
        <Modal :show="isDropUnmodifiedModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDropUnmodify">
                <input type="hidden" v-model="edit.prodId">
                <div class="bg-zinc-300 h-auto">
                    <div class="p-6  md:mx-auto">
                        <svg class="text-rose-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base font-semibold text-[#1a0037] text-center">Move to Trash!</h3>
                            <p class="text-zinc-700 my-2">Confirming this action will remove the selected Product into the list. This action can't be undone.</p>
                            <p> Please confirm if you wish to proceed.  </p>
                            <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
                                <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
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

:deep(div.dt-container select.dt-input) {
    border: 1px solid #03244d;
    margin-left: 1px;
    width: 75px;
}

:deep(div.dt-container .dt-search input) {
    background-color: #fafafa;
    border: 1px solid #03244d;
    margin-right: 1px;
    width: 250px;
}

:deep(div.dt-length > label) {
    display: none;
}

:deep([data-v-55c4f46f] table.dataTable tbody > tr > td:nth-child(3)) {
        text-align: left !important;
}
</style>