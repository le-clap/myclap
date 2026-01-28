<script setup>
import {onMounted, onUnmounted, ref, watch} from 'vue'
import flatpickr from 'flatpickr'
import {French} from 'flatpickr/dist/l10n/fr.js'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Sélectionner une date'
    },
    required: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
let fpInstance = null

onMounted(() => {
    fpInstance = flatpickr(inputRef.value, {
        locale: French,
        dateFormat: 'Y-m-d',
        altInput: true,
        //allowInput: true,
        altFormat: 'd F Y',
        defaultDate: props.modelValue || null,
        theme: 'dark',
        onChange: (selectedDates, dateStr) => {
            emit('update:modelValue', dateStr)
        }
    })
})

watch(() => props.modelValue, (newVal) => {
    if (fpInstance && newVal !== fpInstance.selectedDates[0]?.toISOString().split('T')[0]) {
        fpInstance.setDate(newVal, false)
    }
})

onUnmounted(() => {
    if (fpInstance) {
        fpInstance.destroy()
    }
})
</script>

<template>
    <div class="relative">
        <input
            ref="inputRef"
            type="text"
            :placeholder="placeholder"
            :required="required"
            class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red cursor-pointer"
        />
        <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none"
             stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
    </div>
</template>

<style>
.flatpickr-calendar {
    background: #1a1a1a;
    border: 1px solid #3a3a3a;
    border-radius: 0.5rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
}

.flatpickr-calendar.arrowTop::before,
.flatpickr-calendar.arrowTop::after {
    border-bottom-color: #3a3a3a;
}

.flatpickr-calendar.arrowBottom::before,
.flatpickr-calendar.arrowBottom::after {
    border-top-color: #3a3a3a;
}

.flatpickr-months {
    background: #1a1a1a;
    border-radius: 0.5rem 0.5rem 0 0;
}

.flatpickr-months .flatpickr-month {
    background: transparent;
    color: #fff;
    fill: #fff;
}

.flatpickr-current-month .flatpickr-monthDropdown-months {
    background: #1a1a1a;
    color: #fff;
}

.flatpickr-current-month .flatpickr-monthDropdown-months:hover {
    background: #2a2a2a;
}

.flatpickr-current-month input.cur-year {
    color: #fff;
}

.flatpickr-months .flatpickr-prev-month,
.flatpickr-months .flatpickr-next-month {
    color: #fff;
    fill: #fff;
}

.flatpickr-months .flatpickr-prev-month:hover,
.flatpickr-months .flatpickr-next-month:hover {
    color: #ff0502;
}

.flatpickr-months .flatpickr-prev-month:hover svg,
.flatpickr-months .flatpickr-next-month:hover svg {
    fill: #ff0502;
}

.flatpickr-weekdays {
    background: #1a1a1a;
}

span.flatpickr-weekday {
    background: #1a1a1a;
    color: #888;
    font-weight: 600;
}

.flatpickr-days {
    border: none;
}

.dayContainer {
    background: #1a1a1a;
}

.flatpickr-day {
    color: #fff;
    border-radius: 0.375rem;
}

.flatpickr-day:hover {
    background: #2a2a2a;
    border-color: #2a2a2a;
}

.flatpickr-day.today {
    border-color: #ff0502;
}

.flatpickr-day.today:hover {
    background: #ff0502;
    border-color: #ff0502;
}

.flatpickr-day.selected,
.flatpickr-day.selected:hover {
    background: #ff0502;
    border-color: #ff0502;
    color: #fff;
}

.flatpickr-day.flatpickr-disabled,
.flatpickr-day.flatpickr-disabled:hover {
    color: #444;
}

.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {
    color: #555;
}

.flatpickr-day.prevMonthDay:hover,
.flatpickr-day.nextMonthDay:hover {
    background: #2a2a2a;
    border-color: #2a2a2a;
}
</style>
