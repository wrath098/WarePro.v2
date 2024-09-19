<script setup>
    import { computed } from 'vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        href: {
            type: String,
            required : true,
        },
        active: {
            type:Boolean
        }
    });
    const classes = computed(() =>
        props.active
        ? 'flex items-center p-2 text-base rounded-lg font-medium leading-5 text-gray-900 bg-indigo-900 text-gray-50 focus:outline-none focus:border-indigo-900 transition duration-150 ease-in-out group'
        : 'flex items-center p-2 text-base rounded-lg font-medium leading-5 text-gray-900 hover:text-gray-50 hover:bg-indigo-900 focus:outline-none focus:border-indigo-900 transition duration-150 ease-in-out group'
    );
</script>
<template>
    <Link :href="props.href" :class="classes">
        <slot />
    </Link>
</template>
