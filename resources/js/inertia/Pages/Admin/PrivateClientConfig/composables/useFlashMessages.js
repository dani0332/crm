import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useFlashMessages() {
  const page = usePage();
  const showFlashMessage = ref(false);

  const flashSuccess = computed(() => page.props.flash?.success);
  const flashError = computed(() => page.props.flash?.error);

  watch([flashSuccess, flashError], () => {
    if (flashSuccess.value || flashError.value) {
      showFlashMessage.value = true;

      setTimeout(() => {
        showFlashMessage.value = false;
      }, 5000);
    }
  });

  const hideFlashMessage = () => {
    showFlashMessage.value = false;
  };

  return {
    flashSuccess,
    flashError,
    showFlashMessage,
    hideFlashMessage,
  };
}
