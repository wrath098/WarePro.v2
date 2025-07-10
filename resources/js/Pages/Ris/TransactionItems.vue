<script setup>
const props = defineProps({
    transactions: Object
});
</script>
<template>
    <pre>{{ JSON.stringify(transactions, null, 2) }}</pre>
</template>