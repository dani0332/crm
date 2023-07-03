<script setup>
const toast = useToast();

const onceDone = ref(false);

const notification = flash => {
  if (onceDone.value) return;

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

  onceDone.value = true;
};
</script>

<template>
  <div
    v-if="
      $page.props.flash.message ||
      $page.props.flash.error ||
      $page.props.flash.success ||
      $page.props.flash.warning ||
      $page.props.flash.info
    "
    class="hidden"
  >
    {{ notification($page.props.flash) }}
  </div>
</template>
