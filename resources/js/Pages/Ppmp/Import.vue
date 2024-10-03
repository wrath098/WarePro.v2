<script setup>
    import { Head, router } from '@inertiajs/vue3';
    import { reactive, ref } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';

    const props = defineProps({
        offices: Object,
    });

    const file = ref([]);
    const fileInput = ref(null);

    const create = reactive({
        ppmpType: '',
        basePrice: '',
        ppmpYear: '',
        office: '',
    });

    const onFileChange = (event) => {
        const selectedFile = event.target.files[0];
        if (selectedFile) {
            file.value = selectedFile;
        }
        console.log("Selected files:", selectedFile);
    };

    const onDrop = (event) => {
        event.preventDefault();
        const droppedFile = event.dataTransfer.files[0];
        if (droppedFile) {
            file.value = droppedFile;
        }
        console.log("Dropped files:", droppedFile);
    };

    const onDragOver = (event) => {
        event.preventDefault();
        event.stopPropagation();
    };

    const years = generateYears();
    function generateYears() {
        const currentYear = new Date().getFullYear() + 2;
        return Array.from({ length: 3 }, (_, i) => currentYear - i);
    }

    const submit = () => {
        if (!file.value) {
            alert('Please select a file first!');
            return;
        }

        const formData = new FormData();
            formData.append('ppmpType', create.ppmpType);
            formData.append('basePrice', create.basePrice);
            formData.append('ppmpYear', create.ppmpYear);
            formData.append('office', create.office);
            formData.append('file', file.value);

        router.post('ppmp/create', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
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
                    <div class="flex flex-col md:flex-row items-center justify-center">
                        <div class="mx-2 w-full md:w-2/5 bg-white p-4 rounded-md shadow">
                            <form @submit.prevent="submit" class="space-y-5">
                                <div>
                                    <label for="ppmpType" class="mb-1 block text-base font-medium text-[#07074D]">
                                        PPMP Type:
                                    </label>
                                    <select v-model="create.ppmpType" id="ppmpType" class="pl-3 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500 text-gray-800" required>
                                        <option disabled selected>Please choose PPMP Type</option>
                                        <option value="individual">Individual</option>
                                        <option value="contingency">Contingency</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="basedPrice" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Based Price:
                                        <span class="text-sm text-[#8f9091]">Percentage to apply to the latest price</span>
                                    </label>
                                    <input v-model="create.basePrice" type="number" id="basedPrice" placeholder="Ex. 15 = 15% || 50 = 50%"
                                        class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-4 text-base font-medium text-[#2c2d30] outline-none focus:border-[#6A64F1] focus:shadow-md" />
                                </div>

                                <div>
                                    <label for="ppmpYear" class="mb-1 block text-base font-medium text-[#07074D]">
                                        PPMP for CY:
                                    </label>
                                    <select v-model="create.ppmpYear" id="ppmpYear" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500">
                                        <option disabled selected>Please choose year</option>
                                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="ppmpYear" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Office
                                    </label>
                                    <select v-model="create.office" id="ppmpYear" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500">
                                        <option disabled selected>Please choose year</option>
                                        <option v-for="office in props.offices" :key="office.id" :value="office.id">{{ office.office_name }}</option>
                                    </select>
                                </div>

                                <div class="pt-4">
                                    <label class="mb-5 block text-xl font-semibold text-[#07074D]">
                                        Upload File
                                    </label>

                                    <div class="mb-8 bg-gray-100 hover:bg-gray-300" @dragover.prevent @drop="onDrop">
                                        <input type="file" ref="fileInput" @change="onFileChange" multiple name="files[]" id="file" class="sr-only" accept=".xls,.xlsx"/>
                                        <label for="file" class="relative flex min-h-[200px] items-center justify-center rounded-md border border-dashed border-[#e0e0e0] p-12 text-center cursor-pointer">
                                            <div class="cursor-pointer">
                                                <span class="mb-2 block text-base font-semibold text-[#071a4d]">
                                                    Drop an Excel file here or click to upload
                                                </span>
                                                <span class="mb-2 block text-base font-medium text-[#6B7280]">
                                                    Or
                                                </span>
                                                <span class="inline-flex rounded border bg-blue-600 text-gray-100 hover:bg-gray-100 hover:text-[#071a4d] py-2 px-7 text-base font-medium">
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

                                <div>
                                    <button
                                        class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none">
                                        Create PPMP
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="mx-2 w-full md:w-3/5 bg-white p-4 rounded-md shadow">
                            <p class="text-base">Additional content goes here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 
<style scoped>
.upload-area {
    border: 2px dashed #007BFF;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.3s ease;
}
.upload-area:hover {
    border-color: #08396d;
}
</style>