<script setup>
import { Inertia } from '@inertiajs/inertia';
    import { ref, defineProps } from 'vue';

const props = defineProps({
    data: Object, // Receiving data from the previous step
});

const form = ref({
    name: props.data?.name || '',
    email: props.data?.email || '',
    address: props.data?.address || '',
});

const nextStep = () => {
    // Move to the next step and send form data
    Inertia.get('step-3', form.value);
};

const goBack = () => {
    // Send form data back to Step 1
    Inertia.get('step-1', form.value);
};
</script>

<template>
    <div>
        <h1>Step 2: Address Information</h1>
        <form @submit.prevent="nextStep">
            <div>
            <label for="address">Address:</label>
            <input v-model="form.address" type="text" id="address" placeholder="Enter your address" required />
            </div>
            <button type="submit">Next</button>
        </form>
    
        <!-- Go Back Button -->
        <button @click="goBack" type="button">Go Back</button>
    </div>
  </template>