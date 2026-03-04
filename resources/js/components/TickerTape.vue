<template>
  <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden shadow-2xl flex flex-col"
       style="min-height: 520px;">

    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-800 bg-neutral-950/60">
      <div class="flex items-center gap-2">
        <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
        <h2 class="text-sm font-semibold text-neutral-200 tracking-wide uppercase">Ticker Tape</h2>
        <span v-if="history.length"
              class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-blue-500/20 text-blue-400 text-xs font-medium">
          {{ history.length }}
        </span>
      </div>
      <button
        v-if="history.length"
        @click="clearAll"
        :disabled="clearing"
        class="text-xs text-red-400 hover:text-red-300 hover:bg-red-950/40 px-3 py-1.5 rounded-lg transition-all duration-150 font-medium disabled:opacity-50">
        Clear All
      </button>
    </div>

    <!-- Loading state -->
    <div v-if="loading && !history.length"
         class="flex-1 flex items-center justify-center">
      <div class="flex gap-1.5">
        <div class="w-2 h-2 rounded-full bg-neutral-600 animate-bounce" style="animation-delay:0ms"></div>
        <div class="w-2 h-2 rounded-full bg-neutral-600 animate-bounce" style="animation-delay:150ms"></div>
        <div class="w-2 h-2 rounded-full bg-neutral-600 animate-bounce" style="animation-delay:300ms"></div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!history.length"
         class="flex-1 flex flex-col items-center justify-center gap-3 text-neutral-600 py-12">
      <svg class="w-12 h-12 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <p class="text-sm font-medium">No calculations yet</p>
      <p class="text-xs text-neutral-700">Results will appear here</p>
    </div>

    <!-- History list -->
    <div v-else class="flex-1 overflow-y-auto divide-y divide-neutral-800/60">
      <transition-group name="tape" tag="div">
        <div
          v-for="item in history"
          :key="item.id"
          class="group flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-neutral-800/40 transition-colors duration-150">

          <!-- Expression + Result -->
          <div class="flex-1 min-w-0">
            <div class="font-mono text-xs text-neutral-400 truncate mb-0.5">
              {{ item.expression }}
            </div>
            <div class="font-mono text-base font-semibold text-white truncate">
              = {{ formatResult(item.result) }}
            </div>
          </div>

          <!-- Timestamp -->
          <div class="text-xs text-neutral-600 shrink-0 hidden sm:block">
            {{ formatTime(item.created_at) }}
          </div>

          <!-- Delete -->
          <button
            @click="deleteOne(item.id)"
            class="shrink-0 opacity-0 group-hover:opacity-100 p-1.5 rounded-lg text-neutral-500 hover:text-red-400 hover:bg-red-950/40 transition-all duration-150">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const history = ref([]);
const loading  = ref(false);
const clearing = ref(false);

function formatResult(val) {
  const n = Number(val);
  if (Number.isInteger(n)) return n.toString();
  const str = n.toPrecision(12);
  return parseFloat(str).toString();
}

function formatTime(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

async function refresh() {
  loading.value = true;
  try {
    const res = await axios.get('/api/calculations');
    history.value = res.data;
  } catch (e) {
    console.error('Failed to load history', e);
  } finally {
    loading.value = false;
  }
}

async function deleteOne(id) {
  try {
    await axios.delete(`/api/calculations/${id}`);
    history.value = history.value.filter(h => h.id !== id);
  } catch (e) {
    console.error('Failed to delete', e);
  }
}

async function clearAll() {
  clearing.value = true;
  try {
    await axios.delete('/api/calculations');
    history.value = [];
  } catch (e) {
    console.error('Failed to clear', e);
  } finally {
    clearing.value = false;
  }
}

defineExpose({ refresh });
onMounted(refresh);
</script>

<style scoped>
.tape-enter-active {
  transition: all 0.25s ease-out;
}
.tape-leave-active {
  transition: all 0.2s ease-in;
}
.tape-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
.tape-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>
