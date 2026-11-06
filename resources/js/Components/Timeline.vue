<script setup>
import Icon from './Icon.vue';

defineProps({ steps: { type: Array, required: true } }); // [{ key, label, state: 'done' | 'current' | 'todo' }]
</script>

<template>
    <ol class="flex flex-col">
        <li v-for="(step, i) in steps" :key="step.key" class="flex gap-3">
            <div class="flex flex-col items-center">
                <span
                    class="grid h-7 w-7 shrink-0 place-items-center rounded-full border-2 text-xs font-extrabold"
                    :class="step.state === 'done' ? 'border-ok bg-ok text-white' : step.state === 'current' ? 'border-gold-600 bg-gold-500 text-ink' : 'border-line bg-white text-muted'"
                >
                    <Icon v-if="step.state === 'done'" name="check" :size="14" :stroke="3" /><template v-else>{{ i + 1 }}</template>
                </span>
                <span v-if="i < steps.length - 1" class="my-0.5 w-0.5 flex-1" :class="step.state === 'done' ? 'bg-ok' : 'bg-line'" />
            </div>
            <span class="pb-4 pt-0.5 text-sm font-bold" :class="step.state === 'todo' ? 'text-muted' : 'text-ink'">{{ step.label }}</span>
        </li>
    </ol>
</template>
