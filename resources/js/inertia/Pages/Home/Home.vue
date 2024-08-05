<script setup>
const props = defineProps({
  im_logo: String,
});
</script>
<template>
  <div>
    <Head title="Home" />
    <div class="min-h-[80svh] flex flex-col gap-4 justify-center items-center">
      <div class="p-2 h-auto max-w-sm">
        <img
          :src="im_logo"
          alt="IMCRM"
          class="w-full"
          width="439"
          height="66"
        />
      </div>
      <h1 class="text-3xl text-primary text-center font-semibold">
        Welcome to IMCRM!
      </h1>
    </div>
  </div>
</template>
