<script setup>
const props = defineProps({
  documents: Object,
  title: String,
});
</script>

<template>
  <div>
    <h4 class="font-semibold text-primary-800 my-1">{{ title }}</h4>
    <ul class="">
      <li class="my-1" v-for="document in documents">
        <Link
          :href="document.document_path"
          class="text-primary-500 hover:underline"
        >
          {{ document.document_name }}
        </Link>
      </li>
    </ul>
  </div>
</template>

<style scoped></style>
