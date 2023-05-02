<script setup>
const toast = useToast();

const flash = reactive({
  message: usePage().props.flash?.message || '',
  error: usePage().props.flash?.error || '',
  success: usePage().props.flash?.success || '',
  warning: usePage().props.flash?.warning || '',
  info: usePage().props.flash?.info || '',
});

watchOnce(
  flash,
  flash => {
    if (
      !flash.message &&
      !flash.error &&
      !flash.success &&
      !flash.warning &&
      !flash.info
    )
      return;

    if (flash.message) {
      toast.success({
        title: flash.message,
        position: 'top',
      });
    } else if (flash.error) {
      toast.error({
        title: flash.error,
        position: 'top',
      });
    } else if (flash.success) {
      toast.success({
        title: flash.success,
        position: 'top',
      });
    } else if (flash.warning) {
      toast.warning({
        title: flash.warning,
        position: 'top',
      });
    } else if (flash.info) {
      toast.info({
        title: flash.info,
        position: 'top',
      });
    }
  },
  { deep: true, immediate: true },
);
</script>

<template>
  <div class="hidden"></div>
</template>
