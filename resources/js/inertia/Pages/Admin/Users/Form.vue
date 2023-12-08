<script setup>
const props = defineProps({
  roles: Object,
  products: Array,
  teams: Array,
  subTeams: Array,
  user: Object,
  userRole: Object,
  selectedAdditionalTeams: Array,
  userProductIds: Array,
  userTeamIds: Array,
  managers: Array,
  userManagerIds: Array,
  permissions: Array,
  userPermissions: Array,
});

const page = usePage();
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const { isRequired, isMobileNo, isEmail } = useRules();
const subTeams = ref([]);
const teams = ref([]);
const managers = ref([]);
const isError = ref(false);

const loader = reactive({
  table: false,
  teamLoader: false,
  subTeamLoader: false,
  managers: false,
});

const selectedRoles = computed(() => {
  if (props.user && props.user.roles) return props.user.roles.map(x => x.name);
  else return [];
});

const userForm = useForm({
  id: props.user?.id ?? null,
  name: props.user?.name ?? null,
  email: props.user?.email ?? null,
  mobile_no: props.user?.mobile_no ?? null,
  landline_no: props.user?.landline_no ?? null,
  password: null,
  products: props?.userProductIds?.length > 0 ? props.userProductIds : [],
  teams: props.userTeamIds?.length > 0 ? props.userTeamIds : null,
  roles: selectedRoles.value?.length > 0 ? selectedRoles.value : null,
  manager: props.userManagerIds ?? null,
  sub_team_id: null,
  additionalTeams: props?.selectedAdditionalTeams ?? [],
  is_active: props.user?.is_active ?? false,
  primary_product: props.userProductIds ? props?.userProductIds[0] : null,
  permissions: props?.userPermissions ?? null,
});

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const roles = computed(() => {
  return Object.keys(props.roles).map(key => ({
    text: key,
    id: props.roles[key],
  }));
});

const computedTeams = computed(() => {
  if (teams.value.length > 0)
    return teams.value.map(item => ({ value: item.id, label: item.name }));
  else return [];
});

const computedSubTeams = computed(() => {
  if (subTeams.value.length > 0)
    return subTeams.value.map(item => ({ value: item.id, label: item.name }));
  else return [];
});

const validRole = computed(() => {
  return (userForm.roles == null && isError.value) ?? false;
});

const validProducts = computed(() => {
  return (userForm.products == null && isError.value) ?? false;
});

const validTeams = computed(() => {
  return (userForm.teams == null && isError.value) ?? false;
});

const loadTeamsByProduct = async e => {
  loader.teamLoader = true;
  try {
    let response = await axios.post('/get-product-teams', {
      productIds: userForm.products,
    });
    if (response.data.length > 0) teams.value = [...response.data];
    else teams.value = [];

    loader.teamLoader = false;
  } catch (e) {
    loader.teamLoader = false;
  }
};

const loadManagerByTeam = async () => {
  loader.managers = true;
  try {
    let response = await axios.post('/get-team-managers', {
      teamId: userForm.teams ?? userForm.products,
    });
    if (response.data.length > 0) managers.value = [...response.data];
    else managers.value = [];
    loader.managers = false;
  } catch (e) {
    loader.managers = false;
  }
};

const loadSubTeams = async () => {
  loader.subTeamLoader = true;
  try {
    let response = await axios.post('/get-sub-teams', {
      teamId: userForm.teams,
    });
    if (response.data.length > 0) subTeams.value = [...response.data];
    else subTeams.value = [];

    loader.subTeamLoader = false;
  } catch (e) {
    loader.subTeamLoader = false;
  }
};

function onSubmit(isValid) {
  if (!userForm.roles || !userForm.teams || !userForm.products) {
    isError.value = true;
  }

  if (isValid && !isError.value) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('users.update', userForm.id)
      : route('users.store');

    userForm.submit(method, url, {
      onError: errors => {
        console.log(errors);
        userForm.setError(errors);
      },
      onSuccess: () => {},
    });
  }
}

const setInitialState = async () => {
  if (isEdit.value) {
    await loadTeamsByProduct();
    await loadSubTeams();
    await loadManagerByTeam();
  }
};

onMounted(() => setInitialState());
</script>
<template>
  <Head :title="isEdit ? 'Edit Users' : 'Create Users'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Edit' : 'Create' }} Users
    </h2>
    <div>
      <Link :href="route('users.index')">
        <x-button size="sm" color="#1d83bc" tag="div"> Users List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="flex justify-center mb-5" v-if="isEdit">
    <img
      v-if="user && user?.profile_photo_path"
      class="rounded-full"
      :src="user.profile_photo_path"
      alt=""
    />
    <img
      v-else
      class="rounded-full"
      src="/image/alfred-theme.png"
      alt=""
      height="150"
      width="150"
    />
  </div>
  <div class="grid sm:grid-cols-1 justify-center my-2" v-if="isEdit">
    <x-toggle
      class="mx-auto"
      size="xs"
      v-model="userForm.is_active"
      color="primary"
    />
  </div>
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="NAME" required>
        <x-input v-model="userForm.name" :rules="[isRequired]" class="w-full" />
      </x-field>
      <x-field label="EMAIL ADDRESS" required>
        <x-input
          type="email"
          v-model="userForm.email"
          :rules="[isRequired, isEmail]"
          class="w-full"
        />
      </x-field>
      <x-field label="MOBILE NUMBER" required>
        <x-input
          v-model="userForm.mobile_no"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
        />
      </x-field>
      <x-field label="LANDLINE NUMBER" required>
        <x-input
          type="tel"
          v-model="userForm.landline_no"
          :rules="[isRequired]"
          class="w-full"
        />
      </x-field>
      <x-field label="PASSWORD">
        <x-input v-model="userForm.password" class="w-full" type="password" />
      </x-field>
      <x-field label="ROLE" required>
        <ComboBox
          v-model="userForm.roles"
          :options="
            roles.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :rules="[isRequired]"
          :hasError="validRole"
        />
      </x-field>
      <x-field label="PRODUCTS" required>
        <ComboBox
          v-model="userForm.products"
          :rules="[isRequired]"
          :hasError="validProducts"
          :options="
            props.products.map(item => ({
              value: item.id,
              label: item.name,
            }))
          "
          @update:modelValue="loadTeamsByProduct($event), loadManagerByTeam()"
        />
      </x-field>
      <x-field label="TEAMS" required>
        <ComboBox
          v-model="userForm.teams"
          :options="computedTeams"
          :loading="loader.teamLoader"
          :rules="[isRequired]"
          :hasError="validTeams"
          @update:modelValue="loadSubTeams($event)"
        />
      </x-field>
      <x-field label="SUB TEAMS">
        <x-select
          v-model="userForm.sub_team_id"
          class="w-full"
          :loading="loader.subTeamLoader"
          :options="computedSubTeams"
        ></x-select>
      </x-field>
      <x-field label="LOB VISIBILITY">
        <x-select
          :multiple="true"
          :options="
            props.products.map(item => ({
              value: item.id,
              label: item.name,
            }))
          "
          class="w-full"
          v-model="userForm.additionalTeams"
          placeholder="Select teams for MyLeads Tab visiblity"
        />
      </x-field>
    </div>

    <div
      class="grid grid-cols-2 gap-4"
      v-if="isEdit && hasRole(rolesEnum.Admin)"
    >
      <x-field label="PERMISSIONS">
        <ComboBox
          :multiple="true"
          v-model="userForm.permissions"
          :options="
            props.permissions.map(x => ({
              value: x.id,
              label: x.name,
            }))
          "
          class="w-full"
        />
      </x-field>
      <x-field label="PRIMARY PRODUCT">
        <x-select
          v-model="userForm.primary_product"
          :options="
            props.products.map(item => ({
              value: item.id,
              label: item.name,
            }))
          "
          class="w-full"
        >
        </x-select>
      </x-field>
    </div>
    <div class="grid sm:grid-cols-1 gap-4 mt-4">
      <x-field label="MANAGER">
        <ComboBox
          v-model="userForm.manager"
          :options="
            managers.map(x => ({
              value: x.id,
              label: x.name,
            }))
          "
          :loading="loader.managers"
        />
      </x-field>
    </div>

    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit">
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>
</template>