<script setup>
    import { computed } from 'vue';
    const props = defineProps({
        type: {
            type: String,
            default: 'button',
        },
        active: {
            type:Boolean,
            default: false,
        }
    });
    const classes = computed(() =>
        props.active
            ? 'flex items-center p-2 w-full text-base rounded-lg font-medium leading-5 text-gray-900 rounded-lg transition duration-75 hover:text-gray-50 hover:bg-indigo-900  group'
            : 'flex items-center p-2 w-full text-base rounded-lg font-medium leading-5 text-gray-900 rounded-lg transition duration-75 hover:text-gray-50 hover:bg-indigo-900  group'
    );
</script>
<template>
    <button :type="type" :class="classes">
        <slot />
    </button>
</template>
