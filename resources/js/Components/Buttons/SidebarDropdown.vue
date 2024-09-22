<script setup>
    import { computed, ref, watch } from 'vue';

    const props = defineProps({
        type: {
            type: String,
            default: 'button',
        },
        active: {
            type: Boolean,
            default: false,
        }
    });

    const isOpen = ref(props.active);

    watch(() => props.active, (newValue) => {
        isOpen.value = newValue;
    });

    const classes = computed(() =>
        props.active
        ? 'flex items-center p-2 mb-1 w-full text-base rounded-lg font-medium leading-5 text-white bg-indigo-900 transition duration-75 group'
        : 'flex items-center p-2 w-full text-base rounded-lg font-medium leading-5 text-gray-900 hover:text-gray-50 hover:bg-indigo-900 transition duration-75 group'
    );

    const toggleDropdown = () => {
        isOpen.value = !isOpen.value;
    };
</script>

<template>
    <div>
        <button :type="type" :class="classes" @click="toggleDropdown">
            <slot />
        </button>
        <ul v-if="isOpen" class="dropdown-list">
            <slot name="dropdown-items" />
        </ul>
    </div>
</template>
