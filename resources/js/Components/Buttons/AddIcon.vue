<script setup>

const props = defineProps({
    onClick: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <button @click="onClick" class="flex items-center font-bold hover:bg-indigo-600 rounded-full p-1 text-lg text-center me-2 mb-2 transition duration-150 ease-in-out">
        <slot />
        <svg class="w-7 h-7 text-indigo-900 hover:text-gray-50 transition duration-75" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18M3 12h18"/>
        </svg>
    </button>
</template>
