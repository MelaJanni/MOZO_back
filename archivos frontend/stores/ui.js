import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
  // --- STATE ---
  const isOffCanvasOpen = ref(false);

  // --- ACTIONS ---
  function toggleOffCanvas() {
    isOffCanvasOpen.value = !isOffCanvasOpen.value;
  }

  function closeOffCanvas() {
    isOffCanvasOpen.value = false;
  }

  return {
    // State
    isOffCanvasOpen,
    // Actions
    toggleOffCanvas,
    closeOffCanvas,
  };
}); 