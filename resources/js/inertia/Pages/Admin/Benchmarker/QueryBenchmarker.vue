<script setup>
const props = defineProps({});

const notification = useToast();
const { isRequired } = useRules();

const form = useForm({
  query: '',
  iterations: 1,
});

const loader = ref(false);
const exectionTime = ref(null);
const errorMessage = ref(null);
console.log('Query Benchmarker');
const onSubmit = isValid => {
  if (isValid) {
    exectionTime.value = null;
    errorMessage.value = null;
    loader.value = true;
    axios
      .post(route('admin.benchmarker.query.process'), {
        iterations: form.iterations,
        query: form.query,
      })
      .then(response => {
        loader.value = false;
        let { execution_time_ms, error, message } = response.data;

        if (error === true) {
          errorMessage.value = message;
          notification.error({
            title: 'An error occurred',
            position: 'top',
          });
        } else {
          exectionTime.value = execution_time_ms;
        }
      })
      .catch(error => {
        loader.value = false;

        if (error?.response?.status === 422) {
          errorMessage.value =
            error?.response?.data?.message || 'An error occurred';
        } else {
          errorMessage.value = 'An error occurred';
        }

        notification.error({
          title: 'Error Occurred',
          position: 'top',
        });
      });
  }
};
</script>
<template>
  <Head title="Query Benchmarker" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Query Benchmarker</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid">
      <x-input
        type="number"
        min="1"
        max="5"
        v-model="form.iterations"
        placeholder="Iterations"
        class="w-full"
        label="Iterations"
        required
      />
    </div>
    <div class="grid">
      <x-textarea
        :rules="[isRequired]"
        type="text"
        v-model="form.query"
        :adjust-to-text="false"
        class="w-full"
        :error="form.errors.query"
        rows="20"
        columns="50"
        label="Query"
        required
      />
    </div>

    <p class="font-medium" v-if="exectionTime">
      Exection Time: <span class="text-red-500">{{ exectionTime }}</span>
    </p>

    <p class="font-medium text-red-500" v-if="errorMessage">
      {{ errorMessage }}
    </p>

    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="emerald"
        type="submit"
        :loading="loader"
        :disabled="loader"
      >
        Run
      </x-button>
    </div>
  </x-form>
</template>
