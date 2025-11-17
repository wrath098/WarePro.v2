<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  modelValue: String,
  items: {
    type: Array,
    required: true,
  },
  placeholder: {
    type: String,
    default: "Select items",
  },
});

const emit = defineEmits(["update:modelValue", "change"])

const open = ref(false);
const search = ref("");
const selected = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
})

const toggleDropdown = () => {
  open.value = !open.value;
};

const filteredItems = computed(() =>
  props.items.filter((item) =>
    item.toLowerCase().includes(search.value.toLowerCase())
  )
);

const selectItem = (item) => {
  selected.value = item;
  emit("change", item);
  open.value = false;
};
</script>

<template>
  <div class="w-60 relative font-sans">
    <label class="block mb-1 text-sm text-gray-700">
      <slot name="title">Input Menu</slot>
    </label>

    <div
      class="border border-gray-300 rounded-md px-3 py-2 bg-white flex justify-between items-center cursor-pointer"
      @click="toggleDropdown"
    >
      <span class="text-gray-700 truncate">
        {{ selected || placeholder }}
      </span>
      <span class="text-gray-500 text-xs">▾</span>
    </div>

    <div v-if="open" class="absolute w-full mt-1 border border-red-300 rounded-md bg-white p-2 shadow z-20">
      <input
        v-model="search"
        type="text"
        :placeholder="`Search for ${placeholder.toLowerCase()}`"
        class="w-full border border-red-300 rounded-md px-2 py-1 text-sm focus:outline-none mb-2"
      />

      <div class="max-h-40 overflow-y-auto space-y-1">
        <div
          v-for="item in filteredItems"
          :key="item"
          @click="selectItem(item)"
          class="px-2 py-1 rounded cursor-pointer"
          :class="{
            'bg-blue-500 text-white font-semibold': selected === item,
            'hover:bg-gray-100': selected !== item
          }"
        >
          {{ item }}
        </div>

        <slot name="item" :items="filteredItems" :selected="selected"></slot>
      </div>
    </div>
  </div>
</template>
