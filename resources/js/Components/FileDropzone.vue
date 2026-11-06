<script setup>
import { ref } from 'vue';
import { fileSize } from '../lib/format';
import Icon from './Icon.vue';

const props = defineProps({
    modelValue: { type: Array, required: true },
    max: { type: Number, default: 5 },
    sizeMb: { type: Number, default: 10 },
    extensions: { type: Array, default: () => [] },
    error: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const over = ref(false);
const problem = ref('');
const input = ref(null);

function accept(list) {
    problem.value = '';
    const next = [...props.modelValue];
    for (const file of list) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (props.extensions.length && !props.extensions.includes(ext)) {
            problem.value = `Le format « .${ext} » n’est pas accepté.`;
        } else if (file.size > props.sizeMb * 1024 * 1024) {
            problem.value = `« ${file.name} » dépasse ${props.sizeMb} Mo.`;
        } else if (next.length >= props.max) {
            problem.value = `${props.max} fichiers maximum.`;
        } else {
            next.push(file);
        }
    }
    emit('update:modelValue', next);
}

const onDrop = (e) => { over.value = false; accept([...e.dataTransfer.files]); };
const onPick = (e) => { accept([...e.target.files]); e.target.value = ''; };
const remove = (i) => emit('update:modelValue', props.modelValue.filter((_, n) => n !== i));
</script>

<template>
    <div>
        <div
            class="halftone cursor-pointer rounded-xl border-2 border-dashed px-4 py-6 text-center transition"
            :class="over ? 'border-gold-600 bg-gold-100' : 'border-line bg-paper2/60 hover:border-gold-500'"
            role="button" tabindex="0"
            @click="input.click()" @keydown.enter.prevent="input.click()"
            @dragover.prevent="over = true" @dragleave="over = false" @drop.prevent="onDrop"
        >
            <Icon name="upload" :size="26" class="mx-auto text-gold-700" />
            <p class="mt-2 text-sm font-bold">Glissez vos fichiers ici ou <span class="text-gold-700 underline">parcourez</span></p>
            <p class="mt-1 text-xs text-muted">Logo, visuel, maquette · {{ max }} fichiers, {{ sizeMb }} Mo chacun</p>
            <input ref="input" type="file" multiple class="hidden" :accept="extensions.map((e) => '.' + e).join(',')" @change="onPick" />
        </div>

        <ul v-if="modelValue.length" class="mt-3 space-y-2">
            <li v-for="(file, i) in modelValue" :key="i" class="flex items-center gap-3 rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <Icon name="paperclip" :size="16" class="text-gold-700" />
                <span class="min-w-0 flex-1 truncate font-semibold">{{ file.name }}</span>
                <span class="text-xs text-muted">{{ fileSize(file.size) }}</span>
                <button type="button" class="grid h-7 w-7 place-items-center rounded-md text-muted hover:bg-danger/10 hover:text-danger" :aria-label="`Retirer ${file.name}`" @click="remove(i)"><Icon name="x" :size="15" /></button>
            </li>
        </ul>
        <p v-if="problem || error" class="error">{{ problem || error }}</p>
    </div>
</template>
