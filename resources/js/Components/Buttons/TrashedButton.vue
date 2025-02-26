<script setup>

const props = defineProps({
    onClick: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <button @click="onClick" class="flex items-center justify-center text-white bg-gradient-to-r from-rose-500 via-rose-600 to-rose-700 hover:bg-gradient-to-br font-medium rounded-lg text-sm text-center px-4 py-1">
        <slot />
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
            <path fill="currentColor" d="M11.5 15.308h1v-4.689l2.1 2.089l.708-.708L12 8.692L8.692 12l.708.708l2.1-2.089zM7.616 20q-.691 0-1.153-.462T6 18.384V6H5V5h4v-.77h6V5h4v1h-1v12.385q0 .69-.462 1.153T16.384 20zM17 6H7v12.385q0 .23.192.423t.423.192h8.77q.23 0 .423-.192t.192-.423zM7 6v13z"/>
        </svg>
    </button>
</template>
