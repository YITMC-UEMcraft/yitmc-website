<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    :width="size"
    :height="size"
    :viewBox="viewBox"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    :class="className"
  >
    <path v-for="(d, i) in paths" :key="i" :d="d" />
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  name: string
  size?: number
}>(), {
  size: 20,
})

const icons: Record<string, { vb: string; paths: string[] }> = {
  'music': {
    vb: '0 0 24 24',
    paths: ['M9 18V5l12-2v13', 'M9 18a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z', 'M21 16a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
  },
  'video': {
    vb: '0 0 24 24',
    paths: ['M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z', 'M14 2v4a2 2 0 0 0 2 2h4', 'M10 11l5 4-5 4v-8Z'],
  },
  'image': {
    vb: '0 0 24 24',
    paths: ['M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4', 'M21 9V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4', 'M7 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z', 'M21 15l-4-4-8 8-3-3-4 4'],
  },
  'users': {
    vb: '0 0 24 24',
    paths: ['M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M22 21v-2a4 4 0 0 0-3-3.87', 'M16 3.13a4 4 0 0 1 0 7.75', 'M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z'],
  },
  'chevron-down': {
    vb: '0 0 24 24',
    paths: ['M6 9l6 6 6-6'],
  },
  'shirt': {
    vb: '0 0 24 24',
    paths: ['M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23Z'],
  },
  'megaphone': {
    vb: '0 0 24 24',
    paths: ['M3 11h2a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H3v-8Z', 'M7 11V9a5 5 0 0 1 5-5v0a5 5 0 0 1 5 5v2', 'M12 4v0M12 8v0', 'M21 11l-6 3v-6l6 3Z'],
  },
  'construction': {
    vb: '0 0 24 24',
    paths: ['M17 10c.8 0 1.5.3 2 .8l3 3a3 3 0 0 1 0 4.2l-4 4a3 3 0 0 1-4.2 0l-3-3A3 3 0 0 1 10 18V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v10', 'M10 8V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4', 'M3 13h4', 'M3 17h4'],
  },
  'trophy': {
    vb: '0 0 24 24',
    paths: ['M6 9H4.5a2.5 2.5 0 0 1 0-5H6', 'M18 9h1.5a2.5 2.5 0 0 0 0-5H18', 'M4 22h16', 'M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22', 'M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22', 'M18 2H6v7a6 6 0 0 0 12 0V2Z'],
  },
  'party-popper': {
    vb: '0 0 24 24',
    paths: ['M5.8 11.3 2 22l10.7-3.79', 'M4 3h.01', 'M22 8h.01', 'M15 2h.01', 'M22 20h.01', 'M22 2l-5.5 5.5', 'M8 16l6-6', 'M11 6c-1.5 3.5-3.5 5.5-7 7'],
  },
  'message-circle': {
    vb: '0 0 24 24',
    paths: ['M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z'],
  },
  'smartphone': {
    vb: '0 0 24 24',
    paths: ['M17 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2Z', 'M12 18h.01'],
  },
  'heart': {
    vb: '0 0 24 24',
    paths: ['M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z'],
  },
  'help-circle': {
    vb: '0 0 24 24',
    paths: ['M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z', 'M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3', 'M12 17h.01'],
  },
  'gamepad': {
    vb: '0 0 24 24',
    paths: ['M6 12h4', 'M8 10v4', 'M15 13h.01', 'M18 11h.01', 'M18 7l3.7 3.7a2.1 2.1 0 0 1 0 3l-1.7 1.7a2.1 2.1 0 0 1-3 0L8.3 6.7a2.1 2.1 0 0 1 0-3L10 2a2.1 2.1 0 0 1 3 0Z'],
  },
  'camera': {
    vb: '0 0 24 24',
    paths: ['M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z', 'M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
  },
  'book-open': {
    vb: '0 0 24 24',
    paths: ['M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z', 'M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z'],
  },
  'target': {
    vb: '0 0 24 24',
    paths: ['M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z', 'M18 12a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z', 'M14 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z'],
  },
  'building': {
    vb: '0 0 24 24',
    paths: ['M6 22V2h12v20', 'M6 22H2v-6l4-4', 'M18 22h4v-6l-4-4', 'M9 6h.01', 'M15 6h.01', 'M9 10h.01', 'M15 10h.01', 'M9 14h.01', 'M15 14h.01', 'M9 18h.01', 'M15 18h.01'],
  },
  'mail': {
    vb: '0 0 24 24',
    paths: ['M22 6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2Z', 'M22 6l-8 6-8-6'],
  },
  'user': {
    vb: '0 0 24 24',
    paths: ['M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2', 'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
  },
  'calendar': {
    vb: '0 0 24 24',
    paths: ['M19 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z', 'M16 2v4', 'M8 2v4', 'M3 10h18'],
  },
  'clipboard': {
    vb: '0 0 24 24',
    paths: ['M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2', 'M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1Z'],
  },
  'check': {
    vb: '0 0 24 24',
    paths: ['M20 6 9 17l-5-5'],
  },
  'map': {
    vb: '0 0 24 24',
    paths: [
      'M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z',
      'M15 5.764v15',
      'M9 3.236v15',
    ],
  },
  'chevron-right': {
    vb: '0 0 24 24',
    paths: ['M9 18l6-6-6-6'],
  },
}

const icon = computed(() => icons[props.name] || icons['help-circle'])
const viewBox = computed(() => icon.value.vb)
const paths = computed(() => icon.value.paths)
const className = computed(() => `app-icon app-icon--${props.name}`)
</script>

<style scoped>
.app-icon {
  display: inline-block;
  vertical-align: middle;
  flex-shrink: 0;
}
</style>
