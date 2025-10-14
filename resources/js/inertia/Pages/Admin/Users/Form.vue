<script setup>
const props = defineProps({
  roles: Object,
  products: Array,
  teams: Array,
  subTeams: Array,
  departments: Array,
  departmentIds: Array,
  user: Object,
  userRole: Object,
  selectedAdditionalTeams: Array,
  userProductIds: Array,
  userTeamIds: Array,
  department_ids: Array,
  managers: Array,
  userManagerIds: Array,
  permissions: Array,
  userPermissions: Array,
  businessTypes: Array,
  userBusinessTypeIds: Array,
  rmCategories: Array,
});

const page = usePage();
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const notification = useToast();

const { isRequired, isMobileNo, isEmail, allowEmpty } = useRules();
const subTeams = ref([]);
const departments = ref([]);
const teams = ref([]);
const managers = ref([]);
const isError = ref(false);
const showBusinessCategories = ref(false);

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

const isAllowed = computed(() => {
  return hasRole(rolesEnum.Admin);
});

const userForm = useForm({
  id: props.user?.id ?? null,
  name: props.user?.name ?? null,
  email: props.user?.email ?? null,
  mobile_no: props.user?.mobile_no ?? null,
  landline_no: props.user?.landline_no ?? null,
  password: null,
  products: props?.userProductIds?.length > 0 ? props.userProductIds : [],
  teams: props.userTeamIds?.length > 0 ? props.userTeamIds : [],
  roles: selectedRoles.value?.length > 0 ? selectedRoles.value : [],
  manager: props.userManagerIds ?? null,
  sub_team_id: props.user?.sub_team_id ?? null,
  additionalTeams: props?.selectedAdditionalTeams ?? [],
  is_active:
    props.user?.is_active == 1 || props.user?.is_active == 0
      ? Boolean(props.user?.is_active)
      : true,
  primary_product: props.userProductIds ? props?.userProductIds[0] : null,
  permissions: props?.userPermissions ?? null,
  calendar_link: props.user?.calendar_link ?? null,
  phone_calendar_link: props.user?.phone_calendar_link ?? null,
  department_id: props.user?.department_id ?? null,
  businessTypes: props?.userBusinessTypeIds ?? [],
  department_ids: props.department_ids?.length > 0 ? props.department_ids : [],
  rm_category_id: props.user?.rm_category_id,
});

const isAdvisor = computed(() => {
  const regex = /\badvisor|ADVISOR\b/i;
  return userForm.roles.some(role => regex.test(role));
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

const computedDepartments = computed(() => {
  if (page.props.departments?.length > 0)
    return page.props.departments?.map(item => ({
      value: item.id,
      label: item.name,
    }));
  else return [];
});

const validRole = computed(() => {
  return (userForm.roles.length == 0 && isError.value) ?? false;
});

const validProducts = computed(() => {
  return (userForm.products.length == 0 && isError.value) ?? false;
});

const validTeams = computed(() => {
  return (userForm.teams.length == 0 && isError.value) ?? false;
});

const loadTeamsByProduct = async e => {
  loader.teamLoader = true;
  try {
    let response = await axios.post('/get-product-teams', {
      productIds: userForm.products,
    });
    if (response.data.length > 0) {
      let newTeams = response.data.filter(
        x => !teams.value.some(item => item.id === x.id),
      );
      teams.value.unshift(...newTeams);
      loadManagerByTeam();
    }

    loader.teamLoader = false;
  } catch (e) {
    loader.teamLoader = false;
  }
};
const loadDepartmentsByTeam = async e => {
  loader.departLoader = true;
  try {
    let response = await axios.post('/get-team-departments', {
      teamIds: userForm.teams,
    });
    if (response.data.length > 0) {
      departments.value = response.data;
    }

    loader.departLoader = false;
  } catch (e) {
    loader.departLoader = false;
  }
};

const loadManagerByTeam = async () => {
  loader.managers = true;
  try {
    let response = await axios.post('/get-team-managers', {
      teamId: userForm.products,
    });
    if (response.data.length > 0) {
      let newManagers = response.data.filter(
        x => !managers.value.some(item => item.id === x.id),
      );
      managers.value.unshift(...newManagers);
    }
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
  if (
    userForm.roles.length == 0 ||
    userForm.teams.length == 0 ||
    userForm.products.length == 0
  ) {
    isError.value = true;
  } else {
    isError.value = false;
  }

  if (isValid && !isError.value) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('users.update', userForm.id)
      : route('users.store');

    userForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
      onSuccess: response => {
        userForm.reset();
      },
    });
  }
}

const resolveBusinessCategoriesShowHide = selectedRoles => {
  showBusinessCategories.value = selectedRoles.includes(
    rolesEnum.CorpLineAdvisor,
  );
};

const setInitialState = async () => {
  if (isEdit.value) {
    resolveBusinessCategoriesShowHide(userForm.roles);
    await loadTeamsByProduct();
    await loadSubTeams();
    await loadDepartmentsByTeam();
  }
};

onMounted(() => setInitialState());

watch(
  () => userForm.teams,
  () => {
    loadSubTeams();
    loadDepartmentsByTeam();
  },
  { deep: true },
);
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
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-input
        label="NAME"
        required
        v-model="userForm.name"
        :rules="[isRequired]"
        class="w-full"
      />
      <x-input
        type="email"
        v-model="userForm.email"
        :rules="[isRequired, isEmail]"
        class="w-full"
        name="email"
        :error="$page.props.errors.email"
        label="EMAIL ADDRESS"
        required
      />
      <x-input
        v-model="userForm.mobile_no"
        class="w-full"
        :rules="[isRequired, isMobileNo]"
        label="MOBILE NUMBER"
        required
      />
      <x-input
        :rules="[isRequired]"
        type="tel"
        v-model="userForm.landline_no"
        class="w-full"
        label="LANDLINE NUMBER"
        required
      />
      <x-input
        label="PASSWORD"
        required
        v-if="isAllowed"
        v-model="userForm.password"
        class="w-full"
        type="password"
      />
      <x-select
        label="ROLES"
        v-model="userForm.roles"
        :options="
          roles.map(item => ({
            value: item.id,
            label: item.text,
          }))
        "
        :rules="[isRequired]"
        @update:model-value="resolveBusinessCategoriesShowHide"
        filterable
        multiple
        truncate
        placeholder="Select Roles"
        filterPlaceholder="Filter roles...."
        required
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="userForm.roles = roles.map(item => item.id)"
            @clear="userForm.roles = []"
          />
        </template>
      </x-select>

      <x-select
        v-if="hasRole(rolesEnum.Admin) && showBusinessCategories"
        label="Busineess Categories"
        v-model="userForm.businessTypes"
        :options="businessTypes"
        :rules="[isRequired]"
        multiple
        placeholder="Select Business Categories"
        filterPlaceholder="Filter business categories...."
        truncate
        filterable
        required
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              userForm.businessTypes = businessTypes.map(item => item.value)
            "
            @clear="userForm.businessTypes = []"
          />
        </template>
      </x-select>

      <x-select
        label="PRODUCTS"
        v-model="userForm.products"
        :options="
          products.map(item => ({
            value: item.id,
            label: item.name,
          }))
        "
        @update:modelValue="loadTeamsByProduct"
        multiple
        truncate
        filterable
        placeholder="Select Products"
        filterPlaceholder="Filter products...."
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="userForm.products = products.map(item => item.id)"
            @clear="userForm.products = []"
          />
        </template>
      </x-select>

      <x-select
        label="TEAMS"
        v-model="userForm.teams"
        :options="computedTeams"
        :rules="[isRequired]"
        :loading="loader.teamLoader"
        multiple
        truncate
        filterable
        placeholder="Select Teams"
        filterPlaceholder="Filter teams...."
        required
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="userForm.teams = computedTeams.map(item => item.value)"
            @clear="userForm.teams = []"
          />
        </template>
      </x-select>

      <x-select
        label="SUB TEAM"
        v-model="userForm.sub_team_id"
        :options="computedSubTeams"
        :loading="loader.subTeamLoader"
        placeholder="Select Sub Team"
        filterPlaceholder="Filter sub teams...."
      />

      <x-select
        label="DEPARTMENT"
        v-model="userForm.department_id"
        :options="computedDepartments"
        placeholder="Select Department"
        filterPlaceholder="Filter departments...."
      />

      <x-select
        v-model="userForm.department_ids"
        :loading="loader.departLoader"
        placeholder="Select Department"
        :options="computedDepartments"
        multiple
        truncate
        filterable
        required
        filterPlaceholder="Filter departments...."
        label="DEPARTMENTS VISIBILITY"
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              userForm.department_ids = computedDepartments.map(
                item => item.value,
              )
            "
            @clear="userForm.department_ids = []"
          />
        </template>
      </x-select>

      <x-select
        label="LOB VISIBILITY"
        v-model="userForm.additionalTeams"
        :options="
          props.products.map(x => ({
            value: x.id,
            label: x.name,
          }))
        "
        multiple
        truncate
        filterable
        placeholder="Select LOB"
        filterPlaceholder="Filter LOB...."
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              userForm.additionalTeams = props.products.map(item => item.id)
            "
            @clear="userForm.additionalTeams = []"
          />
        </template>
      </x-select>

      <x-select
        label="RM CATEGORY"
        v-model="userForm.rm_category_id"
        :options="props.rmCategories?.map(x => ({ value: x.id, label: x.text }))"
        :rules="[isRequired]"
        placeholder="Select RM Category"
        filterPlaceholder="Filter RM categories...."
        required
      />

      <x-select
        v-if="hasRole(rolesEnum.Admin)"
        label="PERMISSIONS"
        v-model="userForm.permissions"
        :options="
          props.permissions.map(x => ({
            value: x.id,
            label: x.name,
          }))
        "
        multiple
        truncate
        filterable
        placeholder="Select Permissions"
        filterPlaceholder="Filter permissions...."
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              userForm.permissions = props.permissions.map(item => item.id)
            "
            @clear="userForm.permissions = []"
          />
        </template>
      </x-select>

      <x-select
        v-model="userForm.is_active"
        :options="[
          { value: false, label: 'No' },
          { value: true, label: 'Yes' },
        ]"
        class="w-full"
        label="ACTIVE"
      >
      </x-select>

      <x-select
        v-if="hasRole(rolesEnum.Admin)"
        v-model="userForm.manager"
        :options="
          managers.map(x => ({
            value: x.id,
            label: x.name,
          }))
        "
        :loading="loader.managers"
        multiple
        truncate
        filterable
        placeholder="Select Manager"
        filterPlaceholder="Filter managers...."
        label="MANAGER"
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="userForm.manager = managers.map(item => item.id)"
            @clear="userForm.manager = []"
          />
        </template>
      </x-select>
    </div>
    <div class="grid sm:grid-cols-2 gap-4 mt-2">
      <x-textarea
        label="GOOGLE MEET CALENDAR (EMBEDDED LINK)"
        :required="isAdvisor"
        class="w-full text-md"
        v-model="userForm.calendar_link"
        :rules="isAdvisor ? [isRequired] : []"
      >
      </x-textarea>
      <x-textarea
        class="w-full text-md"
        v-model="userForm.phone_calendar_link"
        :rules="isAdvisor ? [isRequired] : []"
        label="PHONE CALL CALENDAR (EMBEDDED LINK)"
        :required="isAdvisor"
      >
      </x-textarea>
    </div>

    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit">
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>
</template>
