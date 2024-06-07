<script setup>
const props = defineProps({
  code: {
    type: String,
  },
});

const id = ref(props.code);
const tableHeaders = ref([]);
const tableData = ref([]);

const onSubmit = () => {
  let data = {
    code: props.code,
    jsonData: true,
  };
  axios
    .post('/get-lob-raw-data', data)
    .then(res => {
      let { data } = { ...res };

      tableData.value = [{ ...data[0] }];
      console.log(tableData.value);
      tableHeaders.value = data.flatMap(obj => {
        return Object.keys(obj).map(key => {
          return { text: key, value: key };
        });
      });
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      //   auditLogs.loading = false;
    });
};
</script>

<template>
  <x-collapse show-icon class="p-4 rounded shadow mb-6 bg-white">
    <h3 class="font-semibold text-primary-800 text-lg">Quote Data</h3>
    <template #content>
      <x-divider class="my-4"></x-divider>
      <x-form @submit="onSubmit" :auto-focus="false">
        <div class="grid sm:grid-cols-2 gap-4">
          <x-field label="Ref ID" required>
            <x-input
              :value="id"
              type="text"
              class="w-full"
              maxLength="20"
              disabled
            />
          </x-field>
        </div>
        <div class="flex justify-end gap-3 mb-4">
          <x-button size="md" color="emerald" type="submit"> Search </x-button>
        </div>
      </x-form>
      <DataTable
        v-if="tableHeaders.length > 0"
        table-class-name="tablefixed mt-3"
        :headers="tableHeaders"
        border-cell
        :items="tableData"
        hide-rows-per-page
        hide-footer
        fixed-checkbox
      >
      </DataTable>
    </template>
  </x-collapse>
</template>