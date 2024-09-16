<script setup>
const props = defineProps({
  code: {
    type: String,
  },
  modelType: {
    type: String,
  },
});

const page = usePage();
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const can = permission => useCan(permission);
const permissionEnum = page.props.permissionsEnum;

const id = ref(props.code);
const showModal = ref(false);
const isloading = ref(false);
const tableHeaders = ref([]);
const tableData = ref([]);

const leadDetails = ref({});
const customers = ref({});
const leadsMembers = ref([]);
const leadsPayments = ref([]);

const membersHeaders = ref([{ Text: '', value: '' }]);

const onSubmit = () => {
  isloading.value = true;
  let data = {
    code: props.code.split('-')[1],
    modelType: props.modelType,
    jsonData: true,
  };
  axios
    .post('/get-lob-raw-data', data)
    .then(res => {
      let { record } = res.data;
      leadDetails.value = { ...record };
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      isloading.value = false;
      showModal.value = true;
    });
};
</script>

<template>
  <x-accordion
    v-if="can(permissionEnum.QUOTE_RAW_DATA)"
    class="p-4 rounded shadow mb-6 bg-white"
  >
    <x-accordion-item @change="onChange">
      <h3 class="font-semibold text-primary-800 text-lg">
        Quote Data For Engineering Only
      </h3>
      <template #content>
        <x-divider class="mb-4 mt-1" />
        <div class="text-center py-3" v-if="tableData.length === 0">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onSubmit"
            :loading="isloading"
          >
            Load Quote
          </x-button>
        </div>

        <AppModal v-model="showModal" show-header show-close>
          <template #header>
            <h2>Quote Raw Data</h2>
          </template>
          <template #default>
            <h2 class="font-bold mb-2 text-primary-800">Lead Details:</h2>
            <div class="flex flex-wrap">
              <div
                v-for="(value, key) in leadDetails"
                :key="key"
                class="bg-gray-300 p-2 m-1 flex rounded-md"
              >
                <span class="font-bold text-sm">{{ key }} </span
                ><span class="text-sm"> : {{ value ?? 'Null' }}</span>
              </div>
            </div>
          </template>
        </AppModal>
      </template>
    </x-accordion-item>
  </x-accordion>
</template>
