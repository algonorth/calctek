<template>
  <div class="min-h-screen bg-neutral-950 flex flex-col">
    <!-- Header -->
    <header class="border-b border-neutral-800 px-6 py-4">
      <div class="max-w-7xl mx-auto flex items-center gap-3">
        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-violet-600 shadow-lg">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
        </div>
        <div>
          <h1 class="text-lg font-semibold text-white tracking-tight">CalcTek</h1>
          <p class="text-xs text-neutral-500">API-Driven Calculator</p>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <Calculator @calculated="onCalculated" />
        <TickerTape ref="tickerTape" />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Calculator from './Calculator.vue';
import TickerTape from './TickerTape.vue';

const tickerTape = ref(null);

function onCalculated() {
  tickerTape.value?.refresh();
}
</script>
