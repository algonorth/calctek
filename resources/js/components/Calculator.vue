<template>
  <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden shadow-2xl">
    <!-- Display -->
    <div class="bg-neutral-950 p-5 border-b border-neutral-800">
      <!-- Expression input -->
      <div class="min-h-8 text-right text-neutral-400 text-sm font-mono mb-1 truncate">
        {{ displayExpression || '&nbsp;' }}
      </div>
      <!-- Result / current number -->
      <div class="text-right font-mono leading-none transition-all duration-150"
           :class="result !== null ? 'text-3xl text-blue-400 font-semibold' : 'text-4xl text-white font-semibold'">
        {{ result !== null ? formatResult(result) : (current || '0') }}
      </div>
      <!-- Error message -->
      <div v-if="error" class="mt-2 text-right text-xs text-red-400 font-medium">
        {{ error }}
      </div>
    </div>

    <!-- Keypad: 4 columns, traditional layout -->
    <div class="p-4 grid grid-cols-4 gap-2">

      <!-- Row 0: Scientific functions -->
      <button @click="appendSqrt"      class="btn-key btn-sci">√</button>
      <button @click="append('^')"     class="btn-key btn-sci">x<sup class="text-xs">y</sup></button>
      <button @click="append('(')"     class="btn-key btn-sci">(</button>
      <button @click="append(')')"     class="btn-key btn-sci">)</button>

      <!-- Row 1: AC, ⌫, ÷ -->
      <button @click="clear"           class="btn-key col-span-2 bg-red-950 text-red-400 hover:bg-red-900 hover:text-red-300 text-sm font-semibold tracking-wider">
        AC
      </button>
      <button @click="backspace"       class="btn-key bg-neutral-800 text-neutral-400 hover:bg-neutral-700">
        <svg class="w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/>
        </svg>
      </button>
      <button @click="append('/')"     class="btn-key btn-op">÷</button>

      <!-- Row 2: 7 8 9 × -->
      <button @click="append('7')"     class="btn-key btn-num">7</button>
      <button @click="append('8')"     class="btn-key btn-num">8</button>
      <button @click="append('9')"     class="btn-key btn-num">9</button>
      <button @click="append('*')"     class="btn-key btn-op">×</button>

      <!-- Row 3: 4 5 6 − -->
      <button @click="append('4')"     class="btn-key btn-num">4</button>
      <button @click="append('5')"     class="btn-key btn-num">5</button>
      <button @click="append('6')"     class="btn-key btn-num">6</button>
      <button @click="append('-')"     class="btn-key btn-op">−</button>

      <!-- Row 4: 1 2 3 + -->
      <button @click="append('1')"     class="btn-key btn-num">1</button>
      <button @click="append('2')"     class="btn-key btn-num">2</button>
      <button @click="append('3')"     class="btn-key btn-num">3</button>
      <button @click="append('+')"     class="btn-key btn-op">+</button>

      <!-- Row 5: 0 . = -->
      <button @click="append('0')"     class="btn-key col-span-2 btn-num">0</button>
      <button @click="appendDecimal"   class="btn-key btn-num">.</button>
      <button @click="calculate"       class="btn-key bg-gradient-to-b from-blue-500 to-blue-600 text-white hover:from-blue-400 hover:to-blue-500 font-bold text-xl shadow-lg shadow-blue-900/40">
        =
      </button>

    </div>
  </div>
</template>


<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const emit = defineEmits(['calculated']);

// State
const expression = ref('');   // full expression string being built
const current    = ref('');   // current token display
const result     = ref(null); // last successful result
const error      = ref('');
const justCalced = ref(false);// whether we just computed a result

const displayExpression = computed(() => expression.value);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatResult(val) {
  // Show up to 10 significant decimal digits, stripping trailing zeros
  const n = Number(val);
  if (Number.isInteger(n)) return n.toString();
  // Avoid scientific notation for small floats
  const str = n.toPrecision(12);
  return parseFloat(str).toString();
}

function endsWithOperator() {
  return /[+\-*/^(]$/.test(expression.value);
}

// ─── Button actions ───────────────────────────────────────────────────────────

function append(char) {
  error.value = '';

  // If we just got a result and user presses a digit/sqrt/open-paren → new expr
  if (justCalced.value) {
    if (/[0-9.(]/.test(char)) {
      expression.value = '';
      result.value = null;
    }
    justCalced.value = false;
  }

  expression.value += char;
  current.value = char;
}

function appendDecimal() {
  // Find last number segment; don't add second '.'
  const lastSegment = expression.value.split(/[+\-*/^()\s]/).pop();
  if (lastSegment.includes('.')) return;
  append('.');
}

function appendSqrt() {
  error.value = '';
  if (justCalced.value) {
    expression.value = '';
    result.value = null;
    justCalced.value = false;
  }
  expression.value += 'sqrt(';
  current.value = 'sqrt(';
}

function backspace() {
  error.value = '';
  if (expression.value.length === 0) return;
  // Remove last character(s) — smart: remove "sqrt(" as a unit
  if (expression.value.endsWith('sqrt(')) {
    expression.value = expression.value.slice(0, -5);
  } else {
    expression.value = expression.value.slice(0, -1);
  }
  result.value = null;
}

function clear() {
  expression.value = '';
  current.value = '';
  result.value = null;
  error.value = '';
  justCalced.value = false;
}

async function calculate() {
  if (!expression.value.trim()) return;
  error.value = '';

  try {
    const response = await axios.post('/api/calculate', {
      expression: expression.value,
    });

    result.value = response.data.result;
    current.value = '';
    justCalced.value = true;
    emit('calculated');
  } catch (err) {
    const msg = err.response?.data?.error || err.response?.data?.message || 'Calculation failed';
    // Extract the first validation error if it's a Laravel 422
    if (err.response?.data?.errors) {
      const firstKey = Object.keys(err.response.data.errors)[0];
      error.value = err.response.data.errors[firstKey][0];
    } else {
      error.value = msg;
    }
  }
}

// ─── Keyboard support ─────────────────────────────────────────────────────────
function handleKey(e) {
  const key = e.key;
  if (/^[0-9]$/.test(key)) append(key);
  else if (key === '.') appendDecimal();
  else if (['+', '-', '*', '/', '^', '(', ')'].includes(key)) append(key);
  else if (key === 'Enter' || key === '=') calculate();
  else if (key === 'Backspace') backspace();
  else if (key === 'Escape') clear();
}

import { onMounted, onUnmounted } from 'vue';
onMounted(() => window.addEventListener('keydown', handleKey));
onUnmounted(() => window.removeEventListener('keydown', handleKey));
</script>

<style scoped>
@reference "../../css/app.css";

.btn-key {
  @apply flex items-center justify-center h-14 rounded-xl text-base font-medium
         transition-all duration-100 active:scale-95 cursor-pointer select-none;
}
.btn-num {
  @apply bg-neutral-800 text-white hover:bg-neutral-700;
}
.btn-op {
  @apply bg-blue-950 text-blue-300 hover:bg-blue-900 hover:text-blue-200 font-semibold;
}
.btn-sci {
  @apply bg-violet-950 text-violet-300 hover:bg-violet-900 hover:text-violet-200;
}
</style>

