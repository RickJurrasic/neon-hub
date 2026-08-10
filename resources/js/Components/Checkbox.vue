<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Boolean, Array],
        required: true,
    },
    value: {
        type: [String, Number, Boolean],
        default: null,
    },
    id: {
        type: String,
        default: undefined,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },
    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        :id="id"
        v-model="proxyChecked"
        type="checkbox"
        :value="value"
        class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
    />
</template>