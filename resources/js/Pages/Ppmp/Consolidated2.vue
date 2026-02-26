<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
import DangerButton from '@/Components/Buttons/DangerButton.vue';
import Dropdown from '@/Components/Dropdown.vue';
import EditButton from '@/Components/Buttons/EditButton.vue';
import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
import Swal from 'sweetalert2';
import useAuthPermission from '@/Composables/useAuthPermission';
import debounce from 'lodash/debounce';
import axios from 'axios';

const ymToMMYYYY = (ym) => {
    if (!ym) return ''
    const [yyyy, mm] = ym.split('-')
    if (!yyyy || !mm) return ''
    return `${mm}/${yyyy}`
}

const mmYYYYToYM = (value) => {
    if (!value) return ''
    const [mm, yyyy] = value.split('/')
    if (!mm || !yyyy) return ''
    return `${yyyy}-${mm.padStart(2, '0')}`
}

const bulkForm = ref({
    procurement_mode: '',
    ppc: '',
    start_pa: '',
    end_pa: '',
    expected_delivery: '',
    supporting_doc: '',
    remarks: ''
});

const isBulkModalOpen = computed(() => modalState.value === 'bulk')

const openBulkModal = () => {
    if (!selectedRows.value.length) {
        Swal.fire('Warning', 'Please select at least one row.', 'warning')
        return
    }

    bulkForm.value = {
        procurement_mode: '',
        start_pa: ''
    }

    modalState.value = 'bulk'
}

const applyBulkUpdate = async () => {
    const hasValue = Object.values(bulkForm.value).some(
        val => val !== '' && val !== null && val !== undefined
    );

    if (!hasValue) {
        Swal.fire('Warning', 'Please fill at least one field to apply bulk update.', 'warning');
        return;
    }

    isLoading.value = true;

    try {
        for (const id of selectedRows.value) {

            if (bulkForm.value.procurement_mode) {
                await saveRowFieldImmediate(id, 'procurement_mode', bulkForm.value.procurement_mode);

                const startPa = bulkForm.value.start_pa || document.querySelector(`.month-display[data-id="${id}"][data-field="start_pa"]`)?.textContent;
                await autoFillDates(id, bulkForm.value.procurement_mode, startPa);
            }

            if (bulkForm.value.start_pa) {
                await saveRowFieldImmediate(id, 'start_pa', bulkForm.value.start_pa);

                const mode = bulkForm.value.procurement_mode || document.querySelector(`.procurement-mode-select[data-id="${id}"]`)?.value;
                if (mode) await autoFillDates(id, mode, bulkForm.value.start_pa);
            }

            for (const field of ['supporting_doc', 'remarks']) {
                if (bulkForm.value[field]) {
                    await saveRowFieldImmediate(id, field, bulkForm.value[field]);
                }
            }
        }

        Swal.fire('Success', 'Bulk update applied.', 'success').then(() => {
            selectedRows.value = [];
            closeModal();

            window.location.reload();
        });

    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Bulk update failed.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const autoFillDates = async (rowId, procurementMode, startPa) => {
    if (!procurementMode) return

    let endPa = ''
    let expectedDelivery = ''

    if (startPa) {
        if (procurementMode === 'Bidding') {
            endPa = addMonths(startPa, 3)
            expectedDelivery = addMonths(endPa, 1)
        }

        if (procurementMode === 'SVP') {
            endPa = addMonths(startPa, 1)
            expectedDelivery = addMonths(endPa, 1)
        }

        if (procurementMode === 'DA/DC') {
            endPa = startPa
            expectedDelivery = addMonths(endPa, 1)
        }
    }

    if (procurementMode === 'Bidding') {
        await saveRowFieldImmediate(rowId, 'ppc', 1);
        const ppcSelect = document.querySelector(`.ppc-select[data-id="${rowId}"]`);
        if (ppcSelect) ppcSelect.value = 'Yes';
    } else if (procurementMode === 'SVP' || procurementMode === 'DA/DC') {
        await saveRowFieldImmediate(rowId, 'ppc', 0);
        const ppcSelect = document.querySelector(`.ppc-select[data-id="${rowId}"]`);
        if (ppcSelect) ppcSelect.value = 'No';
    }

    if (endPa) {
        await saveRowFieldImmediate(rowId, 'end_pa', endPa)
        const endSpan = document.querySelector(
            `.month-display[data-id="${rowId}"][data-field="end_pa"]`
        )
        if (endSpan) endSpan.textContent = endPa
    }

    if (expectedDelivery) {
        await saveRowFieldImmediate(rowId, 'expected_delivery', expectedDelivery)
        const deliverySpan = document.querySelector(
            `.month-display[data-id="${rowId}"][data-field="expected_delivery"]`
        )
        if (deliverySpan) deliverySpan.textContent = expectedDelivery
    }
}

const handleClick = (e) => {
    const target = e.target;

    if (
        !target.classList.contains('month-display') &&
        !target.classList.contains('month-editor')
    ) return;

    const input = target.nextElementSibling;
    if (!input) return;

    target.classList.add('hidden');
    input.classList.remove('hidden');
    input.focus();
};

const handleChange = (e) => {
    const target = e.target;

    if (target.classList.contains('row-checkbox')) {
        const id = target.dataset.id;

        if (target.checked) {
            if (!selectedRows.value.includes(id)) {
                selectedRows.value.push(id);
            }
        } else {
            selectedRows.value = selectedRows.value.filter(i => i !== id);
        }
        return;
    }

    if (target.id === 'select-all') {
        const checked = target.checked;
        const checkboxes = document.querySelectorAll('.row-checkbox');

        selectedRows.value = [];

        checkboxes.forEach(cb => {
            cb.checked = checked;
            if (checked) {
                selectedRows.value.push(cb.dataset.id);
            }
        });
        return;
    }

    if (!target.classList.contains('inline-input')) return;

    const id = target.dataset.id;
    let field = target.dataset.field;
    let value = target.value;

    if (target.type === 'month' && value) {
        const [yyyy, mm] = value.split('-');
        value = `${mm}/${yyyy}`;
    }

    if (field === 'ppc') {
        value = value === 'Yes' ? 1 : 0;
    }

    console.log('INLINE SAVE TRIGGERED', { id, field, value });

    saveRowField(id, field, value);

    if (field === 'start_pa') {
        const select = document.querySelector(
            `.procurement-mode-select[data-id="${id}"]`
        );
        autoFillDates(id, select?.value, value);
    }

    if (field === 'procurement_mode') {
        const startSpan = document.querySelector(`.month-display[data-id="${id}"][data-field="start_pa"]`);
        autoFillDates(id, value, startSpan?.textContent);

        if (value === 'SVP' || value === 'DA/DC') {
            const ppcInput = document.querySelector(`.ppc-select[data-id="${id}"]`);
            if (ppcInput) ppcInput.value = 'No';
            saveRowField(id, 'ppc', 0);
        }
    }

    const display = target.previousElementSibling;
    if (display) {
        display.textContent = value;
        display.classList.remove('hidden');
        target.classList.add('hidden');
    }
};

const { hasAnyRole, hasPermission } = useAuthPermission();
const page = usePage();
const isLoading = ref(false);

const message = computed(() => page.props.flash.message);
const errMessage = computed(() => page.props.flash.error);

const props = defineProps({
    ppmp: Object,
    countTrashed: Number,
    accountClass: Object,
    user: Number,
});

const form = useForm({
    conId: props.ppmp.id,
    user: props.user,
});

const editParticular = useForm({
    partId: '',
    prodCode: '',
    prodDesc: '',
    firstQty: '',
    secondQty: '',
    user: props.user,
});

const editAdjustment = useForm({
    ppmpId: '',
    adjustmentType: '',
    initAdjustment: 100,
    customInitAdjustment: [],
    user: props.user,
});

const editFinalAdjustment = useForm({
    ppmpId: '',
    adjustmentType: '',
    initAdjustment: 100,
    customInitAdjustment: [],
    user: props.user,
});

const dropParticular = useForm({
    pId: '',
    user: props.user,
});

const modalState = ref(null);
const showModal = (modalType) => (modalState.value = modalType);
const closeModal = () => (modalState.value = null);

const isConfirmModalOpen = computed(() => modalState.value === 'confirm');
const isEditPPModalOpen = computed(() => modalState.value === 'edit');
const isDropPPModalOpen = computed(() => modalState.value === 'drop');
const isEditAdjustment = computed(() => modalState.value === 'adjustment');
const isEditFinalAdjustment = computed(() => modalState.value === 'finalAdjustment');

const numberWithoutCommas = (qty) => qty.replace(/,/g, '');

const openEditPpmpModal = (particular) => {
    editParticular.partId = particular.pId;
    editParticular.prodCode = particular.prodCode;
    editParticular.prodDesc = particular.prodName;
    editParticular.firstQty = numberWithoutCommas(particular.qtyFirst);
    editParticular.secondQty = numberWithoutCommas(particular.qtySecond);
    modalState.value = 'edit';
};

const openAdjustmentPpmpModal = (ppmp) => {
    editAdjustment.ppmpId = ppmp.id;
    modalState.value = 'adjustment';
};

const openFinalAdjustmentModal = (ppmp) => {
    editFinalAdjustment.ppmpId = ppmp.id;
    modalState.value = 'finalAdjustment';
};

const openDropPpmpModal = (particular) => {
    dropParticular.pId = particular.pId;
    modalState.value = 'drop';
};

const submitRequest = (method, url, formData) => {
    isLoading.value = true;
    formData[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            if (errMessage.value) {
                Swal.fire({
                    title: 'Failed',
                    text: errMessage.value,
                    icon: 'error',
                }).then(() => (isLoading.value = false));
            } else {
                formData.reset();
                Swal.fire({
                    title: 'Success',
                    text: message.value,
                    icon: 'success',
                }).then(() => {
                    closeModal();
                    isLoading.value = false;
                });
            }
        },
        onError: (errors) => {
            isLoading.value = false;
            console.info('Error: ' + JSON.stringify(errors));
        },
    });
};

const submitEdit = () =>
    submitRequest('put', route('conso-particular-update', { ppmpConsolidated: editParticular.partId }), editParticular);
const submitDrop = () =>
    submitRequest('delete', route('conso-particular-destroy', { ppmpConsolidated: dropParticular.pId }), dropParticular);
const submitAdjustment = () => submitRequest('post', route('updateInitialAdjustment'), editAdjustment);
const submitFinalAdjustment = () => submitRequest('post', route('updateFinalAdjustment'), editFinalAdjustment);

const submit = async () => {
    const confirm = await Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Proceed!',
    });

    if (!confirm.isConfirmed) return;

    isLoading.value = true;
    form.post(route('proceed.to.final.ppmp', { ppmpTransaction: form.conId }), {
        preserveScroll: true,
        onSuccess: () => {
            if (errMessage.value) {
                Swal.fire({ title: 'Error!', text: errMessage.value, icon: 'error' });
                isLoading.value = false;
                return;
            }
            closeModal();
            isLoading.value = false;
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join('\n') || 'An error occurred.';
            Swal.fire({ title: 'Error!', text: errorMessage, icon: 'error' });
            isLoading.value = false;
        },
    });
};

const ppmpId = usePage().props.ppmp.id

const formatMonthDisplay = (value) => {
    if (!value) return '';
    const [mm, yyyy] = value.split('/');
    return `${mm.padStart(2, '0')}/${yyyy}`;
};

const renderMonthInput = (value, rowId, field) => {
    let monthValue = '';
    if (value) {
        const [mm, yyyy] = value.split('/');
        if (mm && yyyy) monthValue = `${yyyy}-${mm.padStart(2, '0')}`;
    }

    return `
        <span
            class="month-editor month-display"
            data-id="${rowId}"
            data-field="${field}"
        >
            ${formatMonthDisplay(value)}
        </span>

        <input
            type="month"
            class="month-editor inline-input month-input hidden"
            data-id="${rowId}"
            data-field="${field}"
            value="${monthValue}"
        />
    `;
};

const parseMonthYear = (value) => {
    if (!value || typeof value !== 'string') return null

    const parts = value.split('/')
    if (parts.length !== 2) return null

    const month = parseInt(parts[0], 10)
    const year = parseInt(parts[1], 10)

    if (
        isNaN(month) ||
        isNaN(year) ||
        month < 1 ||
        month > 12
    ) {
        return null
    }

    return { month, year }
}

const formatMonthYear = (month, year) => {
    const mm = String(month).padStart(2, '0')
    return `${mm}/${year}`
}

const addMonths = (value, monthsToAdd) => {
    const parsed = parseMonthYear(value)
    if (!parsed) return ''

    let { month, year } = parsed
    month += monthsToAdd

    while (month > 12) {
        month -= 12
        year += 1
    }

    while (month < 1) {
        month += 12
        year -= 1
    }

    return formatMonthYear(month, year)
}

const selectedRows = ref([])
const savingRows = ref({});

const saveRowFieldImmediate = async (id, field, value) => {
    try {
        savingRows.value[id] = true

        console.log('INLINE SAVE TRIGGERED', { id, field, value })

        await axios.put(
            route('ppmp.particular.inline-update', id),
            {
                field,
                value,
                user: props.user,
            }
        )

    } catch (e) {
        console.error(e)
        Swal.fire('Error', 'Failed to save changes', 'error')
    } finally {
        savingRows.value[id] = false
    }
}

const saveRowField = debounce(saveRowFieldImmediate, 200)

const columns = [
    {
        title: '<input type="checkbox" id="select-all" />',
        data: null,
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
            return `
            <input 
                type="checkbox" 
                class="row-checkbox"
                data-id="${row.pId}"
            />
            `
        }
    },
    {
        data: 'prodCode',
        title: 'Stock No#',
        width: '8%'
    },
    {
        data: 'prodName',
        title: 'Description',
        width: '24%'
    },
    {
        data: 'procurement_mode',
        title: 'Recommended Mode of Procurement',
        width: '8%',
        render: (data, type, row) => {
            const value = data ?? '';
            return `
            <select class="inline-input procurement-mode-select" style="border-radius: 0.5rem; border: 1px solid #d1d5db; padding-top: 0.25rem; padding-bottom: 0.25rem; padding-left: 0.25rem; padding-right: 2rem;"data-id="${row.pId}" data-field="procurement_mode">
                <option disabled selected style="font-size: 10px">Select</option>
                <option value="Bidding" ${value === 'Bidding' ? 'selected' : ''}>Bidding</option>
                <option value="SVP" ${value === 'SVP' ? 'selected' : ''}>SVP</option>
                <option value="DA/DC" ${value === 'DA/DC' ? 'selected' : ''}>DA/DC</option>
            </select>
            `;
        },
    },
    {
        data: 'ppc',
        title: 'Pre-Procurement Conference',
        width: '8%',
        render: (data, type, row) => {
            const value = data ? 'Yes' : 'No';
            return `
        <select 
        class="inline-input ppc-select" style="border-radius: 0.5rem; border: 1px solid #d1d5db; padding-top: 0.25rem; padding-bottom: 0.25rem; padding-left: 0.25rem; padding-right: 2rem;" data-id="${row.pId}" data-field="ppc">
            <option value="Yes" ${value === 'Yes' ? 'selected' : ''}>Yes</option>
            <option value="No" ${value === 'No' ? 'selected' : ''}>No</option>
        </select>
        `;
        },
    },
    {
        data: 'start_pa',
        title: 'Start of Procurement Activity',
        width: '8%',
        render: (data, type, row) => renderMonthInput(data, row.pId, 'start_pa'),
    },
    {
        data: 'end_pa',
        title: 'End of Procurement Activity',
        width: '8%',
        render: (data, type, row) => renderMonthInput(data, row.pId, 'end_pa'),
    },
    {
        data: 'expected_delivery',
        title: 'Expected Delivery',
        width: '8%',
        render: (data, type, row) => renderMonthInput(data, row.pId, 'expected_delivery'),
    },
    {
        data: 'amount',
        title: 'Estimated Budget',
        width: '8%', render: (data, type, row) => data ?? row.totalAmount
    },
    {
        data: 'supporting_doc',
        title: 'Supporting Document',
        width: '8%',
        render: (data, type, row) => {
            const value = data ?? '';
            return `
                <input
                    type="text"
                    class="inline-input"
                    data-id="${row.pId}"
                    data-field="supporting_doc"
                    value="${value}"
                    style="width: 80px !important; margin: 0 !important; padding-top: 0.25rem; padding-bottom: 0.25rem; padding-left: 0.25rem; padding-right: 0.25rem; border-radius: 0.5rem; border: 1px solid #d1d5db;"
                />
            `;
        },
    },
    {
        data: 'remarks',
        title: 'Remarks',
        width: '8%',
        render: (data, type, row) => {
            const value = data ?? '';
            return `
                <input
                    type="text"
                    class="inline-input"
                    data-id="${row.pId}"
                    data-field="remarks"
                    value="${value}"
                    style="width: 80px !important; margin: 0 !important; padding-top: 0.25rem; padding-bottom: 0.25rem; padding-left: 0.25rem; padding-right: 0.25rem; border-radius: 0.5rem; border: 1px solid #d1d5db;"
                />
            `;
        },
    },
];

onMounted(() => {
    document.addEventListener('click', handleClick);
    document.addEventListener('change', handleChange);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClick);
    document.removeEventListener('change', handleChange);
});
</script>

<template>

    <Head title="PPMP" />
    <AuthenticatedLayout>
        <div v-if="isLoading" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="w-16 h-16 border-4 border-t-indigo-600 border-gray-200 rounded-full animate-spin"></div>
        </div>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center">
                        <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="mr-4 h-4 w-4">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Project Procurement Management Plan
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('conso.ppmp.type', { type: 'consolidated', status: 'draft' })"
                                class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Consolidated PPMP List
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#"
                                class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                No# {{ ppmp.ppmp_code }}
                            </a>
                        </div>
                    </li>
                </ol>
                <ol>
                    <Dropdown width="56">
                        <template #trigger>
                            <button class="flex items-center rounded-full transition">
                                <span class="sr-only">Open options</span>
                                <svg class="w-7 h-7 text-indigo-800 hover:text-indigo-600" fill="currentColor"
                                    xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="m23.365,3.699l-1.322,1.322-3.064-3.064,1.234-1.234c.801-.801,2.108-.955,2.985-.237,1.009.825,1.064,2.316.166,3.214Zm-5.8-.328l-5.296,5.296c-.813.813-1.269,1.915-1.269,3.064v.769c0,.276.224.5.5.5h.769c1.149,0,2.251-.457,3.064-1.269l5.296-5.296-3.064-3.064Zm3.707,10.514l-.451-.26c.102-.544.153-1.088.153-1.625s-.051-1.081-.153-1.625l-.29-1.015-3.784,3.784c-1.196,1.196-2.786,1.855-4.478,1.855h-.77c-1.379,0-2.5-1.121-2.5-2.5v-.77c0-1.691.659-3.281,1.855-4.478l4.119-4.119v-.134c0-1.654-1.346-3-3-3s-3,1.346-3,3v.522c-1.047.37-2.016.929-2.857,1.649l-.45-.259c-.693-.398-1.501-.504-2.277-.295-.773.208-1.419.706-1.818,1.4-.4.694-.505,1.503-.296,2.277.208.773.706,1.419,1.401,1.819l.451.259c-.102.544-.153,1.088-.153,1.626s.051,1.082.153,1.626l-.451.259c-.695.4-1.192,1.046-1.401,1.819-.209.774-.104,1.583.295,2.276.399.695,1.045,1.193,1.819,1.401.776.21,1.584.104,2.277-.295l.45-.259c.841.721,1.81,1.279,2.857,1.649v.522c0,1.654,1.346,3,3,3s3-1.346,3-3v-.522c1.047-.37,2.016-.929,2.857-1.649l.45.259c.695.399,1.503.505,2.277.295.773-.208,1.419-.706,1.819-1.401.825-1.434.329-3.271-1.105-4.096Z" />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <button @click="openAdjustmentPpmpModal(ppmp)"
                                class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24">
                                    <path fill="currentColor"
                                        d="M3 21V5.8L5.3 3h13.4L21 5.8v2.325l-5 5V8H8v8l4-2l2.075 1.05L12 17.1V21zm11 0v-3.075l6.575-6.55l3.075 3.05L17.075 21zm6.575-5.6l.925-.975l-.925-.925l-.95.95zM5.4 6h13.2l-.85-1H6.25z" />
                                </svg>
                                <span class="ml-2">Initial Qty Adj</span>
                            </button>

                            <button @click="openFinalAdjustmentModal(ppmp)"
                                class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24">
                                    <path fill="currentColor"
                                        d="M3 21V5.8L5.3 3h13.4L21 5.8v2.325l-5 5V8H8v8l4-2l2.075 1.05L12 17.1V21zm11 0v-3.075l6.575-6.55l3.075 3.05L17.075 21zm6.575-5.6l.925-.975l-.925-.925l-.95.95zM5.4 6h13.2l-.85-1H6.25z" />
                                </svg>
                                <span class="ml-2">Final Qty Adj</span>
                            </button>

                            <a v-if="hasPermission('print-app') || hasAnyRole(['Developer'])"
                                :href="route('generatePdf.ConsolidatedPpmp', { ppmp: ppmp.id, type: 'raw' })"
                                target="_blank"
                                class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linejoin="round" stroke-width="2"
                                        d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z" />
                                </svg>
                                <span class="ml-2">Raw File Format</span>
                            </a>

                            <a v-if="hasPermission('print-app') || hasAnyRole(['Developer'])"
                                :href="route('generatePdf.ConsolidatedPpmp', { ppmp: ppmp.id, type: 'contingency' })"
                                target="_blank"
                                class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linejoin="round" stroke-width="2"
                                        d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z" />
                                </svg>
                                <span class="ml-2">Contingency Format</span>
                            </a>

                            <div v-if="hasPermission('print-app-summary-overview') || hasAnyRole(['Developer'])">
                                <a v-if="ppmp.ppmp_type == 'Consolidated'"
                                    :href="route('generatePdf.summaryOfConsolidated', { ppmp: ppmp.id })"
                                    target="_blank"
                                    class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linejoin="round" stroke-width="2"
                                            d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z" />
                                    </svg>
                                    <span class="ml-2">Summary Overview</span>
                                </a>
                            </div>

                            <button v-if="hasPermission('confirm-app-finalization') || hasAnyRole(['Developer'])"
                                @click="showModal('confirm')"
                                class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2"
                                        d="M4 17h7m9-3l-4 4l-2-2M4 12h11M4 7h11" />
                                </svg>
                                <span class="ml-2">Proceed as Approved</span>
                            </button>
                        </template>
                    </Dropdown>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <div class="shadow sm:rounded-lg bg-zinc-300 mb-5">
                        <div class="flex items-center justify-between bg-zinc-600 px-4 py-5 sm:px-6 rounded-t-lg">
                            <div>
                                <h3 class="font-bold text-lg leading-6 text-zinc-300">
                                    APP Information
                                </h3>
                                <p class="text-sm text-zinc-300">
                                    ID# [{{ ppmp.ppmp_code }}]
                                </p>
                            </div>
                            <div>
                                <Link :href="route('conso.ppmp.show', ppmpId)"
                                    class="bg-indigo-900 font-bold text-md leading-6 text-white p-2 rounded-md hover:bg-indigo-800 transition">
                                    Done
                                </Link>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-0 lg:grid-cols-2 lg:gap-2">
                            <dl class="font-semibold text-base">
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Description
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.description }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Account Classess
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.account_class_ids }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        No. of Offices
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.office_ppmp_ids }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        No. Of Item Listed
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.totalItems }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        APP for CY
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.ppmp_year }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Date Created
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.formatted_created }}
                                    </dd>
                                </div>
                            </dl>
                            <dl class="font-semibold text-base">
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Updated By
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.updater.name }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Price Adjustment
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.price_adjustment * 100 }}%
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Initial Quantity Adjustment
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.init_qty_adjustment ?? 'Not Available' }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Final Quantity Adjustment
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.final_qty_adjustment ?? 'Not Available' }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        No. Of Trashed Items
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ countTrashed }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Total Amount
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        Php. {{ ppmp.totalAmount }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <div class="mb-3">
                        <button @click="openBulkModal"
                            class="bg-indigo-900 text-white px-4 py-2 rounded-md hover:bg-indigo-800 transition">
                            Bulk Update
                        </button>
                    </div>
                    <div class="col-span-3 p-2 bg-zinc-300 rounded-md shadow mt-5 lg:mt-0">
                        <div class="p-2 overflow-hidden">
                            <div class="relative overflow-x-auto">
                                <DataTable class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                                    :columns="columns" :data="ppmp.transactions" :options="{
                                        paging: true,
                                        searching: true,
                                        ordering: false
                                    }">
                                    <template #action="props">
                                        <EditButton
                                            v-if="hasPermission('edit-app-particular') || hasAnyRole(['Developer'])"
                                            @click="openEditPpmpModal(props.cellData)" tooltip="Edit" />
                                        <RemoveButton
                                            v-if="hasPermission('delete-app-particular') || hasAnyRole(['Developer'])"
                                            @click="openDropPpmpModal(props.cellData)" tooltip="Remove" />
                                    </template>
                                </DataTable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
    <Modal :show="isEditAdjustment" @close="closeModal">
        <form @submit.prevent="submitAdjustment">
            <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline">Edit Initial
                            Quantity
                            Adjustment</h3>
                        <p class="text-sm text-zinc-700"> Enter the desired adjustment.</p>
                        <div class="mt-5">
                            <p class="text-sm font-semibold text-[#1a0037]">Adjustment Type </p>
                            <div class="relative z-0 w-full my-3 group">
                                <select v-model="editAdjustment.adjustmentType" name="adjustmentType"
                                    id="adjustmentType"
                                    class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                    required>
                                    <option disabled selected value="">Type</option>
                                    <option value="grouped">Group</option>
                                    <option value="custom">Custom</option>
                                </select>
                                <label for="adjustmentType"
                                    class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Select
                                    Type</label>
                            </div>
                        </div>
                        <div v-if="editAdjustment.adjustmentType == 'grouped'" class="mt-5">
                            <div class="mt-5">
                                <p class="text-sm font-semibold text-[#1a0037]">Initial Quantity Adjustment</p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editAdjustment.initAdjustment" type="text" name="qtyAdjust"
                                        id="qtyAdjust"
                                        class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                        placeholder="" required />
                                    <label for="qtyAdjust"
                                        class="font-semibold text-zinc-700  absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Value
                                        must be within 50 to 100</label>
                                </div>
                            </div>
                        </div>
                        <div v-if="editAdjustment.adjustmentType == 'custom'" class="mt-5">
                            <p class="text-sm font-semibold text-[#1a0037]"> Initial Quantity Adjustment: <span
                                    class="font-medium">Value must be within 50 to 100</span></p>
                            <div v-for="account in accountClass" :key="account.id"
                                class="relative z-0 w-full group my-2">
                                <input v-model="editAdjustment.customInitAdjustment[account.id]" type="text"
                                    name="qtyAdjustment" :id="'qtyAdjustment-' + account.id"
                                    class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                    placeholder="" required />
                                <label :for="'qtyAdjustment-' + account.id"
                                    class="font-semibold text-zinc-700  absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{
                                        account.fund_name }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-zinc-400 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Confirm
                </SuccessButton>

                <DangerButton @click="closeModal">
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Cancel
                </DangerButton>
            </div>
        </form>
    </Modal>
    <Modal :show="isBulkModalOpen" @close="closeModal">
        <div class="relative">
            <div v-if="isLoading" class="absolute inset-0 bg-black/30 flex items-center justify-center z-50 rounded-md">
                <div class="w-12 h-12 border-4 border-t-indigo-600 border-gray-200 rounded-full animate-spin"></div>
            </div>

            <div class="bg-zinc-300 px-6 pt-6 pb-4 opacity-100">
                <h3 class="text-lg font-semibold text-[#1a0037] mb-4">
                    Bulk Update Selected Rows
                </h3>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Recommended Mode of Procurement</label>
                    <select v-model="bulkForm.procurement_mode" class="w-full border rounded-md p-2">
                        <option disabled value="">Select</option>
                        <option value="Bidding">Bidding</option>
                        <option value="SVP">SVP</option>
                        <option value="DA/DC">DA/DC</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">
                        Pre-Procurement Conference
                    </label>
                    <select v-model="bulkForm.ppc" class="w-full border rounded-md p-2">
                        <option disabled value="">-- Select --</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">
                        Start of Procurement Activity
                    </label>
                    <input type="month" :value="mmYYYYToYM(bulkForm.start_pa)"
                        @change="e => bulkForm.start_pa = ymToMMYYYY(e.target.value)"
                        class="w-full border rounded-md p-2" />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">
                        End of Procurement Activity
                    </label>
                    <input type="month" :value="mmYYYYToYM(bulkForm.end_pa)"
                        @change="e => bulkForm.end_pa = ymToMMYYYY(e.target.value)"
                        class="w-full border rounded-md p-2" />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">
                        Expected Delivery
                    </label>
                    <input type="month" :value="mmYYYYToYM(bulkForm.expected_delivery)"
                        @change="e => bulkForm.expected_delivery = ymToMMYYYY(e.target.value)"
                        class="w-full border rounded-md p-2" />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">
                        Supporting Document
                    </label>
                    <input type="text" v-model="bulkForm.supporting_doc" class="w-full border rounded-md p-2"
                        placeholder="Market Scoping Checklist, etc..." />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">
                        Remarks
                    </label>
                    <input type="text" v-model="bulkForm.remarks" class="w-full border rounded-md p-2"
                        placeholder="Additional Details..." />
                </div>
            </div>

            <div class="bg-zinc-400 px-6 py-3 flex justify-end gap-3">
                <button @click="closeModal" class="bg-red-600 text-white px-4 py-2 rounded-md" :disabled="isLoading">
                    Cancel
                </button>

                <button @click="applyBulkUpdate" :disabled="isLoading"
                    class="bg-indigo-900 text-white px-4 py-2 rounded-md flex items-center gap-2">
                    <svg v-if="isLoading" class="w-5 h-5 text-white animate-spin" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Done
                </button>
            </div>
        </div>
    </Modal>
    <Modal :show="isEditFinalAdjustment" @close="closeModal">
        <form @submit.prevent="submitFinalAdjustment">
            <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline">Edit Final
                            Quantity
                            Adjustment</h3>
                        <p class="text-sm text-zinc-700">
                            Sets the final item quantity limit for each office request.
                            <strong>Note:</strong> Applies only to office requests; consolidated particulars remain
                            unchanged.
                        </p>
                        <div class="mt-5">
                            <p class="text-sm font-semibold text-[#1a0037]">Adjustment Type </p>
                            <div class="relative z-0 w-full my-3 group">
                                <select v-model="editFinalAdjustment.adjustmentType" name="adjustmentType"
                                    id="adjustmentType"
                                    class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                    required>
                                    <option disabled selected value="">Type</option>
                                    <option value="grouped">Group</option>
                                    <option value="custom">Custom</option>
                                </select>
                                <label for="adjustmentType"
                                    class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Select
                                    Type</label>
                            </div>
                        </div>
                        <div v-if="editFinalAdjustment.adjustmentType == 'grouped'" class="mt-5">
                            <div class="mt-5">
                                <p class="text-sm font-semibold text-[#1a0037]">Final Quantity Adjustment</p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editFinalAdjustment.initAdjustment" type="text" name="qtyAdjust"
                                        id="qtyAdjust"
                                        class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                        placeholder="" required />
                                    <label for="qtyAdjust"
                                        class="font-semibold text-zinc-700  absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Value
                                        must be within 50 to 100</label>
                                </div>
                            </div>
                        </div>
                        <div v-if="editFinalAdjustment.adjustmentType == 'custom'" class="mt-5">
                            <p class="text-sm font-semibold text-[#1a0037]"> Final Quantity Adjustment: <span
                                    class="font-medium">Value must be within 50 to 100</span></p>
                            <div v-for="account in accountClass" :key="account.id"
                                class="relative z-0 w-full group my-2">
                                <input v-model="editFinalAdjustment.customInitAdjustment[account.id]" type="text"
                                    name="qtyAdjustment" :id="'qtyAdjustment-' + account.id"
                                    class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                    placeholder="" required />
                                <label :for="'qtyAdjustment-' + account.id"
                                    class="font-semibold text-zinc-700  absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">{{
                                        account.fund_name }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-zinc-400 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Confirm
                </SuccessButton>

                <DangerButton @click="closeModal">
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Cancel
                </DangerButton>
            </div>
        </form>
    </Modal>
    <Modal :show="isEditPPModalOpen" @close="closeModal">
        <form @submit.prevent="submitEdit">
            <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Update Quantity
                        </h3>
                        <p class="text-sm text-zinc-700"> Enter the quantity you want to update on the input field.</p>
                        <div class="mt-3">
                            <p class="text-sm font-semibold text-[#1a0037]"> Product Information: </p>
                            <input v-model="editParticular.prodCode" type="text" id="prodCode"
                                class="mt-2 p-2 bg-gray-100 border border-gray-100 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500"
                                placeholder="Ex. 01-01-01" readonly>
                            <textarea v-model="editParticular.prodDesc" type="text" id="prodCode"
                                class="mt-2 p-2 bg-gray-100 border border-gray-100 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500"
                                placeholder="Ex. 01-01-01" readonly></textarea>
                        </div>
                        <div class="mt-5">
                            <p class="text-sm font-semibold text-[#1a0037]"> Quantity: </p>
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-2 pointer-events-none">
                                    <span class="text-zinc-700 text-sm font-semibold">1st Qty: </span>
                                </div>
                                <input v-model="editParticular.firstQty" type="number" id="firstQty"
                                    class="mt-2 pl-16 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500"
                                    placeholder="First Semester" required>
                            </div>
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-2 pointer-events-none">
                                    <span class="text-zinc-700 text-sm font-semibold">2nd Qty: </span>
                                </div>
                                <input v-model="editParticular.secondQty" type="number" id="secondQty"
                                    class="mt-2 pl-16 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500"
                                    placeholder="Second Semester">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-zinc-400 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Confirm
                </SuccessButton>

                <DangerButton @click="closeModal">
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Cancel
                </DangerButton>
            </div>
        </form>
    </Modal>
    <Modal :show="isDropPPModalOpen" @close="closeModal">
        <form @submit.prevent="submitDrop">
            <div class="bg-zinc-300 h-auto">
                <div class="p-6  md:mx-auto">
                    <svg class="text-rose-600 w-16 h-16 mx-auto my-6" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                            clip-rule="evenodd" />
                    </svg>

                    <div class="text-center">
                        <h3 class="md:text-2xl text-base font-semibold text-[#1a0037] text-center">Move to Trash!</h3>
                        <p class="text-zinc-700 my-2">Confirming this action will remove the selected Product from the
                            list.
                            This action can't be undone.</p>
                        <p> Please confirm if you wish to proceed. </p>
                        <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
                            <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Confirm
                            </SuccessButton>

                            <DangerButton @click="closeModal">
                                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Cancel
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </Modal>
    <Modal :show="isConfirmModalOpen" @close="closeModal">
        <form @submit.prevent="submit">
            <div class="bg-zinc-300 h-auto">
                <div class="p-6  md:mx-auto">
                    <svg class="text-indigo-700 w-16 h-16 mx-auto my-6" xmlns="http://www.w3.org/2000/svg" id="Layer_1"
                        data-name="Layer 1" viewBox="0 0 24 24" width="512" height="512" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18.5,3h-5.53c-.08,0-.16-.02-.22-.05l-3.16-1.58c-.48-.24-1.02-.37-1.56-.37h-2.53C2.47,1,0,3.47,0,6.5v11c0,3.03,2.47,5.5,5.5,5.5h13c3.03,0,5.5-2.47,5.5-5.5V8.5c0-3.03-2.47-5.5-5.5-5.5Zm2.5,14.5c0,1.38-1.12,2.5-2.5,2.5H5.5c-1.38,0-2.5-1.12-2.5-2.5V8H20.95c.03,.16,.05,.33,.05,.5v9Zm-3.13-3.71c.39,.39,.39,1.02,0,1.41l-3.16,3.16c-.63,.63-1.71,.18-1.71-.71v-1.66H7.5c-.83,0-1.5-.67-1.5-1.5s.67-1.5,1.5-1.5h5.5v-1.66c0-.89,1.08-1.34,1.71-.71l3.16,3.16Z" />
                    </svg>
                    <div class="text-center">
                        <h3 class="md:text-2xl text-base font-semibold text-[#1a0037] text-center">Confirm as Approved!
                        </h3>
                        <p class="text-zinc-700 my-2">Confirming this action will remark the selected PPMP as
                            Final/Approved. This action can't be undone.</p>
                        <p> Please confirm if you wish to proceed. </p>
                        <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
                            <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Confirm
                            </SuccessButton>

                            <DangerButton @click="closeModal">
                                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Cancel
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </Modal>
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
    background-color: #fafafa;
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

:deep(table.dataTable tbody > tr > td:nth-child(2)) {
    text-align: left !important;
}

:deep(.month-editor.month-display) {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100px;
    padding: 2px;
    cursor: pointer;
    background-color: #fafafa;
    border: 1px solid transparent;
    border-radius: 4px;
    font-weight: 500;
    color: #03244d;
    height: 30px;
    text-align: center !important;
}

:deep(.month-editor.month-display:hover) {
    border-color: #7393dc;
    background-color: #eef1ff;
}

:deep(.month-editor.month-input) {
    width: 100px;
    padding: 2px;
    border: 1px solid #7393dc;
    border-radius: 4px;
    background-color: #ffffff;
    text-align: center !important;
    height: 30px;
}

:deep(.month-editor.hidden) {
    display: none;
}
</style>