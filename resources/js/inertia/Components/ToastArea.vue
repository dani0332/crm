<script setup>
const toast = useToast();
const page = usePage();

const flash = computed(() => page.props.flash || '');
const errors = computed(() => page.props.errors || '');

watch(
  flash,
  async () => {
    if (!flash.value) return;
    if (flash.value.message) {
      toast.success({
        title: flash.value.message,
        position: 'top',
      });
    } else if (flash.value.error) {
      toast.error({
        title: flash.value.error,
        position: 'top',
      });
    } else if (flash.value.success) {
      toast.success({
        title: flash.value.success,
        position: 'top',
      });
    } else if (flash.value.warning) {
      toast.warning({
        title: flash.value.warning,
        position: 'top',
      });
    } else if (flash.value.info) {
      toast.info({
        title: flash.value.info,
        position: 'top',
      });
    }
  },
  { immediate: true },
);
watch(
  errors,
  async () => {
    if (!errors.value) return;
    if (errors.value.flash) {
      toast.error({
        title: errors.value.flash,
        position: 'top',
      });
    }
  },
  { immediate: true },
);
</script>

<template>
  <div class="hidden"></div>
</template>
