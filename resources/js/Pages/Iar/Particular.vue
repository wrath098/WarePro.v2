<script setup>
    import { Head } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import { computed, reactive, ref } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';


    const props = defineProps({
        iar: Object,
        particulars: Object,
    });

    const acceptParticular = reactive({pid: ''});

    const openAcceptModal = (particular) => {
        acceptParticular.pid = particular.pId;
        modalState.value = 'accept';
    }

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isAcceptModalOpen = computed(() => modalState.value === 'accept');

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

    const submitAcceptance = () => submitForm('post', 'particulars/accept', acceptParticular);
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg md:leading-6"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-green-700">Inspection and Acceptance</a></li>
                    <li class="after:content-[':'] after:ml-2 text-green-700" aria-current="page">Transactions</li>
                    <li class="text-green-700" aria-current="page">IAR No. {{ iar.sdi_iar_id }} - P.O. No. {{ iar.po_no }}</li> 
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
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3  gap-4 mb-4">
                        <li v-for="particular in particulars" :key="particular.pId" class="gap-4 p-4 justify-center h-auto rounded-lg  bg-gray-100 shadow-md transition-transform transform">
                            <div class="flex-1 flex items-start justify-between bg-gray-50 p-4 rounded-lg">
                                <div class="flex flex-col gap-1">
                                    <p class="text-xl font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Item No. {{ particular.itemNo }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Stock No: </span> {{ particular.stockNo }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium ">Description: </span> {{ particular.specs }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Quantity: </span> {{ particular.quantity }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Unit Cost: </span> {{ particular.unit }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Price: </span> {{ particular.price }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Total Cost: </span> {{ particular.cost }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex md:flex-row justify-end mt-2">
                                <button class="inline-flex items-center px-3 py-2 m-1 text-sm font-medium text-center text-white bg-gray-600 rounded-lg hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300">
                                    Reject
                                    <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                                    </svg>
                                </button>
                                <button @click="openAcceptModal(particular)" class="inline-flex items-center px-3 py-2 m-1 text-sm font-medium text-center text-white bg-indigo-600 rounded-lg hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300">
                                    Receive
                                    <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                                    </svg>
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal :show="isAcceptModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitAcceptance">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-indigo-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2c-.791 0-1.55.314-2.11.874l-.893.893a.985.985 0 0 1-.696.288H7.04A2.984 2.984 0 0 0 4.055 7.04v1.262a.986.986 0 0 1-.288.696l-.893.893a2.984 2.984 0 0 0 0 4.22l.893.893a.985.985 0 0 1 .288.696v1.262a2.984 2.984 0 0 0 2.984 2.984h1.262c.261 0 .512.104.696.288l.893.893a2.984 2.984 0 0 0 4.22 0l.893-.893a.985.985 0 0 1 .696-.288h1.262a2.984 2.984 0 0 0 2.984-2.984V15.7c0-.261.104-.512.288-.696l.893-.893a2.984 2.984 0 0 0 0-4.22l-.893-.893a.985.985 0 0 1-.288-.696V7.04a2.984 2.984 0 0 0-2.984-2.984h-1.262a.985.985 0 0 1-.696-.288l-.893-.893A2.984 2.984 0 0 0 12 2Zm3.683 7.73a1 1 0 1 0-1.414-1.413l-4.253 4.253-1.277-1.277a1 1 0 0 0-1.415 1.414l1.985 1.984a1 1 0 0 0 1.414 0l4.96-4.96Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Accept Product!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will add the selected Product to the Product Inventory. <br> This action can't be undone.</p>
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
    </div>
</template>