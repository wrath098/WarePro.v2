<template>
    <div>
      <h1>Step 3: Confirm Your Information</h1>
      <p>Name: {{ form.name }}</p>
      <p>Email: {{ form.email }}</p>
      <p>Address: {{ form.address }}</p>
  
      <form @submit.prevent="submitForm">
        <button type="submit">Submit</button>
      </form>
    </div>
  </template>
  
  <script setup>
  import { ref, defineProps } from 'vue';
  
  // Receive data from previous steps
  const props = defineProps({
    data: Object
  });
  
  // Form data with values from all previous steps
  const form = ref({ ...props.data });
  
  // Submit the form
  const submitForm = () => {
    $inertia.post('/form/submit', form.value);
  };
  </script>