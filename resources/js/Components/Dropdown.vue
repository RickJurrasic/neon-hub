<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";

const props = defineProps({
    align: {
        type: String,
        default: "right",
    },
    width: {
        type: String,
        default: "48",
    },
    contentClasses: {
        type: String,
        default: "py-1 bg-white dark:bg-gray-700",
    },
});

const open = ref(false);
const trigger = ref(null);
const menu = ref(null);

const closeOnEscape = (e) => {
    if (open.value && e.key === "Escape") {
        open.value = false;
    }
};

const closeOnDocumentClick = (e) => {
    if (!open.value) {
        return;
    }

    const target = e.target;

    const clickedTrigger = trigger.value?.contains(target);
    const clickedMenu = menu.value?.contains(target);

    if (!clickedTrigger && !clickedMenu) {
        open.value = false;
        return;
    }

    if (clickedMenu) {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener("keydown", closeOnEscape);
    document.addEventListener("click", closeOnDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener("keydown", closeOnEscape);
    document.removeEventListener("click", closeOnDocumentClick);
});

const widthClass = computed(() => {
    return {
        48: "w-48",
    }[props.width.toString()];
});

const alignmentClasses = computed(() => {
    if (props.align === "left") {
        return "ltr:origin-top-left rtl:origin-top-right start-0";
    }

    if (props.align === "right") {
        return "ltr:origin-top-right rtl:origin-top-left end-0";
    }

    return "origin-top";
});
</script>

<template>
    <div class="relative">
        <button
            ref="trigger"
            type="button"
            @click="open = !open"
        >
            <slot name="trigger" />
        </button>

        <!-- Full Screen Dropdown Overlay -->
        <div
            v-show="open"
            class="fixed inset-0 z-40"
        ></div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                ref="menu"
                class="absolute z-50 mt-2 rounded-md shadow-lg"
                :class="[widthClass, alignmentClasses]"
                style="display: none"
            >
                <div
                    class="rounded-md ring-1 ring-black ring-opacity-5"
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>