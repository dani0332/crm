<script setup>
const page = usePage();

const breadcrumbs = computed(() => {
  return insertBetween(page.props.breadcrumbs || [], '/');
});

const insertBetween = (items, insertion) => {
  return items.flatMap((value, index, array) =>
    array.length - 1 !== index ? [value, insertion] : value,
  );
};

console.log(breadcrumbs);
</script>

<template>
  <nav v-if="breadcrumbs" class="mb-4">
    <ol class="flex gap-1">
      <li v-for="page in breadcrumbs">
        <span v-if="page === '/'" class="text-gray-400">/</span>
        <span v-else>
          <Link
            v-if="!page.current"
            :href="page.url"
            class="text-sm border-b text-gray-500"
          >
            <span>{{ page.title }} </span>
          </Link>
          <span class="text-sm font-semibold" v-else>{{ page.title }}</span>
        </span>
      </li>
    </ol>
  </nav>
</template>
